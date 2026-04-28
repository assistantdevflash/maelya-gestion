<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Depense;
use App\Models\Produit;
use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->isEmploye()) {
            return redirect()->route('dashboard.caisse');
        }

        $user = Auth::user();
        $institutId = session('current_institut_id', $user->institut_id);

        // ── Plan Basic : dashboard simplifié ─────────────────────────────────
        if (!$user->aFonctionnalite('dashboard_complet')) {
            $today = now()->toDateString();
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth = now()->endOfMonth()->toDateString();

            $caJour = Vente::where('statut', 'validee')->whereDate('created_at', $today)->sum('total');
            $caMois = Vente::where('statut', 'validee')
                ->whereDate('created_at', '>=', $startOfMonth)
                ->whereDate('created_at', '<=', $endOfMonth)->sum('total');
            $ventesJour = Vente::where('statut', 'validee')->whereDate('created_at', $today)->count();
            $ventesMois = Vente::where('statut', 'validee')
                ->whereDate('created_at', '>=', $startOfMonth)
                ->whereDate('created_at', '<=', $endOfMonth)->count();

            $abonnement = $user->abonnementActif;
            $joursRestants = $abonnement?->expire_le ? (int) now()->diffInDays($abonnement->expire_le, false) : null;

            return view('dashboard.index-basic', compact(
                'caJour', 'caMois', 'ventesJour', 'ventesMois', 'abonnement', 'joursRestants'
            ));
        }

        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        // KPIs
        $caJour = Vente::where('statut', 'validee')
            ->whereDate('created_at', $today)
            ->sum('total');

        $caMois = Vente::where('statut', 'validee')
            ->whereDate('created_at', '>=', $startOfMonth)
            ->whereDate('created_at', '<=', $endOfMonth)
            ->sum('total');

        $nbClients = Client::where('actif', true)->count();
        $totalClients = $nbClients;

        $ventesJour = Vente::where('statut', 'validee')
            ->whereDate('created_at', $today)
            ->count();

        $ventesMois = Vente::where('statut', 'validee')
            ->whereDate('created_at', '>=', $startOfMonth)
            ->whereDate('created_at', '<=', $endOfMonth)
            ->count();

        $nouveauxClientsJour = Client::whereDate('created_at', $today)->count();

        $produitsEnAlerte = Produit::where('actif', true)
            ->whereColumn('stock', '<=', 'seuil_alerte')
            ->count();

        $depensesMois = Depense::whereDate('date', '>=', $startOfMonth)
            ->whereDate('date', '<=', $endOfMonth)
            ->sum('montant');

        $beneficeEstime = $caMois - $depensesMois;
        $beneficeMois = $beneficeEstime;

        // Paiements par mode ce mois
        $paiementsCash = Vente::where('statut', 'validee')
            ->where('mode_paiement', 'cash')
            ->whereDate('created_at', '>=', $startOfMonth)
            ->whereDate('created_at', '<=', $endOfMonth)
            ->sum('total');

        $paiementsMobile = Vente::where('statut', 'validee')
            ->where('mode_paiement', 'mobile_money')
            ->whereDate('created_at', '>=', $startOfMonth)
            ->whereDate('created_at', '<=', $endOfMonth)
            ->sum('total');

        $paiementsCarte = Vente::where('statut', 'validee')
            ->where('mode_paiement', 'carte')
            ->whereDate('created_at', '>=', $startOfMonth)
            ->whereDate('created_at', '<=', $endOfMonth)
            ->sum('total');

        $paiementsMixte = Vente::where('statut', 'validee')
            ->where('mode_paiement', 'mixte')
            ->whereDate('created_at', '>=', $startOfMonth)
            ->whereDate('created_at', '<=', $endOfMonth)
            ->sum('total');

        // Graphique 30 derniers jours
        $ventesParJour = Vente::where('statut', 'validee')
            ->whereDate('created_at', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('DATE(created_at) as date, SUM(total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        // Remplir les jours manquants
        $labels = [];
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('d/m');
            $data[] = $ventesParJour[$date] ?? 0;
        }

        // Dernières ventes
        $dernieresVentes = Vente::with('client')
            ->where('statut', 'validee')
            ->latest()
            ->limit(5)
            ->get();

        // Produits en alerte
        $alertesStock = Produit::where('actif', true)
            ->whereColumn('stock', '<=', 'seuil_alerte')
            ->limit(5)
            ->get();

        $abonnement = $user->abonnementActif;
        $joursRestants = $abonnement?->expire_le ? (int) now()->diffInDays($abonnement->expire_le, false) : null;

        // Clients fêtant leur anniversaire aujourd'hui sans cadeau déjà créé
        $cadeauClientIds = \App\Models\CodeReduction::withoutGlobalScopes()
            ->where('institut_id', $institutId)
            ->where('code', 'like', 'ANNIV-%')
            ->whereDate('date_debut', now()->toDateString())
            ->pluck('client_id')
            ->toArray();

        $anniversairesAujourdhui = \App\Models\Client::where('actif', true)
            ->where('date_naissance', now()->format('m-d'))
            ->whereNotIn('id', $cadeauClientIds)
            ->get();

        // Données graphique
        $chartData = ['labels' => $labels, 'values' => $data];

        return view('dashboard.index', compact(
            'caJour', 'caMois', 'nbClients', 'totalClients', 'ventesJour', 'ventesMois',
            'nouveauxClientsJour', 'produitsEnAlerte', 'depensesMois', 'beneficeEstime', 'beneficeMois',
            'paiementsCash', 'paiementsMobile', 'paiementsCarte', 'paiementsMixte',
            'labels', 'data', 'chartData', 'dernieresVentes', 'alertesStock',
            'abonnement', 'joursRestants', 'anniversairesAujourdhui'
        ));
    }
}
