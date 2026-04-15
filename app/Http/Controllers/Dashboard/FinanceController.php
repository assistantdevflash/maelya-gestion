<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use App\Models\Vente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $periode = $request->input('periode', 'mois');
        [$debutStr, $finStr] = $this->getPeriode($periode, $request);
        $debut = \Carbon\Carbon::parse($debutStr);
        $fin = \Carbon\Carbon::parse($finStr);

        $totalVentes = Vente::where('statut', 'validee')
            ->whereDate('created_at', '>=', $debut)
            ->whereDate('created_at', '<=', $fin)
            ->sum('total');

        $nbVentes = Vente::where('statut', 'validee')
            ->whereDate('created_at', '>=', $debut)
            ->whereDate('created_at', '<=', $fin)
            ->count();

        $totalDepenses = Depense::whereDate('date', '>=', $debut)
            ->whereDate('date', '<=', $fin)
            ->sum('montant');

        $benefice = $totalVentes - $totalDepenses;
        $marge = $totalVentes > 0 ? round(($benefice / $totalVentes) * 100, 1) : 0;

        // Répartition dépenses par catégorie
        $depensesParCat = Depense::whereDate('date', '>=', $debut)
            ->whereDate('date', '<=', $fin)
            ->selectRaw('categorie, SUM(montant) as total')
            ->groupBy('categorie')
            ->pluck('total', 'categorie');

        // Dernières dépenses
        $depenses = Depense::whereDate('date', '>=', $debut)
            ->whereDate('date', '<=', $fin)
            ->latest('date')
            ->paginate(20);

        // Évolution mensuelle 12 mois (une seule requête)
        $debut12 = now()->subMonths(11)->startOfMonth();
        $ventesParMois = Vente::where('statut', 'validee')
            ->where('created_at', '>=', $debut12)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as mois_key, SUM(total) as ca")
            ->groupBy('mois_key')
            ->pluck('ca', 'mois_key');

        $evolutionMois = [];
        for ($i = 11; $i >= 0; $i--) {
            $mois = now()->subMonths($i);
            $key = $mois->format('Y-m');
            $evolutionMois[] = [
                'label' => $mois->translatedFormat('M y'),
                'ca' => (int) ($ventesParMois[$key] ?? 0),
            ];
        }

        return view('dashboard.finances.index', compact(
            'totalVentes', 'totalDepenses', 'nbVentes', 'benefice', 'marge',
            'depensesParCat', 'depenses', 'evolutionMois', 'periode', 'debut', 'fin'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'categorie' => ['required', 'in:loyer,salaires,fournitures,produits,equipement,marketing,autres'],
            'montant' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        Depense::create($data);

        return back()->with('success', 'Dépense enregistrée.');
    }

    public function update(Request $request, Depense $depense)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'categorie' => ['required', 'in:loyer,salaires,fournitures,produits,equipement,marketing,autres'],
            'montant' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $depense->update($data);
        return back()->with('success', 'Dépense mise à jour.');
    }

    public function destroy(Depense $depense)
    {
        $depense->delete();
        return back()->with('success', 'Dépense supprimée.');
    }

    public function rapport(Request $request)
    {
        $debut = $request->input('debut', now()->startOfMonth()->toDateString());
        $fin = $request->input('fin', now()->toDateString());

        $ventes = Vente::with('client', 'items')
            ->where('statut', 'validee')
            ->whereDate('created_at', '>=', $debut)
            ->whereDate('created_at', '<=', $fin)
            ->latest()
            ->get();

        $depenses = Depense::whereDate('date', '>=', $debut)
            ->whereDate('date', '<=', $fin)
            ->latest('date')
            ->get();

        $revenus = $ventes->sum('total');
        $totalDepenses = $depenses->sum('montant');
        $benefice = $revenus - $totalDepenses;

        return view('dashboard.finances.rapport', compact(
            'ventes', 'depenses', 'revenus', 'totalDepenses', 'benefice', 'debut', 'fin'
        ));
    }

    public function exportVentes(Request $request)
    {
        $debut = $request->input('debut', now()->startOfMonth()->toDateString());
        $fin = $request->input('fin', now()->toDateString());

        $ventes = Vente::with('client')
            ->where('statut', 'validee')
            ->whereDate('created_at', '>=', $debut)
            ->whereDate('created_at', '<=', $fin)
            ->latest()
            ->get();

        $headers = ['Numéro', 'Date', 'Client', 'Total (FCFA)', 'Mode Paiement', 'Statut'];
        $rows = $ventes->map(fn($v) => [
            $v->numero,
            $v->created_at->format('d/m/Y H:i'),
            $v->client?->nom_complet ?? 'Anonyme',
            $v->total,
            $v->mode_paiement,
            $v->statut,
        ]);

        return response()->streamDownload(function () use ($headers, $rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($f, $row, ';');
            }
            fclose($f);
        }, "ventes-{$debut}_{$fin}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportDepenses(Request $request)
    {
        $debut = $request->input('debut', now()->startOfMonth()->toDateString());
        $fin = $request->input('fin', now()->toDateString());

        $depenses = Depense::whereDate('date', '>=', $debut)
            ->whereDate('date', '<=', $fin)
            ->latest('date')
            ->get();

        $headers = ['Date', 'Description', 'Catégorie', 'Montant (FCFA)'];
        $rows = $depenses->map(fn($d) => [
            $d->date->format('d/m/Y'),
            $d->description,
            Depense::categorieLabel($d->categorie),
            $d->montant,
        ]);

        return response()->streamDownload(function () use ($headers, $rows) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($f, $row, ';');
            }
            fclose($f);
        }, "depenses-{$debut}_{$fin}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request)
    {
        $debut = $request->input('debut', now()->startOfMonth()->toDateString());
        $fin = $request->input('fin', now()->toDateString());

        $ventes = Vente::with('client')
            ->where('statut', 'validee')
            ->whereDate('created_at', '>=', $debut)
            ->whereDate('created_at', '<=', $fin)
            ->latest()
            ->get();

        $depenses = Depense::whereDate('date', '>=', $debut)
            ->whereDate('date', '<=', $fin)
            ->latest('date')
            ->get();

        $ca = $ventes->sum('total');
        $depenses_total = $depenses->sum('montant');
        $benefice = $ca - $depenses_total;
        $nbVentes = $ventes->count();
        $institut = auth()->user()->institut;
        $dateDebut = \Carbon\Carbon::parse($debut);
        $dateFin = \Carbon\Carbon::parse($fin);

        $repartitionPaiement = $ventes->groupBy('mode_paiement')->map(fn($group) => [
            'count' => $group->count(),
            'total' => $group->sum('total'),
        ]);

        $pdf = Pdf::loadView('pdf.rapport-financier', compact(
            'ventes', 'depenses', 'ca', 'depenses_total', 'benefice',
            'nbVentes', 'repartitionPaiement', 'dateDebut', 'dateFin', 'institut'
        ));

        return $pdf->download("rapport-financier-{$debut}_{$fin}.pdf");
    }

    private function getPeriode(string $periode, Request $request): array
    {
        return match($periode) {
            'jour', 'today' => [now()->toDateString(), now()->toDateString()],
            'semaine', 'week' => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'mois', 'month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'trimestre' => [now()->firstOfQuarter()->toDateString(), now()->lastOfQuarter()->toDateString()],
            'annee' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            'custom' => [
                $request->input('debut', now()->startOfMonth()->toDateString()),
                $request->input('fin', now()->endOfMonth()->toDateString()),
            ],
            default => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        };
    }
}
