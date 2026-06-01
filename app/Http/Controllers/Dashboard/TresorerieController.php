<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use App\Models\RendezVous;
use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TresorerieController extends Controller
{
    public function index(Request $request)
    {
        $jours = (int) $request->input('jours', 30);
        $jours = max(7, min(90, $jours));

        $debut = Carbon::today();
        $fin   = Carbon::today()->addDays($jours);

        // RDV confirmés ou en attente futurs : on les considère comme revenus prévus
        $rdvFuturs = RendezVous::with('prestations')
            ->where('debut_le', '>=', $debut)
            ->where('debut_le', '<=', $fin)
            ->whereIn('statut', ['confirme', 'en_attente'])
            ->get();

        $revenusPrevu = $rdvFuturs->sum(function ($r) {
            return $r->prestations->sum('prix');
        });

        // Moyenne quotidienne des ventes des 30 derniers jours pour projeter
        $caRecent = Vente::where('statut', 'validee')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('total');
        $caQuotidien = $caRecent / 30;
        $projectionVentes = (int) round($caQuotidien * $jours);

        // Dépenses moyennes des 90 derniers jours (récurrentes implicites)
        $depensesRecentes = Depense::where('date', '>=', now()->subDays(90))->sum('montant');
        $depQuotidienne = $depensesRecentes / 90;
        $projectionDepenses = (int) round($depQuotidienne * $jours);

        $solde = $revenusPrevu + $projectionVentes - $projectionDepenses;

        // Détail par jour pour graphique simple
        $jourLabel = [];
        $jourEntrees = [];
        $jourSorties = [];
        for ($i = 0; $i < $jours; $i++) {
            $d = $debut->copy()->addDays($i);
            $jourLabel[] = $d->format('d/m');
            $entrees = $rdvFuturs->where('debut_le', '>=', $d)
                ->where('debut_le', '<', $d->copy()->addDay())
                ->sum(fn ($r) => $r->prestations->sum('prix'));
            $jourEntrees[] = (int) ($entrees + $caQuotidien);
            $jourSorties[] = (int) $depQuotidienne;
        }

        return view('dashboard.tresorerie.index', compact(
            'jours', 'revenusPrevu', 'projectionVentes', 'projectionDepenses', 'solde',
            'jourLabel', 'jourEntrees', 'jourSorties', 'rdvFuturs'
        ));
    }
}
