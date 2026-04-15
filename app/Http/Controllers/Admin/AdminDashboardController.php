<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\MessageContact;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalInstituts = Institut::count();
        $abonnementsActifs = Abonnement::where('statut', 'actif')
            ->where('expire_le', '>=', now()->toDateString())
            ->count();
        $abonnementsEnAttente = Abonnement::where('statut', 'en_attente')->count();
        $abonnementsExpires = Abonnement::where('statut', 'actif')
            ->where('expire_le', '<', now()->toDateString())
            ->count();
        $revenusMois = Abonnement::where('statut', 'actif')
            ->whereMonth('debut_le', now()->month)
            ->whereYear('debut_le', now()->year)
            ->sum('montant');
        $revenusTotal = Abonnement::where('statut', 'actif')->sum('montant');
        $nouveauxInscrits = User::where('role', 'admin')
            ->whereDate('created_at', '>=', now()->subDays(30)->toDateString())
            ->count();
        $messagesNonLus = MessageContact::where('lu', false)->count();

        // Dernières demandes en attente
        $demandesEnAttente = Abonnement::with(['user.institut', 'plan'])
            ->where('statut', 'en_attente')
            ->latest()
            ->limit(5)
            ->get();

        // Inscriptions 30 derniers jours
        $inscriptionsParJour = User::where('role', 'admin')
            ->whereDate('created_at', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as nb')
            ->groupBy('date')
            ->pluck('nb', 'date');

        $labelsInscriptions = [];
        $dataInscriptions = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labelsInscriptions[] = now()->subDays($i)->format('d/m');
            $dataInscriptions[] = $inscriptionsParJour[$date] ?? 0;
        }

        $derniersInstituts = Institut::with('users')->latest()->limit(10)->get();

        $chartData = ['labels' => $labelsInscriptions, 'values' => $dataInscriptions];

        return view('admin.dashboard', compact(
            'totalInstituts', 'abonnementsActifs', 'abonnementsEnAttente', 'abonnementsExpires',
            'revenusMois', 'revenusTotal', 'nouveauxInscrits', 'messagesNonLus',
            'demandesEnAttente', 'derniersInstituts', 'chartData'
        ));
    }
}
