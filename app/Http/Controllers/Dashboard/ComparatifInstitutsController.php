<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\ComparatifInstitutsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ComparatifInstitutsController extends Controller
{
    public function __construct(private ComparatifInstitutsService $service)
    {
    }

    private function instituts()
    {
        $user = Auth::user();
        abort_unless($user->aPlanEntreprise(), 403, 'Cette fonctionnalité nécessite le plan Entreprise.');

        return $user->mesInstituts()->where('actif', true)->orderBy('nom')->get();
    }

    private function donnees(Request $request): array
    {
        $instituts = $this->instituts();
        $periode   = $this->service->periode(
            $request->input('periode', 'mois'),
            $request->input('du'),
            $request->input('au')
        );

        $stats = $this->service->stats(
            $instituts, $periode['debut'], $periode['fin'],
            $periode['prec_debut'], $periode['prec_fin']
        );

        $tri = $request->input('tri', 'ca');

        usort($stats, function ($a, $b) use ($tri) {
            return match ($tri) {
                'nom'      => strcasecmp($a['institut']->nom, $b['institut']->nom),
                'ventes'   => $b['nb_ventes'] <=> $a['nb_ventes'],
                'panier'   => $b['panier_moyen'] <=> $a['panier_moyen'],
                'clients'  => $b['nb_clients'] <=> $a['nb_clients'],
                'evolution'=> ($b['evolution'] ?? -INF) <=> ($a['evolution'] ?? -INF),
                default    => $b['ca'] <=> $a['ca'],
            };
        });

        $totalCa     = array_sum(array_column($stats, 'ca'));
        $totalVentes = array_sum(array_column($stats, 'nb_ventes'));
        $totalClients = array_sum(array_column($stats, 'nb_clients'));

        return compact('instituts', 'stats', 'periode', 'tri', 'totalCa', 'totalVentes', 'totalClients');
    }

    public function index(Request $request)
    {
        return view('dashboard.comparatif.index', $this->donnees($request));
    }

    /**
     * Export CSV du comparatif, dans l'ordre affiché à l'écran.
     */
    public function export(Request $request)
    {
        $donnees = $this->donnees($request);
        $nom     = 'comparatif-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($donnees) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel

            fputcsv($sortie, [
                'Établissement', 'CA encaissé', 'Évolution (%)', 'Ventes',
                'Panier moyen', 'Annulées', 'Taux annulation (%)', 'Remises',
                'Part crédit (%)', 'Produits vendus', 'Clients actifs', 'Nouveaux clients',
                'Période',
            ], ';');

            foreach ($donnees['stats'] as $ligne) {
                fputcsv($sortie, [
                    $ligne['institut']->nom,
                    $ligne['ca'],
                    $ligne['evolution'] ?? '',
                    $ligne['nb_ventes'],
                    $ligne['panier_moyen'],
                    $ligne['nb_annulees'],
                    $ligne['taux_annulation'],
                    $ligne['remises'],
                    $ligne['part_credit'],
                    $ligne['produits_qte'],
                    $ligne['nb_clients'],
                    $ligne['nb_nouveaux'],
                    $donnees['periode']['libelle'],
                ], ';');
            }

            fclose($sortie);
        }, $nom, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $nom . '"',
        ]);
    }
}
