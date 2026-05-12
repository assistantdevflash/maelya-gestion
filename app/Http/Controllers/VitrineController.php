<?php

namespace App\Http\Controllers;

use App\Models\Institut;
use Illuminate\Http\Request;

class VitrineController extends Controller
{
    public function show(string $slug)
    {
        $institut = Institut::where('slug', $slug)
            ->where('vitrine_active', true)
            ->where('actif', true)
            ->firstOrFail();

        $prestations = $institut->prestations()
            ->where('actif', true)
            ->with('categorie')
            ->orderBy('nom')
            ->get()
            ->groupBy(fn($p) => $p->categorie?->nom ?? 'Autres');

        $produits = $institut->produits()
            ->where('actif', true)
            ->with('categorie')
            ->orderBy('nom')
            ->get()
            ->groupBy(fn($p) => $p->categorie?->nom ?? 'Autres');

        return view('vitrine.show', compact('institut', 'prestations', 'produits'));
    }
}
