<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Inventaire;
use App\Models\InventaireLigne;
use App\Models\Produit;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventaireController extends Controller
{
    public function index()
    {
        $inventaires = Inventaire::with('user')->latest()->paginate(20);
        return view('dashboard.inventaires.index', compact('inventaires'));
    }

    public function create()
    {
        $produits = $this->produitsACcompter();

        return view('dashboard.inventaires.create', compact('produits'));
    }

    /** Produits actifs proposés au comptage. */
    private function produitsACcompter()
    {
        return Produit::where('actif', true)
            ->orderBy('nom')
            ->get(['id', 'nom', 'unite', 'stock', 'seuil_alerte', 'reference',
                   'code_barre', 'cout_moyen_pondere', 'prix_achat', 'categorie_id']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date_inventaire' => ['required', 'date'],
            'notes'           => ['nullable', 'string'],
            'comptes'         => ['required', 'array'],
            'comptes.*'       => ['required', 'integer', 'min:0'],
        ]);

        $inv = DB::transaction(function () use ($data) {
            $inv = Inventaire::create([
                'user_id'            => Auth::id(),
                'date_inventaire'    => $data['date_inventaire'],
                'statut'             => 'en_cours',
                'notes'              => $data['notes'] ?? null,
                'total_ecart_valeur' => 0,
            ]);

            $this->enregistrerLignes($inv, $data['comptes']);

            return $inv;
        });

        return redirect()->route('dashboard.inventaires.show', $inv)
            ->with('success', 'Inventaire enregistré (mode brouillon).');
    }

    public function show(Inventaire $inventaire)
    {
        $inventaire->load('lignes.produit', 'user');

        // Stock réel actuel : permet d'avertir si le stock a bougé depuis le
        // comptage, avant d'écraser quoi que ce soit.
        $stocksActuels = Produit::withoutGlobalScopes()
            ->whereIn('id', $inventaire->lignes->pluck('produit_id'))
            ->pluck('stock', 'id');

        $lignesModifiees = $inventaire->lignes->filter(
            fn ($l) => $inventaire->statut === 'en_cours'
                && (int) ($stocksActuels[$l->produit_id] ?? $l->stock_theorique) !== (int) $l->stock_theorique
        );

        return view('dashboard.inventaires.show', [
            'inventaire'      => $inventaire,
            'stocksActuels'   => $stocksActuels,
            'lignesModifiees' => $lignesModifiees,
        ]);
    }

    /** Un brouillon doit pouvoir être corrigé sans être supprimé. */
    public function edit(Inventaire $inventaire)
    {
        abort_unless($inventaire->statut === 'en_cours', 403, 'Un inventaire validé ne peut plus être modifié.');

        $produits = $this->produitsACcompter();
        $comptes  = $inventaire->lignes->pluck('stock_compte', 'produit_id');

        return view('dashboard.inventaires.edit', compact('inventaire', 'produits', 'comptes'));
    }

    public function update(Request $request, Inventaire $inventaire)
    {
        abort_unless($inventaire->statut === 'en_cours', 403, 'Un inventaire validé ne peut plus être modifié.');

        $data = $request->validate([
            'date_inventaire' => ['required', 'date'],
            'notes'           => ['nullable', 'string'],
            'comptes'         => ['required', 'array'],
            'comptes.*'       => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($inventaire, $data) {
            $inventaire->update([
                'date_inventaire' => $data['date_inventaire'],
                'notes'           => $data['notes'] ?? null,
            ]);

            $inventaire->lignes()->delete();
            $this->enregistrerLignes($inventaire, $data['comptes']);
        });

        return redirect()->route('dashboard.inventaires.show', $inventaire)
            ->with('success', 'Inventaire mis à jour.');
    }

    /**
     * Applique les écarts au stock réel.
     *
     * Si des produits ont bougé depuis le comptage, une confirmation explicite
     * est exigée : sinon une vente survenue entre-temps serait silencieusement
     * annulée par l'écrasement du stock.
     */
    public function valider(Request $request, Inventaire $inventaire)
    {
        if ($inventaire->statut !== 'en_cours') {
            return back()->with('error', 'Cet inventaire est déjà validé ou annulé.');
        }

        $inventaire->load('lignes');

        $stocksActuels = Produit::withoutGlobalScopes()
            ->whereIn('id', $inventaire->lignes->pluck('produit_id'))
            ->pluck('stock', 'id');

        $modifiees = $inventaire->lignes->filter(
            fn ($l) => (int) ($stocksActuels[$l->produit_id] ?? $l->stock_theorique) !== (int) $l->stock_theorique
        );

        if ($modifiees->isNotEmpty() && ! $request->boolean('confirmer_mouvements')) {
            return redirect()
                ->route('dashboard.inventaires.show', $inventaire)
                ->with('error', \App\Helpers\TexteHelper::pluriel($modifiees->count(), 'produit a', 'produits ont')
                    . ' bougé depuis le comptage. Relisez les écarts ou confirmez la validation.');
        }

        DB::transaction(function () use ($inventaire) {
            $stock = app(StockService::class);

            foreach ($inventaire->lignes as $ligne) {
                $produit = Produit::withoutGlobalScopes()->find($ligne->produit_id);
                if (! $produit) {
                    continue;
                }

                // Le théorique de référence devient le stock réel au moment de
                // la validation.
                $theoriqueReel = (int) $produit->stock;
                $ecartReel     = (int) $ligne->stock_compte - $theoriqueReel;

                $ligne->update([
                    'stock_theorique' => $theoriqueReel,
                    'ecart'           => $ecartReel,
                    'valeur_ecart'    => $ecartReel * (int) ($produit->cout_moyen_pondere ?: $produit->prix_achat),
                ]);

                if ($ecartReel === 0) {
                    continue;
                }

                $stock->ajuster($produit, (int) $ligne->stock_compte, [
                    'note' => 'Inventaire du ' . $inventaire->date_inventaire->format('d/m/Y'),
                ]);
            }

            $inventaire->update([
                'statut'             => 'valide',
                'total_ecart_valeur' => $inventaire->lignes()->sum('valeur_ecart'),
            ]);
        });

        return redirect()->route('dashboard.inventaires.show', $inventaire)
            ->with('success', 'Inventaire validé : le stock a été aligné sur les quantités comptées.');
    }

    /**
     * Crée les lignes d'un inventaire à partir des quantités comptées.
     *
     * @param  array<string, int|string>  $comptes
     */
    private function enregistrerLignes(Inventaire $inventaire, array $comptes): void
    {
        $produits = Produit::whereIn('id', array_keys($comptes))
            ->get(['id', 'stock', 'cout_moyen_pondere', 'prix_achat']);

        $totalEcart = 0;

        foreach ($produits as $produit) {
            $stockTheorique = (int) $produit->stock;
            $stockCompte    = (int) $comptes[$produit->id];
            $ecart          = $stockCompte - $stockTheorique;
            $cmp            = (int) ($produit->cout_moyen_pondere ?: $produit->prix_achat);
            $valeurEcart    = $ecart * $cmp;

            $totalEcart += $valeurEcart;

            InventaireLigne::create([
                'inventaire_id'   => $inventaire->id,
                'produit_id'      => $produit->id,
                'stock_theorique' => $stockTheorique,
                'stock_compte'    => $stockCompte,
                'ecart'           => $ecart,
                'valeur_ecart'    => $valeurEcart,
            ]);
        }

        $inventaire->update(['total_ecart_valeur' => $totalEcart]);
    }

    public function destroy(Inventaire $inventaire)
    {
        if ($inventaire->statut === 'valide') {
            return back()->with('error', 'Impossible de supprimer un inventaire validé.');
        }
        $inventaire->delete();
        return redirect()->route('dashboard.inventaires.index')->with('success', 'Inventaire supprimé.');
    }
}
