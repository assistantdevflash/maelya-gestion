<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /** Libellés lisibles des types de mouvement (enum de la table). */
    public const TYPES = [
        'entree'           => 'Entrée',
        'sortie_vente'     => 'Sortie',
        'correction'       => 'Correction',
        'annulation_vente' => 'Annulation',
    ];

    public function index(Request $request)
    {
        $search = $request->input('q');
        $alerte = $request->boolean('alerte');

        $produits = Produit::with('categorie')
            ->when($search, fn($q) => $q->where('nom', 'like', "%{$search}%"))
            ->when($alerte, fn($q) => $q->whereColumn('stock', '<=', 'seuil_alerte'))
            ->where('actif', true)
            ->orderBy('nom')
            ->paginate(30)
            ->withQueryString();

        $nbAlertes = $alerte ? $produits->total() : Produit::where('actif', true)->whereColumn('stock', '<=', 'seuil_alerte')->count();

        $tousLesProduits = Produit::where('actif', true)->orderBy('nom')->limit(500)->get(['id', 'nom']);

        return view('dashboard.stock.index', compact('produits', 'nbAlertes', 'search', 'alerte', 'tousLesProduits'));
    }

    public function entree(Request $request, Produit $produit, StockService $stock)
    {
        $data = $request->validate([
            'quantite' => ['required', 'integer', 'min:1'],
            'prix_unitaire' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $prixUnit = isset($data['prix_unitaire']) ? (int) $data['prix_unitaire'] : null;

        $mouvement = $stock->entrer($produit, (int) $data['quantite'], [
            'prix_unitaire' => $prixUnit ?: null,
            'note'          => $data['note'] ?? 'Réapprovisionnement',
        ]);

        if (! $mouvement) {
            return back()->with('error', 'Aucune quantité à ajouter.');
        }

        return back()->with(
            'success',
            "Stock mis à jour : +{$data['quantite']} {$produit->unite} (nouveau stock : {$mouvement->stock_apres})."
        );
    }

    public function correction(Request $request, Produit $produit, StockService $stock)
    {
        $data = $request->validate([
            'stock_corrige' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $mouvement = $stock->ajuster($produit, (int) $data['stock_corrige'], [
            'note' => $data['note'] ?? 'Correction manuelle',
        ]);

        if (! $mouvement) {
            return back()->with('info', 'Le stock était déjà à cette valeur.');
        }

        return back()->with('success', 'Stock corrigé : ' . $mouvement->stock_apres . ' ' . $produit->unite . '.');
    }

    /**
     * Historique des mouvements de stock.
     *
     * Les mouvements étaient enregistrés depuis le début mais aucun écran ne
     * les affichait : impossible d'expliquer un écart ou de retrouver qui a
     * modifié quoi.
     */
    public function mouvements(Request $request)
    {
        $q     = trim((string) $request->input('q'));
        $type  = (string) $request->input('type');
        $du    = $request->input('du');
        $au    = $request->input('au');

        $base = MouvementStock::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('produit', function ($p) use ($q) {
                    $like = '%' . $q . '%';
                    $p->withoutGlobalScopes()
                      ->where(fn ($w) => $w->where('nom', 'like', $like)
                                           ->orWhere('reference', 'like', $like)
                                           ->orWhere('code_barre', 'like', $like));
                });
            })
            ->when(array_key_exists($type, self::TYPES), fn ($query) => $query->where('type', $type))
            ->when($du, fn ($query) => $query->whereDate('created_at', '>=', $du))
            ->when($au, fn ($query) => $query->whereDate('created_at', '<=', $au));

        // Totaux de la sélection (avant pagination)
        $totaux = (clone $base)
            ->selectRaw("SUM(CASE WHEN type IN ('entree','annulation_vente') THEN quantite ELSE 0 END) as entrees")
            ->selectRaw("SUM(CASE WHEN type IN ('sortie_vente','correction') THEN quantite ELSE 0 END) as sorties")
            ->first();

        $mouvements = $base
            ->with(['produit:id,nom,unite,reference', 'user:id,prenom,nom_famille'])
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $produits = Produit::orderBy('nom')->limit(500)->get(['id', 'nom']);

        return view('dashboard.stock.mouvements', [
            'mouvements' => $mouvements,
            'produits'   => $produits,
            'recherche'  => $q,
            'type'       => $type,
            'du'         => $du,
            'au'         => $au,
            'entrees'    => (int) ($totaux->entrees ?? 0),
            'sorties'    => (int) ($totaux->sorties ?? 0),
        ]);
    }
}
