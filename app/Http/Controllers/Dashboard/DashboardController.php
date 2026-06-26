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

        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        // ── Plan Basic : dashboard simplifié ─────────────────────────────────
        if (!$user->aFonctionnalite('dashboard_complet')) {
            $stats = Vente::where('statut', 'validee')
                ->selectRaw("
                    SUM(CASE WHEN DATE(created_at) = ? THEN total ELSE 0 END) as ca_jour,
                    COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as ventes_jour,
                    SUM(CASE WHEN DATE(created_at) >= ? AND DATE(created_at) <= ? THEN total ELSE 0 END) as ca_mois,
                    COUNT(CASE WHEN DATE(created_at) >= ? AND DATE(created_at) <= ? THEN 1 END) as ventes_mois
                ", [$today, $today, $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth])
                ->first();

            $caJour     = (int) ($stats->ca_jour ?? 0);
            $caMois     = (int) ($stats->ca_mois ?? 0);
            $ventesJour = (int) ($stats->ventes_jour ?? 0);
            $ventesMois = (int) ($stats->ventes_mois ?? 0);

            $abonnement = $user->abonnementActif;
            $joursRestants = $abonnement?->expire_le ? (int) now()->diffInDays($abonnement->expire_le, false) : null;

            return view('dashboard.index-basic', compact(
                'caJour', 'caMois', 'ventesJour', 'ventesMois', 'abonnement', 'joursRestants'
            ));
        }

        $yesterday = now()->subDay()->toDateString();
        $startOfPrevMonth = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $endOfPrevMonth   = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        // ── 1. Stats ventes fusionnées (12 requêtes → 1) ─────────────────────
        $statsVentes = Vente::where('statut', 'validee')
            ->selectRaw("
                -- Current period (crédit = apport réel uniquement)
                SUM(CASE WHEN DATE(created_at) = ? THEN CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END ELSE 0 END) as ca_jour,
                COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as ventes_jour,
                SUM(CASE WHEN DATE(created_at) >= ? AND DATE(created_at) <= ? THEN CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END ELSE 0 END) as ca_mois,
                COUNT(CASE WHEN DATE(created_at) >= ? AND DATE(created_at) <= ? THEN 1 END) as ventes_mois,
                -- Payments by mode (this month) — crédit = apport réel
                SUM(CASE WHEN mode_paiement = 'cash' AND DATE(created_at) >= ? AND DATE(created_at) <= ? THEN total ELSE 0 END) as paiements_cash,
                SUM(CASE WHEN mode_paiement = 'mobile_money' AND DATE(created_at) >= ? AND DATE(created_at) <= ? THEN total ELSE 0 END) as paiements_mobile,
                SUM(CASE WHEN mode_paiement = 'carte' AND DATE(created_at) >= ? AND DATE(created_at) <= ? THEN total ELSE 0 END) as paiements_carte,
                SUM(CASE WHEN mode_paiement = 'mixte' AND DATE(created_at) >= ? AND DATE(created_at) <= ? THEN total ELSE 0 END) as paiements_mixte,
                SUM(CASE WHEN mode_paiement = 'credit' AND DATE(created_at) >= ? AND DATE(created_at) <= ? THEN montant_paye ELSE 0 END) as paiements_credit,
                -- Previous day
                SUM(CASE WHEN DATE(created_at) = ? THEN CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END ELSE 0 END) as ca_jour_prec,
                COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as ventes_jour_prec,
                -- Previous month
                SUM(CASE WHEN DATE(created_at) >= ? AND DATE(created_at) <= ? THEN CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END ELSE 0 END) as ca_mois_prec,
                COUNT(CASE WHEN DATE(created_at) >= ? AND DATE(created_at) <= ? THEN 1 END) as ventes_mois_prec
            ", [
                $today, $today,
                $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth,
                $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth,
                $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth,
                $startOfMonth, $endOfMonth, // paiements_credit
                $yesterday, $yesterday,
                $startOfPrevMonth, $endOfPrevMonth, $startOfPrevMonth, $endOfPrevMonth
            ])->first();

        $caJour          = (int) ($statsVentes->ca_jour ?? 0);
        $ventesJour      = (int) ($statsVentes->ventes_jour ?? 0);
        $caMois          = (int) ($statsVentes->ca_mois ?? 0);
        $ventesMois      = (int) ($statsVentes->ventes_mois ?? 0);
        $paiementsCash   = (int) ($statsVentes->paiements_cash ?? 0);
        $paiementsMobile = (int) ($statsVentes->paiements_mobile ?? 0);
        $paiementsCarte  = (int) ($statsVentes->paiements_carte ?? 0);
        $paiementsMixte  = (int) ($statsVentes->paiements_mixte ?? 0);
        $paiementsCredit = (int) ($statsVentes->paiements_credit ?? 0);
        $caJourPrec      = (int) ($statsVentes->ca_jour_prec ?? 0);
        $ventesJourPrec  = (int) ($statsVentes->ventes_jour_prec ?? 0);
        $caMoisPrec      = (int) ($statsVentes->ca_mois_prec ?? 0);
        $ventesMoisPrec  = (int) ($statsVentes->ventes_mois_prec ?? 0);

        // ── 2. Stats clients fusionnées (3 requêtes → 1) ─────────────────────
        $statsClients = Client::where('actif', true)
            ->selectRaw("
                COUNT(*) as nb_clients,
                SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as nouveaux_jour,
                SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as nouveaux_jour_prec
            ", [$today, $yesterday])
            ->first();

        $nbClients             = (int) ($statsClients->nb_clients ?? 0);
        $totalClients          = $nbClients;
        $nouveauxClientsJour   = (int) ($statsClients->nouveaux_jour ?? 0);
        $nouveauxClientsJourPrec = (int) ($statsClients->nouveaux_jour_prec ?? 0);

        // ── 3. Produits en alerte ────────────────────────────────────────────
        $produitsEnAlerte = Produit::where('actif', true)
            ->whereColumn('stock', '<=', 'seuil_alerte')
            ->count();

        // ── 4. Dépenses du mois ──────────────────────────────────────────────
        $depensesMois = Depense::whereDate('date', '>=', $startOfMonth)
            ->whereDate('date', '<=', $endOfMonth)
            ->sum('montant');

        $beneficeEstime = $caMois - $depensesMois;
        $beneficeMois = $beneficeEstime;

        // ── 5. Graphique 30 derniers jours ───────────────────────────────────
        $ventesParJour = Vente::where('statut', 'validee')
            ->whereDate('created_at', '>=', now()->subDays(29)->toDateString())
            ->selectRaw("DATE(created_at) as date, SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('d/m');
            $data[] = $ventesParJour[$date] ?? 0;
        }

        // ── 6. Dernières ventes ──────────────────────────────────────────────
        $dernieresVentes = Vente::with('client')
            ->where('statut', 'validee')
            ->latest()
            ->limit(5)
            ->get();

        // ── 7. Produits en alerte (liste) ────────────────────────────────────
        $alertesStock = Produit::where('actif', true)
            ->whereColumn('stock', '<=', 'seuil_alerte')
            ->limit(5)
            ->get();

        // ── 8. Abonnement ────────────────────────────────────────────────────
        $abonnement = $user->abonnementActif;
        $joursRestants = $abonnement?->expire_le ? (int) now()->diffInDays($abonnement->expire_le, false) : null;

        // ── 9. Évolutions ────────────────────────────────────────────────────
        $pct = function ($actuel, $prec) {
            if ($prec <= 0) {
                return $actuel > 0 ? 100 : 0;
            }
            return round((($actuel - $prec) / $prec) * 100, 1);
        };
        $evolutionCaJour       = $pct($caJour, $caJourPrec);
        $evolutionCaMois       = $pct($caMois, $caMoisPrec);
        $evolutionCa           = $evolutionCaMois;
        $evolutionVentesJour   = $pct($ventesJour, $ventesJourPrec);
        $evolutionVentesMois   = $pct($ventesMois, $ventesMoisPrec);
        $evolutionClientsJour  = $pct($nouveauxClientsJour, $nouveauxClientsJourPrec);

        // ── 10. Anniversaires du jour sans cadeau déjà créé ──────────────────
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

        $chartData = ['labels' => $labels, 'values' => $data];

        return view('dashboard.index', compact(
            'caJour', 'caMois', 'nbClients', 'totalClients', 'ventesJour', 'ventesMois',
            'nouveauxClientsJour', 'produitsEnAlerte', 'depensesMois', 'beneficeEstime', 'beneficeMois',
            'paiementsCash', 'paiementsMobile', 'paiementsCarte', 'paiementsMixte', 'paiementsCredit',
            'labels', 'data', 'chartData', 'dernieresVentes', 'alertesStock',
            'abonnement', 'joursRestants', 'anniversairesAujourdhui',
            'caJourPrec', 'caMoisPrec', 'ventesJourPrec', 'ventesMoisPrec',
            'evolutionCa', 'evolutionCaJour', 'evolutionCaMois',
            'evolutionVentesJour', 'evolutionVentesMois', 'evolutionClientsJour'
        ));
    }

    public function faq()
    {
        return view('dashboard.faq');
    }

    public function faqPdf()
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('dashboard.faq-pdf')
            ->setPaper('a4', 'portrait');
        return $pdf->download('documentation-maelyagestion.pdf');
    }
}
