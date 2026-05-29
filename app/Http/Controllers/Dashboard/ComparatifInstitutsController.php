<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Vente;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComparatifInstitutsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        abort_unless($user->aPlanEntreprise(), 403, 'Cette fonctionnalité nécessite le plan Entreprise.');

        $instituts = $user->mesInstituts()->where('actif', true)->orderBy('nom')->get();
        $debutMois = now()->startOfMonth();
        $finMois   = now()->endOfMonth();

        $stats = [];
        foreach ($instituts as $i) {
            $caMois = Vente::where('institut_id', $i->id)
                ->where('statut', '!=', 'annulee')
                ->whereBetween('created_at', [$debutMois, $finMois])
                ->sum('total');

            $nbVentes = Vente::where('institut_id', $i->id)
                ->where('statut', '!=', 'annulee')
                ->whereBetween('created_at', [$debutMois, $finMois])
                ->count();

            $nbClients = Client::where('institut_id', $i->id)->count();

            $nbClientsNouv = Client::where('institut_id', $i->id)
                ->whereBetween('created_at', [$debutMois, $finMois])
                ->count();

            $topPresta = DB::table('vente_items')
                ->join('ventes', 'vente_items.vente_id', '=', 'ventes.id')
                ->where('ventes.institut_id', $i->id)
                ->where('ventes.statut', '!=', 'annulee')
                ->whereBetween('ventes.created_at', [$debutMois, $finMois])
                ->where('vente_items.type', 'prestation')
                ->select('vente_items.libelle', DB::raw('COUNT(*) as nb'))
                ->groupBy('vente_items.libelle')
                ->orderByDesc('nb')
                ->limit(1)
                ->first();

            $stats[] = [
                'institut'       => $i,
                'ca_mois'        => (int) $caMois,
                'nb_ventes'      => $nbVentes,
                'nb_clients'     => $nbClients,
                'nb_clients_nouv'=> $nbClientsNouv,
                'panier_moyen'   => $nbVentes > 0 ? (int) ($caMois / $nbVentes) : 0,
                'top_presta'     => $topPresta?->libelle,
            ];
        }

        // Tri par CA décroissant
        usort($stats, fn($a, $b) => $b['ca_mois'] <=> $a['ca_mois']);

        $totalCa = array_sum(array_column($stats, 'ca_mois'));
        $totalVentes = array_sum(array_column($stats, 'nb_ventes'));

        return view('dashboard.comparatif.index', compact('stats', 'totalCa', 'totalVentes'));
    }
}
