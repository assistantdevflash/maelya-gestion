<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CategorieProduit;
use Illuminate\Http\Request;

class CategorieProduitController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard.produits.index');
    }

    public function store(Request $request)
    {
        $request->validate(['nom' => ['required', 'string', 'max:100']]);
        CategorieProduit::create(['nom' => $request->nom]);
        return back()->with('success', 'Catégorie produit créée.');
    }

    public function update(Request $request, CategorieProduit $categoriesProduit)
    {
        $request->validate(['nom' => ['required', 'string', 'max:100']]);
        $categoriesProduit->update(['nom' => $request->nom]);
        return back()->with('success', 'Catégorie modifiée.');
    }

    public function destroy(CategorieProduit $categoriesProduit)
    {
        if ($categoriesProduit->produits()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer : des produits utilisent cette catégorie.');
        }
        $categoriesProduit->delete();
        return back()->with('success', 'Catégorie supprimée.');
    }
}
