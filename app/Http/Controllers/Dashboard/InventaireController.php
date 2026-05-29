<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Inventaire;
use App\Models\InventaireLigne;
use App\Models\MouvementStock;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventaireController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index()
    {
        $inventaires = Inventaire::with('user')->latest()->paginate(20);
        return view('dashboard.inventaires.index', compact('inventaires'));
    }

    public function create()
    {
        $produits = Produit::where('actif', true)
            ->orderBy('nom')
            ->get(['id', 'nom', 'unite', 'stock', 'cout_moyen_pondere', 'prix_achat']);
        return view('dashboard.inventaires.create', compact('produits'));
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

            $totalEcart = 0;
            foreach ($data['comptes'] as $produitId => $stockCompte) {
                $produit = Produit::find($produitId);
                if (!$produit) continue;

                $stockTheorique = (int) $produit->stock;
                $stockCompte    = (int) $stockCompte;
                $ecart          = $stockCompte - $stockTheorique;
                $cmp            = $produit->cout_moyen_pondere ?: $produit->prix_achat;
                $valeurEcart    = $ecart * $cmp;
                $totalEcart    += $valeurEcart;

                InventaireLigne::create([
                    'inventaire_id'   => $inv->id,
                    'produit_id'      => $produit->id,
                    'stock_theorique' => $stockTheorique,
                    'stock_compte'    => $stockCompte,
                    'ecart'           => $ecart,
                    'valeur_ecart'    => $valeurEcart,
                ]);
            }

            $inv->update(['total_ecart_valeur' => $totalEcart]);
            return $inv;
        });

        return redirect()->route('dashboard.inventaires.show', $inv)
            ->with('success', 'Inventaire enregistré (mode brouillon).');
    }

    public function show(Inventaire $inventaire)
    {
        $inventaire->load('lignes.produit', 'user');
        return view('dashboard.inventaires.show', compact('inventaire'));
    }

    /** Applique les écarts sur le stock + crée des mouvements de correction */
    public function valider(Inventaire $inventaire)
    {
        if ($inventaire->statut !== 'en_cours') {
            return back()->with('error', 'Cet inventaire est déjà validé ou annulé.');
        }

        DB::transaction(function () use ($inventaire) {
            foreach ($inventaire->lignes as $ligne) {
                if ($ligne->ecart === 0) continue;
                $produit = Produit::find($ligne->produit_id);
                if (!$produit) continue;

                $stockAvant = $produit->stock;
                $produit->update(['stock' => $ligne->stock_compte]);

                MouvementStock::create([
                    'institut_id' => $inventaire->institut_id,
                    'produit_id'  => $produit->id,
                    'user_id'     => Auth::id(),
                    'type'        => 'correction',
                    'quantite'    => abs($ligne->ecart),
                    'stock_avant' => $stockAvant,
                    'stock_apres' => $ligne->stock_compte,
                    'note'        => 'Inventaire ' . $inventaire->date_inventaire->format('d/m/Y'),
                ]);
            }
            $inventaire->update(['statut' => 'valide']);
        });

        return back()->with('success', 'Inventaire validé : stock ajusté.');
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
