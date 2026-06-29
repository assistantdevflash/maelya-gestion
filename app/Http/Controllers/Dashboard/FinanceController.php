<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use App\Models\Vente;
use App\Models\VenteItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            ->selectRaw("SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END) as total_reel")
            ->value('total_reel') ?? 0;

        $nbVentes = Vente::where('statut', 'validee')
            ->whereDate('created_at', '>=', $debut)
            ->whereDate('created_at', '<=', $fin)
            ->count();

        $totalDepenses = Depense::whereDate('date', '>=', $debut)
            ->whereDate('date', '<=', $fin)
            ->sum('montant');

        $benefice = $totalVentes - $totalDepenses;
        $marge = $totalVentes > 0 ? round(($benefice / $totalVentes) * 100, 1) : 0;

        // Valorisation du stock + marge potentielle (basée sur CMP)
        $produitsValorisation = \App\Models\Produit::where('actif', true)
            ->where('stock', '>', 0)
            ->get(['stock', 'cout_moyen_pondere', 'prix_achat', 'prix_vente']);
        $valeurStock = $produitsValorisation->sum(fn ($p) =>
            $p->stock * ($p->cout_moyen_pondere ?: $p->prix_achat)
        );
        $margePotentielleStock = $produitsValorisation->sum(fn ($p) =>
            $p->stock * max(0, $p->prix_vente - ($p->cout_moyen_pondere ?: $p->prix_achat))
        );

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
        $driver = \DB::getDriverName();
        $moisExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at) as mois_key"
            : "DATE_FORMAT(created_at, '%Y-%m') as mois_key";
        $ventesParMois = Vente::where('statut', 'validee')
            ->where('created_at', '>=', $debut12)
            ->selectRaw("$moisExpr, SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END) as ca")
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

        $institutId = session('current_institut_id', Auth::user()->institut_id);

        // ── Prestations par catégorie (inclut les ventes rapides type_libre=prestation) ──
        $regularPresta = VenteItem::query()
            ->select([
                'categories_prestations.nom as categorie_nom',
                DB::raw('SUM(vente_items.quantite) as quantite'),
                DB::raw('SUM(vente_items.sous_total) as chiffre_affaires'),
            ])
            ->join('ventes', 'ventes.id', '=', 'vente_items.vente_id')
            ->join('prestations', 'prestations.id', '=', 'vente_items.item_id')
            ->leftJoin('categories_prestations', 'categories_prestations.id', '=', 'prestations.categorie_id')
            ->where('vente_items.type', 'prestation')
            ->where('ventes.institut_id', $institutId)
            ->where('ventes.statut', 'validee')
            ->whereDate('ventes.created_at', '>=', $debut)
            ->whereDate('ventes.created_at', '<=', $fin)
            ->groupBy('categories_prestations.nom')
            ->get();

        // Ventes rapides associées à une catégorie de prestation
        $librePresta = VenteItem::query()
            ->select([
                'categories_prestations.nom as categorie_nom',
                DB::raw('SUM(vente_items.quantite) as quantite'),
                DB::raw('SUM(vente_items.sous_total) as chiffre_affaires'),
            ])
            ->join('ventes', 'ventes.id', '=', 'vente_items.vente_id')
            ->join('categories_prestations', 'categories_prestations.id', '=', 'vente_items.categorie_id')
            ->where('vente_items.type', 'libre')
            ->where('vente_items.type_libre', 'prestation')
            ->whereNotNull('vente_items.categorie_id')
            ->where('ventes.institut_id', $institutId)
            ->where('ventes.statut', 'validee')
            ->whereDate('ventes.created_at', '>=', $debut)
            ->whereDate('ventes.created_at', '<=', $fin)
            ->groupBy('categories_prestations.nom')
            ->get();

        $prestationsParCategorie = $regularPresta->concat($librePresta)
            ->groupBy('categorie_nom')
            ->map(fn ($group) => (object) [
                'categorie_nom'   => $group->first()->categorie_nom,
                'quantite'         => (int) $group->sum('quantite'),
                'chiffre_affaires' => (int) $group->sum('chiffre_affaires'),
            ])
            ->sortByDesc('chiffre_affaires')
            ->values();

        // ── Produits par catégorie (inclut les ventes rapides type_libre=produit) ──
        $regularProduits = VenteItem::query()
            ->select([
                'categories_produits.nom as categorie_nom',
                DB::raw('SUM(vente_items.quantite) as quantite'),
                DB::raw('SUM(vente_items.sous_total) as chiffre_affaires'),
            ])
            ->join('ventes', 'ventes.id', '=', 'vente_items.vente_id')
            ->join('produits', 'produits.id', '=', 'vente_items.item_id')
            ->leftJoin('categories_produits', 'categories_produits.id', '=', 'produits.categorie_id')
            ->where('vente_items.type', 'produit')
            ->where('ventes.institut_id', $institutId)
            ->where('ventes.statut', 'validee')
            ->whereDate('ventes.created_at', '>=', $debut)
            ->whereDate('ventes.created_at', '<=', $fin)
            ->groupBy('categories_produits.nom')
            ->get();

        // Ventes rapides associées à une catégorie de produit
        $libreProduits = VenteItem::query()
            ->select([
                'categories_produits.nom as categorie_nom',
                DB::raw('SUM(vente_items.quantite) as quantite'),
                DB::raw('SUM(vente_items.sous_total) as chiffre_affaires'),
            ])
            ->join('ventes', 'ventes.id', '=', 'vente_items.vente_id')
            ->join('categories_produits', 'categories_produits.id', '=', 'vente_items.categorie_id')
            ->where('vente_items.type', 'libre')
            ->where('vente_items.type_libre', 'produit')
            ->whereNotNull('vente_items.categorie_id')
            ->where('ventes.institut_id', $institutId)
            ->where('ventes.statut', 'validee')
            ->whereDate('ventes.created_at', '>=', $debut)
            ->whereDate('ventes.created_at', '<=', $fin)
            ->groupBy('categories_produits.nom')
            ->get();

        $produitsParCategorie = $regularProduits->concat($libreProduits)
            ->groupBy('categorie_nom')
            ->map(fn ($group) => (object) [
                'categorie_nom'   => $group->first()->categorie_nom,
                'quantite'         => (int) $group->sum('quantite'),
                'chiffre_affaires' => (int) $group->sum('chiffre_affaires'),
            ])
            ->sortByDesc('chiffre_affaires')
            ->values();

        // ═══ TRÉSORERIE PRÉVISIONNELLE ═══
        $joursPrevi = (int) $request->input('jours_previ', 30);
        $joursPrevi = max(7, min(90, $joursPrevi));
        
        $debutPrevi = \Carbon\Carbon::today();
        $finPrevi = \Carbon\Carbon::today()->addDays($joursPrevi);
        
        // RDV confirmés ou en attente futurs
        $rdvFuturs = \App\Models\RendezVous::with('prestations')
            ->where('debut_le', '>=', $debutPrevi)
            ->where('debut_le', '<=', $finPrevi)
            ->whereIn('statut', ['confirme', 'en_attente'])
            ->get();
        
        $revenusPrevu = $rdvFuturs->sum(function ($r) {
            return $r->prestations->sum('prix');
        });
        
        // Moyenne quotidienne des ventes des 30 derniers jours
        $caRecent = Vente::where('statut', 'validee')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('total');
        $caQuotidien = $caRecent / 30;
        $projectionVentes = (int) round($caQuotidien * $joursPrevi);
        
        // Dépenses moyennes des 90 derniers jours
        $depensesRecentes = Depense::where('date', '>=', now()->subDays(90))->sum('montant');
        $depQuotidienne = $depensesRecentes / 90;
        $projectionDepenses = (int) round($depQuotidienne * $joursPrevi);
        
        $soldePrevi = $revenusPrevu + $projectionVentes - $projectionDepenses;
        
        // Détail par jour pour graphique
        $jourLabel = [];
        $jourEntrees = [];
        $jourSorties = [];
        for ($i = 0; $i < $joursPrevi; $i++) {
            $d = $debutPrevi->copy()->addDays($i);
            $jourLabel[] = $d->format('d/m');
            $entrees = $rdvFuturs->where('debut_le', '>=', $d)
                ->where('debut_le', '<', $d->copy()->addDay())
                ->sum(fn ($r) => $r->prestations->sum('prix'));
            $jourEntrees[] = (int) ($entrees + $caQuotidien);
            $jourSorties[] = (int) $depQuotidienne;
        }

        return view('dashboard.finances.index', compact(
            'totalVentes', 'totalDepenses', 'nbVentes', 'benefice', 'marge',
            'depensesParCat', 'depenses', 'evolutionMois', 'periode', 'debut', 'fin',
            'valeurStock', 'margePotentielleStock',
            'prestationsParCategorie', 'produitsParCategorie',
            'joursPrevi', 'revenusPrevu', 'projectionVentes', 'projectionDepenses', 
            'soldePrevi', 'jourLabel', 'jourEntrees', 'jourSorties', 'rdvFuturs'
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

        $data['user_id'] = Auth::id();

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

    /** Page depenses pour les employes (depenses personnelles uniquement) */
    public function depenses(Request $request)
    {
        $user = Auth::user();

        $depenses = Depense::orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->when($user->isEmploye(), fn($q) => $q->where('user_id', $user->id))
            ->paginate(30);

        $totalPeriode = Depense::orderBy('date', 'desc')
            ->when($user->isEmploye(), fn($q) => $q->where('user_id', $user->id))
            ->sum('montant');

        return view('dashboard.finances.depenses', compact('depenses', 'totalPeriode'));
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
