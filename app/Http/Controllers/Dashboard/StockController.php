<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MouvementStock;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }
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

        $nbAlertes = Produit::where('actif', true)->whereColumn('stock', '<=', 'seuil_alerte')->count();

        $tousLesProduits = Produit::where('actif', true)->orderBy('nom')->get(['id', 'nom']);

        return view('dashboard.stock.index', compact('produits', 'nbAlertes', 'search', 'alerte', 'tousLesProduits'));
    }

    public function entree(Request $request, Produit $produit)
    {
        $data = $request->validate([
            'quantite' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        DB::transaction(function () use ($produit, $data) {
            $stockAvant = $produit->stock;
            $produit->increment('stock', $data['quantite']);

            MouvementStock::create([
                'institut_id' => $this->institutId(),
                'produit_id' => $produit->id,
                'user_id' => Auth::id(),
                'type' => 'entree',
                'quantite' => $data['quantite'],
                'stock_avant' => $stockAvant,
                'stock_apres' => $stockAvant + $data['quantite'],
                'note' => $data['note'] ?? 'Réapprovisionnement',
            ]);
        });

        return back()->with('success', "Stock mis à jour : +{$data['quantite']} {$produit->unite}.");
    }

    public function correction(Request $request, Produit $produit)
    {
        $data = $request->validate([
            'stock_corrige' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        DB::transaction(function () use ($produit, $data) {
            $stockAvant = $produit->stock;
            $diff = $data['stock_corrige'] - $stockAvant;
            $produit->update(['stock' => $data['stock_corrige']]);

            MouvementStock::create([
                'institut_id' => $this->institutId(),
                'produit_id' => $produit->id,
                'user_id' => Auth::id(),
                'type' => 'correction',
                'quantite' => abs($diff),
                'stock_avant' => $stockAvant,
                'stock_apres' => $data['stock_corrige'],
                'note' => $data['note'] ?? 'Correction d\'inventaire',
            ]);
        });

        return back()->with('success', 'Stock corrigé avec succès.');
    }
}
