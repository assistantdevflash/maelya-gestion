<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\HistoriquePoints;
use App\Models\ProgrammeFidelite;
use App\Services\FideliteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FideliteController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index(Request $request)
    {
        $institutId = $this->institutId();

        $programme = ProgrammeFidelite::where('institut_id', $institutId)->first();

        $search = trim((string) $request->input('q'));

        // Par défaut, seuls les clients qui ont des points à convertir.
        // « tous » permet de retrouver un client à zéro point pour l'ajuster.
        $filtrePoints = $request->input('clients') === 'tous' ? 'tous' : 'avec_points';

        $clients = Client::where('institut_id', $institutId)
            ->where('actif', true)
            ->when($filtrePoints === 'avec_points', fn ($q) => $q->where('points_fidelite', '>', 0))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('prenom', 'like', "%{$search}%")
                       ->orWhere('nom', 'like', "%{$search}%")
                       ->orWhere('raison_sociale', 'like', "%{$search}%")
                       ->orWhere('telephone', 'like', "%{$search}%");
                });
            })
            ->with(['codesReductionFidelite' => function ($q) {
                $q->where('actif', true)
                  ->where('code', 'like', FideliteService::PREFIXE_CODE . '%')
                  // Comparaison sur la date du jour : un code qui expire
                  // aujourd'hui reste affiché jusqu'au soir.
                  ->where(function ($q2) {
                      $q2->whereNull('date_fin')->orWhere('date_fin', '>=', now()->toDateString());
                  })
                  ->where(function ($q2) {
                      $q2->whereNull('limite_utilisation')->orWhereColumn('nb_utilisations', '<', 'limite_utilisation');
                  })
                  ->latest();
            }])
            ->orderByDesc('points_fidelite')
            ->paginate(20)
            ->withQueryString();

        // Tous les compteurs portent sur les clients actifs, comme le classement.
        $base = fn () => Client::where('institut_id', $institutId)->where('actif', true);

        $totalPoints       = (int) $base()->sum('points_fidelite');
        $clientsAvecPoints = $base()->where('points_fidelite', '>', 0)->count();
        $clientsEligibles  = $programme
            ? $base()->where('points_fidelite', '>=', $programme->seuil_recompense)->count()
            : 0;

        $recompensesDistribuees = HistoriquePoints::where('institut_id', $institutId)
            ->where('type', 'recompense')
            ->count();

        $derniersMouvements = HistoriquePoints::where('institut_id', $institutId)
            ->with(['client', 'user', 'codeReduction'])
            ->latest()
            ->limit(12)
            ->get();

        return view('dashboard.fidelite.index', compact(
            'programme', 'clients', 'totalPoints', 'clientsAvecPoints', 'clientsEligibles',
            'recompensesDistribuees', 'derniersMouvements', 'search', 'filtrePoints'
        ));
    }

    public function configurer(Request $request)
    {
        $request->validate([
            'actif' => ['required', 'boolean'],
            'tranche_fcfa' => ['required', 'integer', 'min:100'],
            'points_par_tranche' => ['required', 'integer', 'min:1', 'max:100'],
            'seuil_recompense' => ['required', 'integer', 'min:1'],
            'type_recompense' => ['required', 'in:pourcentage,montant_fixe'],
            'valeur_recompense' => ['required', 'integer', 'min:1'],
        ]);

        $institutId = $this->institutId();

        ProgrammeFidelite::updateOrCreate(
            ['institut_id' => $institutId],
            $request->only(['actif', 'tranche_fcfa', 'points_par_tranche', 'seuil_recompense', 'type_recompense', 'valeur_recompense'])
        );

        return back()->with('success', 'Programme de fidélité mis à jour.');
    }

    public function recompenser(Request $request, Client $client, FideliteService $service)
    {
        $institutId = $this->institutId();

        abort_unless($client->institut_id === $institutId, 403);

        $programme = $service->programmeActif($institutId);

        abort_unless($programme, 403, 'Programme de fidélité non configuré.');

        try {
            $code = $service->convertir($client, $programme, Auth::id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        $valeur = $programme->valeur_recompense . ($programme->type_recompense === 'pourcentage' ? '%' : ' FCFA');

        return back()->with('success', "Code {$code->code} généré pour {$client->nom_complet} ({$valeur} de réduction).");
    }

    public function ajuster(Request $request, Client $client, FideliteService $service)
    {
        $data = $request->validate([
            'points' => ['required', 'integer', 'not_in:0'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        abort_unless($client->institut_id === $this->institutId(), 403);

        try {
            $applique = $service->ajuster($client, (int) $data['points'], $data['description'], Auth::id());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        // On annonce le mouvement réellement appliqué, pas celui demandé.
        $signe = $applique > 0 ? '+' : '';

        return back()->with('success', "{$signe}{$applique} points pour {$client->nom_complet}. Solde : {$client->fresh()->points_fidelite} pts.");
    }

    /**
     * Historique d'un client (JSON, pour le panneau latéral).
     */
    public function historique(Client $client)
    {
        abort_unless($client->institut_id === $this->institutId(), 403);

        $lignes = HistoriquePoints::where('institut_id', $this->institutId())
            ->where('client_id', $client->id)
            ->with('user')
            ->latest()
            ->limit(60)
            ->get()
            ->map(fn (HistoriquePoints $h) => [
                'date'        => $h->created_at?->format('d/m/Y H:i'),
                'points'      => (int) $h->points,
                'type'        => $h->type,
                'description' => $h->description,
                'auteur'      => $h->user?->nom_complet,
            ]);

        return response()->json([
            'client' => $client->nom_complet,
            'solde'  => (int) $client->points_fidelite,
            'lignes' => $lignes,
        ]);
    }

    /**
     * Export CSV du solde de fidélité des clients actifs.
     */
    public function export()
    {
        $institutId = $this->institutId();
        $programme  = ProgrammeFidelite::where('institut_id', $institutId)->first();
        $seuil      = (int) ($programme->seuil_recompense ?? 0);

        $clients = Client::where('institut_id', $institutId)
            ->where('actif', true)
            ->orderByDesc('points_fidelite')
            ->get();

        return response()->streamDownload(function () use ($clients, $seuil) {
            $sortie = fopen('php://output', 'w');
            fputcsv($sortie, ['Client', 'Téléphone', 'Points', 'Récompenses disponibles', 'Prochain palier'], ';');

            foreach ($clients as $client) {
                $points = (int) $client->points_fidelite;

                fputcsv($sortie, [
                    $client->nom_complet,
                    $client->telephone,
                    $points,
                    $seuil > 0 ? intdiv($points, $seuil) : 0,
                    $seuil > 0 ? ($points % $seuil) . ' / ' . $seuil : '',
                ], ';');
            }

            fclose($sortie);
        }, 'fidelite-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function imprimerCode(CodeReduction $codeReduction)
    {
        abort_unless($codeReduction->institut_id === $this->institutId(), 403);

        // Un seul gabarit de coupon pour tous les codes : celui des remises,
        // qui porte le QR code et le plafond éventuel.
        return redirect()->route('dashboard.codes-reduction.print', $codeReduction);
    }
}
