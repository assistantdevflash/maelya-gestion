<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Institut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BoutiqueConfigController extends Controller
{
    /**
     * Afficher la page de configuration de la boutique
     */
    public function index()
    {
        $institut = Institut::findOrFail(session('institut_id'));

        return view('dashboard.boutique.config', compact('institut'));
    }

    /**
     * Mettre à jour la configuration de la boutique
     */
    public function update(Request $request)
    {
        $institut = Institut::findOrFail(session('institut_id'));

        $data = $request->validate([
            'boutique_active' => 'boolean',
            'boutique_frais_livraison' => 'nullable|numeric|min:0',
            'boutique_delai_livraison' => 'nullable|string|max:255',
            'boutique_conditions' => 'nullable|string|max:5000',
            'boutique_zones_livraison' => 'nullable|array',
        ]);

        // Si boutique_active n'est pas dans la requête, c'est false
        $data['boutique_active'] = $request->has('boutique_active');

        $institut->update($data);

        // Vider le cache des produits de la boutique
        Cache::forget("boutique_{$institut->id}_produits");

        return back()->with('success', 'Configuration de la boutique mise à jour avec succès.');
    }
}
