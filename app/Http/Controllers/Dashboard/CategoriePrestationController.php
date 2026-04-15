<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CategoriePrestation;
use Illuminate\Http\Request;

class CategoriePrestationController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard.prestations.index');
    }

    public function store(Request $request)
    {
        $request->validate(['nom' => ['required', 'string', 'max:100']]);
        CategoriePrestation::create(['nom' => $request->nom, 'ordre' => 0]);
        return back()->with('success', 'Catégorie créée.');
    }

    public function update(Request $request, CategoriePrestation $categoriesPrestation)
    {
        $request->validate(['nom' => ['required', 'string', 'max:100']]);
        $categoriesPrestation->update(['nom' => $request->nom]);
        return back()->with('success', 'Catégorie renommée.');
    }

    public function destroy(CategoriePrestation $categoriesPrestation)
    {
        if ($categoriesPrestation->prestations()->count() > 0) {
            return redirect()->route('categories-prestations.index')->with('error', 'Impossible de supprimer : des prestations utilisent cette catégorie.');
        }
        $categoriesPrestation->prestations()->onlyTrashed()->forceDelete();
        $categoriesPrestation->delete();
        return redirect()->route('categories-prestations.index')->with('success', 'Catégorie supprimée.');
    }
}
