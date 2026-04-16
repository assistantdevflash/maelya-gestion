<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\Vente;
use App\Models\Depense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminFinanceController extends Controller
{
    public function index(Request $request)
    {
        $annee = (int) $request->get('annee', now()->year);
        $moisFiltre = $request->get('mois'); // null = tous

        $isSqlite = DB::getDriverName() === 'sqlite';
        $monthExpr = fn(string $col) => $isSqlite ? "CAST(strftime('%m', $col) AS INTEGER)" : "MONTH($col)";
        $yearExpr = fn(string $col) => $isSqlite ? "CAST(strftime('%Y', $col) AS INTEGER)" : "YEAR($col)";

        // ── Revenus abonnements ──────────────────────────────────────────
        $abonnementsQuery = Abonnement::where('statut', 'actif')
            ->whereYear('debut_le', $annee);

        if ($moisFiltre) {
            $abonnementsQuery->whereMonth('debut_le', $moisFiltre);
        }

        $revenuTotal = (clone $abonnementsQuery)->sum('montant');
        $nbAbonnements = (clone $abonnementsQuery)->count();

        // Revenus par mois (graphique)
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
            ->pluck('nb', 'mois');

        // Remplir les 12 mois
        $moisLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        $revenusData = [];
        $nbData = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenusData[] = $revenusParMois[$m] ?? 0;
            $nbData[] = $nbParMois[$m] ?? 0;
        }

        // Mois le + productif / le - productif
        $moisMax = $revenusParMois->isNotEmpty() ? $revenusParMois->sortDesc()->keys()->first() : null;
        $moisMin = $revenusParMois->filter(fn($v) => $v > 0)->isNotEmpty()
            ? $revenusParMois->filter(fn($v) => $v > 0)->sort()->keys()->first()
            : null;

        // ── Répartition par plan ─────────────────────────────────────────
        $revenusParPlan = Abonnement::where('statut', 'actif')
            ->whereYear('debut_le', $annee)
            ->join('plans_abonnement', 'abonnements.plan_id', '=', 'plans_abonnement.id')
            ->selectRaw('plans_abonnement.nom as plan, SUM(abonnements.montant) as total, COUNT(*) as nb')
            ->groupBy('plans_abonnement.nom')
            ->orderByDesc('total')
            ->get();

        // ── Répartition par période ──────────────────────────────────────
        $revenusParPeriode = Abonnement::where('statut', 'actif')
            ->whereYear('debut_le', $annee)
            ->selectRaw('periode, SUM(montant) as total, COUNT(*) as nb')
            ->groupBy('periode')
            ->orderByDesc('total')
            ->get();

        // ── CA des instituts ─────────────────────────────────────────────
        $instituts = Institut::where('actif', true)
            ->with('proprietaire')
            ->withCount(['ventes as ca_total' => function ($q) use ($annee, $moisFiltre) {
                $q->where('statut', 'validee')->whereYear('created_at', $annee);
                if ($moisFiltre) $q->whereMonth('created_at', $moisFiltre);
                $q->select(DB::raw('COALESCE(SUM(total), 0)'));
            }])
            ->withCount(['ventes as nb_ventes' => function ($q) use ($annee, $moisFiltre) {
                $q->where('statut', 'validee')->whereYear('created_at', $annee);
                if ($moisFiltre) $q->whereMonth('created_at', $moisFiltre);
            }])
            ->withCount(['ventes as ca_mois_courant' => function ($q) {
                $q->where('statut', 'validee')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->select(DB::raw('COALESCE(SUM(total), 0)'));
            }])
            ->get()
            ->sortByDesc('ca_total');

        // Calculer les dépenses par institut
        $depensesQuery = Depense::whereYear('date', $annee);
        if ($moisFiltre) $depensesQuery->whereMonth('date', $moisFiltre);
        $depensesParInstitut = $depensesQuery
            ->selectRaw('institut_id, SUM(montant) as total')
            ->groupBy('institut_id')
            ->pluck('total', 'institut_id');

        // Progression mois courant vs mois précédent par institut
        $caMoisPrecedent = Vente::where('statut', 'validee')
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->selectRaw('institut_id, SUM(total) as total')
            ->groupBy('institut_id')
            ->pluck('total', 'institut_id');

        // ── Top 5 instituts les + constants (nb de mois actifs) ──────────
        $constantsQuery = Vente::where('statut', 'validee')
            ->whereYear('created_at', $annee);
        if ($moisFiltre) $constantsQuery->whereMonth('created_at', $moisFiltre);
        $constantsRaw = $constantsQuery
            ->selectRaw('institut_id, COUNT(DISTINCT ' . $monthExpr('created_at') . ') as mois_actifs, SUM(total) as ca')
            ->groupBy('institut_id')
            ->orderByDesc('mois_actifs')
            ->limit(5)
            ->get();

        $institutsConstants = $constantsRaw->map(function ($row) {
            $inst = Institut::find($row->institut_id);
            return $inst ? (object) ['institut' => $inst, 'mois_actifs' => $row->mois_actifs, 'ca' => $row->ca] : null;
        })->filter();

        // ── Top dépensiers ───────────────────────────────────────────────
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

        // ── KPIs globaux ─────────────────────────────────────────────────
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

        // Panier moyen
        $panierMoyen = $nbAbonnements > 0 ? round($revenuTotal / $nbAbonnements) : 0;

        // Années disponibles
        $anneesDisponibles = Abonnement::selectRaw('DISTINCT ' . $yearExpr('debut_le') . ' as annee')
            ->whereNotNull('debut_le')
            ->orderByDesc('annee')
            ->pluck('annee')
            ->unique();

        if ($anneesDisponibles->isEmpty()) {
            $anneesDisponibles = collect([now()->year]);
        }

        return view('admin.finance.index', compact(
            'annee', 'moisFiltre', 'moisLabels',
            'revenuTotal', 'nbAbonnements', 'revenusData', 'nbData',
            'moisMax', 'moisMin',
            'revenusParPlan', 'revenusParPeriode',
            'instituts', 'depensesParInstitut', 'caMoisPrecedent',
            'institutsConstants', 'topDepensiers',
            'revenuMoisCourant', 'revenuMoisPrecedent', 'progressionRevenu',
            'abonnementsActifs', 'tauxConversion', 'panierMoyen',
            'anneesDisponibles'
        ));
    }
}
