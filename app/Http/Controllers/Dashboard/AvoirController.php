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
        $avoirs = Avoir::where('institut_id', $this->institutId())
            ->with(['vente', 'client', 'user', 'codeReduction'])
            ->latest()
            ->paginate(20);

        return view('dashboard.avoirs.index', compact('avoirs'));
    }

    public function store(Request $request, Vente $vente, AvoirService $service)
    {
        abort_unless($vente->institut_id === $this->institutId(), 404);

        $data = $request->validate([
            'montant' => ['required', 'integer', 'min:100', 'max:' . max(100, (int) $vente->total)],
            'motif'   => ['nullable', 'string', 'max:255'],
        ]);

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
