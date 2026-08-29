<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Commande;
use App\Models\Depense;
use App\Models\Institut;
use App\Models\PaymentTransaction;
use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFinanceDashboardController extends Controller
{
    public function index(Request $request)
    {
        $annee = (int) $request->get('annee', now()->year);
        $moisFiltre = $request->get('mois'); // null = tous

        $isSqlite = DB::getDriverName() === 'sqlite';
        $monthExpr = fn(string $col) => $isSqlite ? "CAST(strftime('%m', $col) AS INTEGER)" : "MONTH($col)";
        $yearExpr = fn(string $col) => $isSqlite ? "CAST(strftime('%Y', $col) AS INTEGER)" : "YEAR($col)";

        $moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

        // ═══════════════════════════════════════════════════════════════════
        // PARTIE 1 — REVENUS ABONNEMENTS + INSTITUTS (ex « Point financier »)
        // ═══════════════════════════════════════════════════════════════════
        $abonnementsQuery = Abonnement::where('statut', 'actif')
            ->whereYear('debut_le', $annee);
        if ($moisFiltre) $abonnementsQuery->whereMonth('debut_le', $moisFiltre);

        $revenuTotal = (clone $abonnementsQuery)->sum('montant');
        $nbAbonnements = (clone $abonnementsQuery)->count();

        $revenusParMois = Abonnement::where('statut', 'actif')
            ->whereYear('debut_le', $annee)
            ->selectRaw($monthExpr('debut_le') . ' as mois, SUM(montant) as total, COUNT(*) as nb')
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois');

        $nbParMois = Abonnement::where('statut', 'actif')
            ->whereYear('debut_le', $annee)
            ->selectRaw($monthExpr('debut_le') . ' as mois, COUNT(*) as nb')
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('nb', 'mois');

        $revenusData = [];
        $nbData = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenusData[] = (int) ($revenusParMois[$m] ?? 0);
            $nbData[] = (int) ($nbParMois[$m] ?? 0);
        }

        $moisMax = $revenusParMois->isNotEmpty() ? $revenusParMois->sortDesc()->keys()->first() : null;
        $moisMin = $revenusParMois->filter(fn($v) => $v > 0)->isNotEmpty()
            ? $revenusParMois->filter(fn($v) => $v > 0)->sort()->keys()->first()
            : null;

        // Répartition par plan / période
        $revenusParPlan = Abonnement::where('statut', 'actif')
            ->whereYear('debut_le', $annee)
            ->join('plans_abonnement', 'abonnements.plan_id', '=', 'plans_abonnement.id')
            ->selectRaw('plans_abonnement.nom as plan, SUM(abonnements.montant) as total, COUNT(*) as nb')
            ->groupBy('plans_abonnement.nom')
            ->orderByDesc('total')
            ->get();

        $revenusParPeriode = Abonnement::where('statut', 'actif')
            ->whereYear('debut_le', $annee)
            ->selectRaw('periode, SUM(montant) as total, COUNT(*) as nb')
            ->groupBy('periode')
            ->orderByDesc('total')
            ->get();

        // KPIs globaux
        $revenuMoisCourant = Abonnement::where('statut', 'actif')
            ->whereYear('debut_le', now()->year)
            ->whereMonth('debut_le', now()->month)
            ->sum('montant');
        $revenuMoisPrecedent = Abonnement::where('statut', 'actif')
            ->whereYear('debut_le', now()->subMonth()->year)
            ->whereMonth('debut_le', now()->subMonth()->month)
            ->sum('montant');
        $progressionRevenu = $revenuMoisPrecedent > 0
            ? round(($revenuMoisCourant - $revenuMoisPrecedent) / $revenuMoisPrecedent * 100, 1)
            : ($revenuMoisCourant > 0 ? 100 : 0);

        $abonnementsActifs = Abonnement::where('statut', 'actif')
            ->where('expire_le', '>=', now())
            ->count();
        $tauxConversion = Abonnement::count() > 0
            ? round(Abonnement::where('statut', 'actif')->count() / Abonnement::count() * 100, 1)
            : 0;
        $panierMoyen = $nbAbonnements > 0 ? round($revenuTotal / $nbAbonnements) : 0;

        // Performance instituts
        $instituts = Institut::where('actif', true)
            ->with('proprietaire')
            ->withCount(['ventes as ca_total' => function ($q) use ($annee, $moisFiltre) {
                $q->where('statut', 'validee')->whereYear('created_at', $annee);
                if ($moisFiltre) $q->whereMonth('created_at', $moisFiltre);
                $q->select(DB::raw("COALESCE(SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END), 0)"));
            }])
            ->withCount(['ventes as nb_ventes' => function ($q) use ($annee, $moisFiltre) {
                $q->where('statut', 'validee')->whereYear('created_at', $annee);
                if ($moisFiltre) $q->whereMonth('created_at', $moisFiltre);
            }])
            ->withCount(['ventes as ca_mois_courant' => function ($q) {
                $q->where('statut', 'validee')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->select(DB::raw("COALESCE(SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END), 0)"));
            }])
            ->get()
            ->sortByDesc('ca_total');

        $depensesQuery = Depense::whereYear('date', $annee);
        if ($moisFiltre) $depensesQuery->whereMonth('date', $moisFiltre);
        $depensesParInstitut = $depensesQuery
            ->selectRaw('institut_id, SUM(montant) as total')
            ->groupBy('institut_id')
            ->pluck('total', 'institut_id');

        $caMoisPrecedent = Vente::where('statut', 'validee')
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->selectRaw("institut_id, SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END) as total")
            ->groupBy('institut_id')
            ->pluck('total', 'institut_id');

        // Top constants + top dépensiers
        $constantsQuery = Vente::where('statut', 'validee')->whereYear('created_at', $annee);
        if ($moisFiltre) $constantsQuery->whereMonth('created_at', $moisFiltre);
        $constantsRaw = $constantsQuery
            ->selectRaw("institut_id, COUNT(DISTINCT " . $monthExpr('created_at') . ") as mois_actifs, SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END) as ca")
            ->groupBy('institut_id')
            ->orderByDesc('mois_actifs')
            ->limit(5)
            ->get();

        $institutsConstants = $constantsRaw->map(function ($row) {
            $inst = Institut::find($row->institut_id);
            return $inst ? (object) ['institut' => $inst, 'mois_actifs' => $row->mois_actifs, 'ca' => $row->ca] : null;
        })->filter();

        $topDepQuery = Depense::whereYear('date', $annee);
        if ($moisFiltre) $topDepQuery->whereMonth('date', $moisFiltre);
        $topDepensiers = $topDepQuery
            ->selectRaw('institut_id, SUM(montant) as total')
            ->groupBy('institut_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $inst = Institut::find($row->institut_id);
                return $inst ? (object) ['institut' => $inst, 'total' => $row->total] : null;
            })->filter();

        $anneesDisponibles = Abonnement::selectRaw('DISTINCT ' . $yearExpr('debut_le') . ' as annee')
            ->whereNotNull('debut_le')
            ->orderByDesc('annee')
            ->pluck('annee')
            ->unique();
        if ($anneesDisponibles->isEmpty()) $anneesDisponibles = collect([now()->year]);

        // ═══════════════════════════════════════════════════════════════════
        // PARTIE 2 — PAIEMENTS & CA EN LIGNE (ex « Dashboard Finance »)
        // ═══════════════════════════════════════════════════════════════════
        $transactionsQuery = PaymentTransaction::where('status', 'completed')
            ->whereYear('paid_at', $annee);
        if ($moisFiltre) $transactionsQuery->whereMonth('paid_at', $moisFiltre);

        $caPlateformeAnnee = (clone $transactionsQuery)->sum('net_amount');
        $nbTransactionsAnnee = (clone $transactionsQuery)->count();
        $totalFrais = (clone $transactionsQuery)->sum('fees');

        $caPlateformeParMois = PaymentTransaction::where('status', 'completed')
            ->whereYear('paid_at', $annee)
            ->selectRaw($monthExpr('paid_at') . ' as mois, SUM(net_amount) as total, COUNT(*) as nb')
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois');

        $commandesQuery = Commande::where('payee', true)
            ->whereYear('payee_at', $annee);
        if ($moisFiltre) $commandesQuery->whereMonth('payee_at', $moisFiltre);

        $caBoutiqueAnnee = (clone $commandesQuery)->sum('total');
        $nbCommandesPayees = (clone $commandesQuery)->count();

        $caBoutiqueParMois = Commande::where('payee', true)
            ->whereYear('payee_at', $annee)
            ->selectRaw($monthExpr('payee_at') . ' as mois, SUM(total) as total, COUNT(*) as nb')
            ->groupBy('mois')
            ->orderBy('mois')
            ->pluck('total', 'mois');

        $plateformeData = [];
        $boutiqueData = [];
        for ($m = 1; $m <= 12; $m++) {
            $plateformeData[] = (int) ($caPlateformeParMois[$m] ?? 0);
            $boutiqueData[] = (int) ($caBoutiqueParMois[$m] ?? 0);
        }

        // Taux de succès
        $statsStatuts = PaymentTransaction::whereYear('created_at', $annee)
            ->selectRaw('status, COUNT(*) as nb, COALESCE(SUM(amount), 0) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
        $nbTotal = $statsStatuts->sum('nb');
        $nbReussies = $statsStatuts['completed']['nb'] ?? 0;
        $tauxSucces = $nbTotal > 0 ? round($nbReussies / $nbTotal * 100, 1) : 0;

        // Répartition par type / méthode
        $parType = PaymentTransaction::where('status', 'completed')
            ->whereYear('paid_at', $annee)
            ->selectRaw('type, SUM(net_amount) as total, COUNT(*) as nb')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        $parMethode = PaymentTransaction::where('status', 'completed')
            ->whereYear('paid_at', $annee)
            ->selectRaw('payment_method_code, SUM(net_amount) as total, COUNT(*) as nb')
            ->groupBy('payment_method_code')
            ->orderByDesc('total')
            ->get();

        $remboursements = PaymentTransaction::where('status', 'refunded')
            ->whereYear('created_at', $annee)
            ->selectRaw('SUM(refunded_amount) as total, COUNT(*) as nb')
            ->first();
        $montantRembourse = (int) ($remboursements->total ?? 0);
        $nbRemboursements = (int) ($remboursements->nb ?? 0);

        // Top instituts boutique
        $topInstitutsBoutique = Commande::where('payee', true)
            ->whereYear('payee_at', $annee)
            ->join('instituts', 'commandes.institut_id', '=', 'instituts.id')
            ->selectRaw('instituts.id, instituts.nom, instituts.ville, SUM(commandes.total) as ca_en_ligne, COUNT(*) as nb_commandes')
            ->groupBy('instituts.id', 'instituts.nom', 'instituts.ville')
            ->orderByDesc('ca_en_ligne')
            ->limit(10)
            ->get();

        $recentes = PaymentTransaction::with('user')
            ->latest()
            ->take(12)
            ->get();

        // Progression CA total (plateforme + boutique)
        $caTotalAnnee = array_sum($plateformeData) + array_sum($boutiqueData);
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
            // Filtres
            'annee', 'moisFiltre', 'moisLabels', 'anneesDisponibles',
            // Partie 1 : abonnements + instituts
            'revenuTotal', 'nbAbonnements', 'revenusData', 'nbData',
            'moisMax', 'moisMin',
            'revenusParPlan', 'revenusParPeriode',
            'instituts', 'depensesParInstitut', 'caMoisPrecedent',
            'institutsConstants', 'topDepensiers',
            'revenuMoisCourant', 'revenuMoisPrecedent', 'progressionRevenu',
            'abonnementsActifs', 'tauxConversion', 'panierMoyen',
            // Partie 2 : paiements + boutique
            'caPlateformeAnnee', 'nbTransactionsAnnee', 'totalFrais',
            'caBoutiqueAnnee', 'nbCommandesPayees',
            'plateformeData', 'boutiqueData',
            'nbTotal', 'nbReussies', 'tauxSucces', 'statsStatuts',
            'parType', 'parMethode',
            'montantRembourse', 'nbRemboursements',
            'topInstitutsBoutique', 'recentes',
            'caTotalAnnee', 'caTotalAnneePrecedente', 'progression',
        ));
    }
}
