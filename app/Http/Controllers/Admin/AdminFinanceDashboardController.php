<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Institut;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFinanceDashboardController extends Controller
{
    public function index(Request $request)
    {
        $annee = (int) $request->get('annee', now()->year);
        $moisFiltre = $request->get('mois');

        $isSqlite = DB::getDriverName() === 'sqlite';
        $monthExpr = fn(string $col) => $isSqlite ? "CAST(strftime('%m', $col) AS INTEGER)" : "MONTH($col)";
        $yearExpr = fn(string $col) => $isSqlite ? "CAST(strftime('%Y', $col) AS INTEGER)" : "YEAR($col)";

        // ── CA plateforme (paiements en ligne des abonnements) ───────────────
        $transactionsQuery = PaymentTransaction::where('status', 'completed')
            ->whereYear('paid_at', $annee);
        if ($moisFiltre) {
            $transactionsQuery->whereMonth('paid_at', $moisFiltre);
        }

        $caPlateformeAnnee = (clone $transactionsQuery)->sum('net_amount');
        $caPlateformeMois  = (clone $transactionsQuery)->sum('net_amount');
        $nbTransactionsAnnee = (clone $transactionsQuery)->count();
        $totalFrais = (clone $transactionsQuery)->sum('fees');

        // CA plateforme par mois (graphique)
        $caPlateformeParMois = PaymentTransaction::where('status', 'completed')
            ->whereYear('paid_at', $annee)
            ->selectRaw($monthExpr('paid_at') . ' as mois, SUM(net_amount) as total, COUNT(*) as nb')
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois');

        // ── CA boutique en ligne (commandes payées) ──────────────────────────
        $commandesQuery = Commande::where('payee', true)
            ->whereYear('payee_at', $annee);
        if ($moisFiltre) {
            $commandesQuery->whereMonth('payee_at', $moisFiltre);
        }

        $caBoutiqueAnnee = (clone $commandesQuery)->sum('total');
        $caBoutiqueMois  = (clone $commandesQuery)->sum('total');
        $nbCommandesPayees = (clone $commandesQuery)->count();

        $caBoutiqueParMois = Commande::where('payee', true)
            ->whereYear('payee_at', $annee)
            ->selectRaw($monthExpr('payee_at') . ' as mois, SUM(total) as total, COUNT(*) as nb')
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois');

        // Séries 12 mois
        $moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        $plateformeData = [];
        $boutiqueData = [];
        for ($m = 1; $m <= 12; $m++) {
            $plateformeData[] = (int) ($caPlateformeParMois[$m] ?? 0);
            $boutiqueData[]   = (int) ($caBoutiqueParMois[$m] ?? 0);
        }

        // ── Taux de succès / échecs ─────────────────────────────────────────
        $statsStatuts = PaymentTransaction::whereYear('created_at', $annee)
            ->selectRaw('status, COUNT(*) as nb, COALESCE(SUM(amount), 0) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $nbTotal = $statsStatuts->sum('nb');
        $nbReussies = $statsStatuts['completed']['nb'] ?? 0;
        $tauxSucces = $nbTotal > 0 ? round($nbReussies / $nbTotal * 100, 1) : 0;

        // ── Répartition par type ────────────────────────────────────────────
        $parType = PaymentTransaction::where('status', 'completed')
            ->whereYear('paid_at', $annee)
            ->selectRaw('type, SUM(net_amount) as total, COUNT(*) as nb')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        // ── Répartition par méthode ─────────────────────────────────────────
        $parMethode = PaymentTransaction::where('status', 'completed')
            ->whereYear('paid_at', $annee)
            ->selectRaw('payment_method_code, SUM(net_amount) as total, COUNT(*) as nb')
            ->groupBy('payment_method_code')
            ->orderByDesc('total')
            ->get();

        // ── Remboursements ──────────────────────────────────────────────────
        $remboursements = PaymentTransaction::where('status', 'refunded')
            ->whereYear('created_at', $annee)
            ->selectRaw('SUM(refunded_amount) as total, COUNT(*) as nb')
            ->first();
        $montantRembourse = (int) ($remboursements->total ?? 0);
        $nbRemboursements = (int) ($remboursements->nb ?? 0);

        // ── Top instituts par CA en ligne ───────────────────────────────────
        $topInstitutsBoutique = Commande::where('payee', true)
            ->whereYear('payee_at', $annee)
            ->join('instituts', 'commandes.institut_id', '=', 'instituts.id')
            ->selectRaw('instituts.id, instituts.nom, instituts.ville, SUM(commandes.total) as ca_en_ligne, COUNT(*) as nb_commandes')
            ->groupBy('instituts.id', 'instituts.nom', 'instituts.ville')
            ->orderByDesc('ca_en_ligne')
            ->limit(10)
            ->get();

        // ── Transactions récentes ───────────────────────────────────────────
        $recentes = PaymentTransaction::with('user')
            ->latest()
            ->take(12)
            ->get();

        // ── Progression mensuelle ───────────────────────────────────────────
        $caTotalAnnee = array_sum($plateformeData) + array_sum($boutiqueData);
        $caTotalAnneePrecedente = 0;
        $caPlateformeAnneePrec = PaymentTransaction::where('status', 'completed')
            ->whereYear('paid_at', $annee - 1)
            ->sum('net_amount');
        $caBoutiqueAnneePrec = Commande::where('payee', true)
            ->whereYear('payee_at', $annee - 1)
            ->sum('total');
        $caTotalAnneePrecedente = (int) $caPlateformeAnneePrec + (int) $caBoutiqueAnneePrec;
        $progression = $caTotalAnneePrecedente > 0
            ? round(($caTotalAnnee - $caTotalAnneePrecedente) / $caTotalAnneePrecedente * 100, 1)
            : null;

        return view('admin.finance-dashboard.index', compact(
            'annee', 'moisFiltre',
            'caPlateformeAnnee', 'caPlateformeMois', 'nbTransactionsAnnee', 'totalFrais',
            'caBoutiqueAnnee', 'caBoutiqueMois', 'nbCommandesPayees',
            'moisLabels', 'plateformeData', 'boutiqueData',
            'nbTotal', 'nbReussies', 'tauxSucces', 'statsStatuts',
            'parType', 'parMethode',
            'montantRembourse', 'nbRemboursements',
            'topInstitutsBoutique', 'recentes',
            'caTotalAnnee', 'caTotalAnneePrecedente', 'progression',
        ));
    }
}
