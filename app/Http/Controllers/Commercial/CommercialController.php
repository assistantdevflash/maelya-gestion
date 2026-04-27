<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Models\CommercialCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommercialController extends Controller
{
    private function profil()
    {
        return Auth::user()->commercialProfile;
    }

    public function dashboard()
    {
        $profil = $this->profil();

        if (!$profil) {
            abort(403, 'Profil commercial introuvable.');
        }

        $profil->loadCount(['parrainages', 'commissions']);

        $totalGagne   = $profil->totalGagne();
        $totalEnAttente = $profil->totalEnAttente();

        $derniersParrainages = $profil->parrainages()
            ->with('proprietaire.institut')
            ->latest()
            ->limit(5)
            ->get();

        $dernieresCommissions = $profil->commissions()
            ->with('parrainage.proprietaire.institut', 'abonnement.plan')
            ->latest()
            ->limit(5)
            ->get();

        $config = \DB::table('commercial_config')->first();

        return view('commercial.dashboard', compact(
            'profil', 'totalGagne', 'totalEnAttente',
            'derniersParrainages', 'dernieresCommissions', 'config'
        ));
    }

    public function parrainages()
    {
        $profil = $this->profil();
        if (!$profil) abort(403);

        $parrainages = $profil->parrainages()
            ->with('proprietaire.institut', 'commissions')
            ->latest()
            ->paginate(20);

        return view('commercial.parrainages', compact('profil', 'parrainages'));
    }

    public function commissions(Request $request)
    {
        $profil = $this->profil();
        if (!$profil) abort(403);

        $query = $profil->commissions()
            ->with('parrainage.proprietaire.institut', 'abonnement.plan')
            ->latest();

        if ($request->statut && in_array($request->statut, ['en_attente', 'payee'])) {
            $query->where('statut', $request->statut);
        }

        $commissions = $query->paginate(30)->withQueryString();

        $totalGagne     = $profil->totalGagne();
        $totalEnAttente = $profil->totalEnAttente();

        return view('commercial.commissions', compact(
            'profil', 'commissions', 'totalGagne', 'totalEnAttente'
        ));
    }
}
