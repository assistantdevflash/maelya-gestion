<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\HistoriquePoints;
use App\Models\ProgrammeFidelite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        // Top clients par points (avec codes fidélité actifs)
        $clients = Client::where('institut_id', $institutId)
            ->where('actif', true)
            ->where('points_fidelite', '>', 0)
            ->with(['codesReductionFidelite' => function ($q) {
                $q->where('actif', true)
                  ->where('code', 'like', 'FID-%')
                  ->where(function ($q2) {
                      $q2->whereNull('date_fin')->orWhere('date_fin', '>=', now());
                  })
                  ->where(function ($q2) {
                      $q2->whereNull('limite_utilisation')->orWhereColumn('nb_utilisations', '<', 'limite_utilisation');
                  })
                  ->latest();
            }])
            ->orderByDesc('points_fidelite')
            ->paginate(20)
            ->withQueryString();

        // Stats globales
        $totalPoints = Client::where('institut_id', $institutId)->sum('points_fidelite');
        $clientsAvecPoints = Client::where('institut_id', $institutId)->where('points_fidelite', '>', 0)->count();
        $recompensesDistribuees = HistoriquePoints::where('institut_id', $institutId)
            ->where('type', 'recompense')
            ->count();

        // Derniers mouvements
        $derniersMouvements = HistoriquePoints::where('institut_id', $institutId)
            ->with('client')
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.fidelite.index', compact(
            'programme', 'clients', 'totalPoints', 'clientsAvecPoints',
            'recompensesDistribuees', 'derniersMouvements'
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

    public function recompenser(Request $request, Client $client)
    {
        $institutId = $this->institutId();

        abort_unless($client->institut_id === $institutId, 403);

        $programme = ProgrammeFidelite::where('institut_id', $institutId)->first();
        abort_unless($programme && $programme->actif, 403, 'Programme de fidélité non configuré.');
        abort_unless($client->points_fidelite >= $programme->seuil_recompense, 422, 'Points insuffisants.');

        $code = DB::transaction(function () use ($client, $programme, $institutId) {
            // Déduire les points
            $client->decrement('points_fidelite', $programme->seuil_recompense);

            HistoriquePoints::create([
                'institut_id' => $institutId,
                'client_id' => $client->id,
                'points' => -$programme->seuil_recompense,
                'type' => 'recompense',
                'description' => 'Conversion en code de réduction',
            ]);

            // Générer un code de réduction
            $code = CodeReduction::create([
                'institut_id' => $institutId,
                'client_id' => $client->id,
                'code' => 'FID-' . strtoupper(Str::random(6)),
                'description' => 'Récompense fidélité pour ' . $client->nom_complet,
                'type' => $programme->type_recompense,
                'valeur' => $programme->valeur_recompense,
                'date_debut' => now(),
                'date_fin' => now()->addDays(30),
                'limite_utilisation' => 1,
                'actif' => true,
            ]);

            return $code;
        });

        return back()->with('success', "Code {$code->code} généré pour {$client->nom_complet} ({$programme->valeur_recompense}" . ($programme->type_recompense === 'pourcentage' ? '%' : ' FCFA') . " de réduction).");
    }

    public function ajuster(Request $request, Client $client)
    {
        $request->validate([
            'points' => ['required', 'integer', 'not_in:0'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $institutId = $this->institutId();
        abort_unless($client->institut_id === $institutId, 403);

        DB::transaction(function () use ($client, $request, $institutId) {
            $client->increment('points_fidelite', $request->points);

            // Empêcher les points négatifs
            if ($client->fresh()->points_fidelite < 0) {
                $client->update(['points_fidelite' => 0]);
            }

            HistoriquePoints::create([
                'institut_id' => $institutId,
                'client_id' => $client->id,
                'points' => $request->points,
                'type' => 'ajustement',
                'description' => $request->description,
            ]);
        });

        return back()->with('success', 'Points ajustés pour ' . $client->nom_complet . '.');
    }

    public function imprimerCode(CodeReduction $codeReduction)
    {
        $institutId = $this->institutId();
        abort_unless($codeReduction->institut_id === $institutId, 403);

        $codeReduction->load('client');
        $institut = \App\Models\Institut::find($institutId);

        return view('dashboard.fidelite.imprimer-code', compact('codeReduction', 'institut'));
    }
}
