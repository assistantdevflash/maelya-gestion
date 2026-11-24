<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Avoir;
use App\Models\Vente;
use App\Services\AvoirService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvoirController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index()
    {
        // Rebranché sur l'écran unifié Remises & Avoirs (onglet Avoirs).
        return redirect()->route('dashboard.codes-reduction.index', ['tab' => 'avoirs']);
    }

    /**
     * Export CSV des avoirs.
     */
    public function export()
    {
        $avoirs = Avoir::where('institut_id', $this->institutId())
            ->with(['client', 'vente', 'codeReduction'])
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($avoirs) {
            $sortie = fopen('php://output', 'w');
            fputcsv($sortie, ['Numéro', 'Date', 'Client', 'Vente', 'Montant', 'Code lié', 'Motif', 'Statut'], ';');

            foreach ($avoirs as $avoir) {
                fputcsv($sortie, [
                    $avoir->numero,
                    $avoir->created_at?->format('d/m/Y H:i'),
                    $avoir->client ? $avoir->client->prenom . ' ' . $avoir->client->nom : 'Client de passage',
                    $avoir->vente?->numero ?? ($avoir->vente ? '#' . substr($avoir->vente->id, 0, 8) : ''),
                    $avoir->montant,
                    $avoir->codeReduction?->code ?? '',
                    $avoir->motif,
                    $avoir->statutEffectif(),
                ], ';');
            }

            fclose($sortie);
        }, 'avoirs-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request, Vente $vente, AvoirService $service)
    {
        abort_unless($vente->institut_id === $this->institutId(), 404);

        $data = $request->validate([
            'montant' => ['required', 'integer', 'min:100', 'max:' . max(100, (int) $vente->total)],
            'motif'   => ['nullable', 'string', 'max:255'],
        ]);

        // Contrôle cumulé : plusieurs avoirs successifs ne peuvent pas
        // dépasser le montant de la vente d'origine.
        $dejaEmis   = (int) Avoir::where('vente_id', $vente->id)->sum('montant');
        $totalVente = (int) $vente->total;

        if ($dejaEmis + (int) $data['montant'] > $totalVente) {
            return back()->withErrors([
                'montant' => 'Le total des avoirs (' . number_format($dejaEmis, 0, ',', ' ')
                    . ' F) dépasse déjà le montant de la vente (' . number_format($totalVente, 0, ',', ' ') . ' F).',
            ])->withInput();
        }

        $avoir = $service->creer([
            'institut_id' => $this->institutId(),
            'vente_id'    => $vente->id,
            'client_id'   => $vente->client_id,
            'user_id'     => Auth::id(),
            'montant'     => $data['montant'],
            'motif'       => $data['motif'] ?? null,
        ]);

        return back()->with('success', "Avoir {$avoir->numero} créé — code : {$avoir->codeReduction->code}");
    }

    /**
     * Marque un avoir comme utilisé manuellement.
     */
    public function marquerUtilise(Avoir $avoir)
    {
        abort_unless($avoir->institut_id === $this->institutId(), 404);

        if ($avoir->statut !== 'emis') {
            return back()->with('error', 'Cet avoir a déjà été marqué comme utilisé.');
        }

        $avoir->update(['statut' => 'utilise']);

        // Marque aussi le code de réduction lié comme épuisé
        if ($avoir->codeReduction) {
            $avoir->codeReduction->update(['actif' => false]);
        }

        return back()->with('success', "Avoir {$avoir->numero} marqué comme utilisé.");
    }
}
