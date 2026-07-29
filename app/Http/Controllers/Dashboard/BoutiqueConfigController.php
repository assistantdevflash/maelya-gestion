<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\FacebookEventsLog;
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
        $institut = Institut::findOrFail(session('current_institut_id', auth()->user()->institut_id));

        // Vérifier si une demande d'ajout d'option boutique est en attente
        $demandeEnAttente = \App\Models\Abonnement::where('user_id', auth()->id())
            ->where('statut', 'en_attente')
            ->whereJsonContains('metadata->type', 'ajout_option_boutique')
            ->first();

        return view('dashboard.boutique.config', compact('institut', 'demandeEnAttente'));
    }

    /**
     * Mettre à jour la configuration de la boutique
     */
    public function update(Request $request)
    {
        $institut = Institut::findOrFail(session('current_institut_id', auth()->user()->institut_id));

        $data = $request->validate([
            'boutique_active' => 'boolean',
            'boutique_frais_livraison' => 'nullable|numeric|min:0',
            'boutique_delai_livraison' => 'nullable|string|max:255',
            'boutique_conditions' => 'nullable|string|max:5000',
            'boutique_zones_livraison' => 'nullable|array',
            'boutique_zones_livraison.*.nom' => 'required|string|max:100',
            'boutique_zones_livraison.*.frais' => 'required|integer|min:0',
            'boutique_zones_livraison.*.delai' => 'nullable|string|max:50',
        ]);

        // Si boutique_active n'est pas dans la requête, c'est false
        $data['boutique_active'] = $request->has('boutique_active');

        $institut->update($data);

        // Vider le cache des produits de la boutique
        Cache::forget("boutique_{$institut->id}_produits");

        return back()->with('success', 'Configuration de la boutique mise à jour avec succès.');
    }

    /**
     * Vider le cache de la boutique (produits)
     */
    public function viderCache()
    {
        $institutId = session('current_institut_id', auth()->user()->institut_id);
        Cache::forget("boutique_{$institutId}_produits");

        return back()->with('success', 'Cache de la boutique vidé avec succès. Les produits seront rechargés à la prochaine visite.');
    }

    // ─── Marketing ──────────────────────────────────────────────────────────

    /**
     * Afficher la page de configuration marketing
     */
    public function marketing()
    {
        $institut = Institut::findOrFail(session('current_institut_id', auth()->user()->institut_id));

        $stats = FacebookEventsLog::where('institut_id', $institut->id)
            ->whereDate('created_at', today())
            ->selectRaw('event_name, count(*) as total')
            ->groupBy('event_name')
            ->pluck('total', 'event_name');

        $caAujourdhui = Commande::where('institut_id', $institut->id)
            ->whereDate('created_at', today())
            ->sum('total');

        return view('dashboard.boutique.marketing', compact('institut', 'stats', 'caAujourdhui'));
    }

    /**
     * Enregistrer la configuration Facebook
     */
    public function saveFacebook(Request $request)
    {
        $institut = Institut::findOrFail(session('current_institut_id', auth()->user()->institut_id));

        $data = $request->validate([
            'facebook_pixel_id'     => 'required|string|max:255',
            'facebook_access_token' => 'required|string|max:500',
            'facebook_test_code'    => 'nullable|string|max:100',
            'facebook_pixel_name'   => 'nullable|string|max:255',
        ]);

        $institut->update([
            'facebook_pixel_id'      => $data['facebook_pixel_id'],
            'facebook_access_token'  => $data['facebook_access_token'],
            'facebook_test_code'     => $data['facebook_test_code'] ?? null,
            'facebook_pixel_name'    => $data['facebook_pixel_name'] ?? null,
            'facebook_connected_at'  => $institut->facebook_connected_at ?? now(),
        ]);

        return back()->with('success', 'Pixel Facebook connecté avec succès.');
    }

    /**
     * Déconnecter Facebook
     */
    public function disconnectFacebook()
    {
        $institut = Institut::findOrFail(session('current_institut_id', auth()->user()->institut_id));

        $institut->update([
            'facebook_pixel_id'      => null,
            'facebook_access_token'  => null,
            'facebook_test_code'     => null,
            'facebook_pixel_name'    => null,
            'facebook_connected_at'  => null,
        ]);

        return back()->with('success', 'Pixel Facebook déconnecté.');
    }
}
