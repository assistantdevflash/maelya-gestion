<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CategorieProduit;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ProduitController extends Controller
{
    public function index(Request $request)
    {
        $recherche = trim((string) $request->input('q'));
        $stock     = (string) $request->input('stock');
        $tri       = (string) $request->input('tri', 'nom');

        $produits = Produit::with(['categorie', 'fournisseur'])
            ->when($recherche !== '', fn ($q) => $q->where(function ($q) use ($recherche) {
                $like = "%{$recherche}%";
                $q->where('nom', 'like', $like)
                  ->orWhere('reference', 'like', $like)
                  ->orWhere('code_barre', 'like', $like);
            }))
            ->when($request->input('categorie'), fn ($q, $catId) => $q->where('categorie_id', $catId))
            ->when($stock === 'rupture', fn ($q) => $q->where('stock', '<=', 0))
            ->when($stock === 'bas', fn ($q) => $q->whereColumn('stock', '<=', 'seuil_alerte'))
            ->when($request->boolean('masques'), fn ($q) => $q->where('visible_boutique', false))
            ->when(true, function ($q) use ($tri) {
                match ($tri) {
                    'stock'     => $q->orderBy('stock'),
                    'prix'      => $q->orderByDesc('prix_vente'),
                    'recent'    => $q->latest(),
                    default     => $q->orderBy('nom'),
                };
            })
            ->paginate(25)
            ->withQueryString();

        $categories = CategorieProduit::orderBy('nom')->get();

        return view('dashboard.produits.index', compact('produits', 'categories', 'recherche', 'stock', 'tri'));
    }

    // ─── Import CSV ──────────────────────────────────────────────────────

    private const COLONNES_IMPORT = ['nom', 'reference', 'prix_achat', 'prix_vente', 'stock', 'seuil_alerte', 'unite'];

    public function importForm()
    {
        return view('dashboard.produits.import');
    }

    /** Gabarit CSV prêt à remplir (avec la bonne en-tête). */
    public function importModele()
    {
        $contenu = "nom;reference;prix_achat;prix_vente;stock;seuil_alerte;unite\n"
            . "Huile de ricin;HUIL-001;2500;4500;10;3;flacon\n";

        return response($contenu, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="modele-produits.csv"',
        ]);
    }

    /**
     * Import de produits depuis un fichier CSV.
     *
     * La référence sert de clé : une ligne dont la référence existe déjà met
     * le produit à jour, sinon il est créé. Une ligne invalide n'interrompt
     * pas l'import, elle est signalée dans le rapport.
     */
    public function import(Request $request)
    {
        $request->validate([
            'fichier' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $handle = fopen($request->file('fichier')->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', 'Fichier illisible.');
        }

        // Détection du séparateur sur la première ligne
        $premiere = fgets($handle);
        rewind($handle);
        $separateur = substr_count((string) $premiere, ';') >= substr_count((string) $premiere, ',') ? ';' : ',';

        $entetes = array_map(
            fn ($h) => strtolower(trim(str_replace('\xEF\xBB\xBF', '', $h))),
            (array) fgetcsv($handle, 0, $separateur)
        );

        $manquantes = array_diff(['nom', 'prix_vente'], $entetes);
        if ($manquantes !== []) {
            fclose($handle);

            return back()->with('error', 'Colonnes manquantes : ' . implode(', ', $manquantes) . '.');
        }

        $crees = $maj = 0;
        $erreurs = [];
        $ligne = 1;

        while (($valeurs = fgetcsv($handle, 0, $separateur)) !== false) {
            $ligne++;

            if (count(array_filter($valeurs, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $donnees = [];
            foreach ($entetes as $i => $colonne) {
                $donnees[$colonne] = isset($valeurs[$i]) ? trim((string) $valeurs[$i]) : null;
            }

            $nom = $donnees['nom'] ?? '';
            if ($nom === '') {
                $erreurs[] = "Ligne {$ligne} : nom vide";
                continue;
            }

            $attributs = [
                'nom'          => mb_substr($nom, 0, 150),
                'reference'    => $donnees['reference'] ?: null,
                'prix_achat'   => max(0, (int) ($donnees['prix_achat'] ?? 0)),
                'prix_vente'   => max(0, (int) ($donnees['prix_vente'] ?? 0)),
                'seuil_alerte' => max(0, (int) ($donnees['seuil_alerte'] ?? 0)),
                'unite'        => mb_substr($donnees['unite'] ?: 'unité', 0, 30),
            ];

            if ($attributs['prix_vente'] <= 0) {
                $erreurs[] = "Ligne {$ligne} : prix de vente invalide";
                continue;
            }

            $existant = $attributs['reference']
                ? Produit::where('reference', $attributs['reference'])->first()
                : null;

            if ($existant) {
                $existant->update($attributs);
                $maj++;
                continue;
            }

            // Le stock initial n'est appliqué qu'à la création : un import ne
            // doit pas écraser un stock géré par les mouvements.
            Produit::create($attributs + [
                'stock'            => max(0, (int) ($donnees['stock'] ?? 0)),
                'actif'            => true,
                'visible_boutique' => false,
            ]);
            $crees++;
        }

        fclose($handle);

        Cache::forget('caisse_catalog_' . (session('current_institut_id', Auth::user()->institut_id)));
        Cache::forget('boutique_' . (session('current_institut_id', Auth::user()->institut_id)) . '_produits');

        $message = \App\Helpers\TexteHelper::pluriel($crees, 'produit créé', 'produits créés')
            . ', ' . $maj . ' mis à jour.';

        return redirect()->route('dashboard.produits.index')
            ->with('success', $message)
            ->with('import_erreurs', array_slice($erreurs, 0, 20));
    }

    /**
     * Actions groupées : catégorie, visibilité boutique, activation.
     */
    public function actionsGroupees(Request $request)
    {
        $data = $request->validate([
            'ids'            => ['required', 'array', 'min:1'],
            'ids.*'          => ['uuid'],
            'action'         => ['required', 'in:categorie,boutique_on,boutique_off,activer,desactiver'],
            'categorie_id'   => ['nullable', 'uuid', 'exists:categories_produits,id'],
        ]);

        // Le scope d'établissement garantit qu'on ne touche qu'à ses produits
        $produits = Produit::whereIn('id', $data['ids']);
        $nb = (clone $produits)->count();

        match ($data['action']) {
            'categorie'    => $produits->update(['categorie_id' => $data['categorie_id'] ?? null]),
            'boutique_on'  => $produits->update(['visible_boutique' => true]),
            'boutique_off' => $produits->update(['visible_boutique' => false]),
            'activer'      => $produits->update(['actif' => true]),
            'desactiver'   => $produits->update(['actif' => false]),
        };

        return back()->with('success', \App\Helpers\TexteHelper::pluriel($nb, 'produit modifié', 'produits modifiés') . '.');
    }

    /**
     * Fiche produit : la ressource excluait `show`, donc un produit n'avait
     * aucune page de détail — impossible de consulter son historique.
     */
    public function show(Produit $produit)
    {
        $produit->load(['categorie', 'images']);

        $mouvements = $produit->mouvements()
            ->with('user:id,prenom,nom_famille')
            ->latest()
            ->limit(30)
            ->get();

        // Dernières ventes de ce produit (caisse + factures)
        $ventes = \App\Models\VenteItem::query()
            ->where('type', 'produit')
            ->where('item_id', $produit->id)
            ->with('vente:id,numero_facture,created_at,total')
            ->latest()
            ->limit(10)
            ->get();

        $totalSorti = (int) $produit->mouvements()
            ->whereIn('type', ['sortie_vente'])
            ->sum('quantite');

        return view('dashboard.produits.show', [
            'produit'     => $produit,
            'mouvements'  => $mouvements,
            'ventes'      => $ventes,
            'totalSorti'  => $totalSorti,
            'valeurStock' => (int) $produit->stock * (int) ($produit->cout_moyen_pondere ?: $produit->prix_achat),
        ]);
    }

    public function create()
    {
        $categories = CategorieProduit::orderBy('nom')->get();
        $fournisseurs = \App\Models\Fournisseur::where('actif', true)->orderBy('nom')->get(['id', 'nom']);
        return view('dashboard.produits.create', compact('categories', 'fournisseurs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'categorie_id' => ['nullable', 'uuid'],
            'fournisseur_id' => ['nullable', 'uuid', 'exists:fournisseurs,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'code_barre' => ['nullable', 'string', 'max:50'],
            'prix_achat' => ['nullable', 'integer', 'min:0'],
            'prix_vente' => ['required', 'integer', 'min:0'],
            'prix_promo' => ['nullable', 'integer', 'min:0', 'lt:prix_vente'],
            'stock' => ['required', 'integer', 'min:0'],
            'seuil_alerte' => ['required', 'integer', 'min:0'],
            'unite' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
            'description_courte' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        // Prix promo ne doit pas dépasser le prix de vente
        if (!empty($data['prix_promo']) && $data['prix_promo'] >= $data['prix_vente']) {
            return back()->withInput()->withErrors(['prix_promo' => 'Le prix promo doit être inférieur au prix de vente.']);
        }

        if (isset($data['prix_achat']) && $data['prix_vente'] < $data['prix_achat']) {
            return back()->withErrors(['prix_vente' => 'Le prix de vente ne peut pas être inférieur au prix d\'achat.'])->withInput();
        }

        $data['prix_achat'] = $data['prix_achat'] ?? 0;
        $data['visible_boutique'] = $request->boolean('visible_boutique', true);
        $data['featured'] = $request->boolean('featured', false);

        // Upload photo optionnelle
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('produits', 'public');
        }

        $produit = Produit::create($data);

        // Vider le cache de la caisse ET de la boutique
        Cache::forget('caisse_catalog_' . $produit->institut_id);
        Cache::forget('boutique_' . $produit->institut_id . '_produits');

        return redirect()->route('dashboard.produits.index')
            ->with('success', 'Produit ajouté.');
    }

    public function edit(Produit $produit)
    {
        $categories = CategorieProduit::orderBy('nom')->get();
        $fournisseurs = \App\Models\Fournisseur::where('actif', true)->orderBy('nom')->get(['id', 'nom']);
        $images = $produit->images()->orderBy('ordre')->get();
        return view('dashboard.produits.edit', compact('produit', 'categories', 'fournisseurs', 'images'));
    }

    public function update(Request $request, Produit $produit)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:150'],
            'categorie_id' => ['nullable', 'uuid'],
            'fournisseur_id' => ['nullable', 'uuid', 'exists:fournisseurs,id'],
            'reference' => ['nullable', 'string', 'max:50'],
            'code_barre' => ['nullable', 'string', 'max:50'],
            'prix_achat' => ['nullable', 'integer', 'min:0'],
            'prix_vente' => ['required', 'integer', 'min:0'],
            'prix_promo' => ['nullable', 'integer', 'min:0', 'lt:prix_vente'],
            'seuil_alerte' => ['required', 'integer', 'min:0'],
            'unite' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
            'description_courte' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if (!empty($data['prix_promo']) && $data['prix_promo'] >= $data['prix_vente']) {
            return back()->withInput()->withErrors(['prix_promo' => 'Le prix promo doit être inférieur au prix de vente.']);
        }

        if (isset($data['prix_achat']) && $data['prix_vente'] < $data['prix_achat']) {
            return back()->withErrors(['prix_vente' => 'Le prix de vente ne peut pas être inférieur au prix d\'achat.'])->withInput();
        }

        $data['prix_achat'] = $data['prix_achat'] ?? 0;
        $data['visible_boutique'] = $request->boolean('visible_boutique', true);
        $data['featured'] = $request->boolean('featured', false);

        // Upload photo optionnelle (remplace l'ancienne si existante)
        if ($request->hasFile('photo')) {
            if ($produit->photo && \Storage::disk('public')->exists($produit->photo)) {
                \Storage::disk('public')->delete($produit->photo);
            }
            $data['photo'] = $request->file('photo')->store('produits', 'public');
        } elseif ($request->input('supprimer_photo')) {
            if ($produit->photo && \Storage::disk('public')->exists($produit->photo)) {
                \Storage::disk('public')->delete($produit->photo);
            }
            $data['photo'] = null;
        }

        $produit->update($data);

        // Vider le cache de la caisse ET de la boutique
        Cache::forget('caisse_catalog_' . $produit->institut_id);
        Cache::forget('boutique_' . $produit->institut_id . '_produits');

        return redirect()->route('dashboard.produits.index')
            ->with('success', 'Produit mis à jour.');
    }

    public function destroy(Produit $produit)
    {
        $institutId = $produit->institut_id;
        $produit->delete();

        // Vider le cache de la caisse ET de la boutique
        Cache::forget('caisse_catalog_' . $institutId);
        Cache::forget('boutique_' . $institutId . '_produits');

        return redirect()->route('dashboard.produits.index')
            ->with('success', 'Produit supprimé.');
    }

    /**
     * Recherche d'un produit par code-barres (utilisé par le scan caméra de la caisse).
     */
    public function rechercheParCodeBarre(Request $request)
    {
        $code = trim((string) $request->query('code'));
        abort_if($code === '', 422, 'Code-barres manquant.');

        $institutId = session('current_institut_id', \Illuminate\Support\Facades\Auth::user()->institut_id);

        $produit = Produit::where('institut_id', $institutId)
            ->where('code_barre', $code)
            ->where('actif', true)
            ->first(['id', 'nom', 'prix_vente', 'stock', 'code_barre']);

        if (! $produit) {
            return response()->json(['found' => false], 404);
        }

        return response()->json([
            'found'      => true,
            'id'         => $produit->id,
            'nom'        => $produit->nom,
            'prix'       => (int) $produit->prix_vente,
            'stock'      => (int) $produit->stock,
            'code_barre' => $produit->code_barre,
        ]);
    }

    /**
     * Basculer la visibilité boutique d'un produit
     */
    public function toggleVisible(Produit $produit)
    {
        $newValue = !$produit->visible_boutique;
        $produit->update(['visible_boutique' => $newValue]);
        Cache::forget('boutique_' . $produit->institut_id . '_produits');
        Cache::forget('caisse_catalog_' . $produit->institut_id);
        return back()->with('success', $newValue
            ? "« {$produit->nom} » visible dans la boutique."
            : "« {$produit->nom} » masqué dans la boutique.");
    }

    /**
     * Basculer le statut "en vedette" d'un produit
     */
    public function toggleFeatured(Produit $produit)
    {
        $newValue = !$produit->featured;
        $produit->update(['featured' => $newValue]);
        Cache::forget('boutique_' . $produit->institut_id . '_produits');
        return back()->with('success', $newValue
            ? "« {$produit->nom} » mis en vedette."
            : "« {$produit->nom} » retiré des vedettes.");
    }
}
