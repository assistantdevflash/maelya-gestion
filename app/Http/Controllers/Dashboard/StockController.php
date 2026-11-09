<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
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
}
