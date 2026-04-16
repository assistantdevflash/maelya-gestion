<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CodeReduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CodeReductionController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index()
    {
        $codes = CodeReduction::where('institut_id', $this->institutId())
            ->with('client')
            ->orderByDesc('created_at')
            ->get();

        $clients = Client::where('institut_id', $this->institutId())
            ->where('actif', true)
            ->orderBy('prenom')
            ->get();

        $stats = [
            'total'       => $codes->count(),
            'actifs'      => $codes->filter(fn($c) => $c->statut() === 'actif')->count(),
            'utilisations'=> $codes->sum('nb_utilisations'),
        ];

        return view('dashboard.codes-reduction.index', compact('codes', 'stats', 'clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'              => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_\-]+$/'],
            'description'       => ['nullable', 'string', 'max:255'],
            'type'              => ['required', 'in:pourcentage,montant_fixe'],
            'valeur'            => ['required', 'integer', 'min:1'],
            'client_id'         => ['nullable', 'string', 'exists:clients,id'],
            'montant_minimum'   => ['nullable', 'integer', 'min:0'],
            'date_debut'        => ['nullable', 'date'],
            'date_fin'          => ['nullable', 'date', 'after_or_equal:date_debut'],
            'limite_utilisation'=> ['nullable', 'integer', 'min:1'],
        ], [
            'code.regex'     => 'Le code ne peut contenir que des lettres majuscules, chiffres, tirets et underscores.',
            'valeur.min'     => 'La valeur doit être au moins 1.',
            'type.in'        => 'Type invalide.',
            'date_fin.after_or_equal' => 'La date de fin doit être après la date de début.',
        ]);

        // Unicité du code pour cet institut (insensible à la casse)
        $codeUp = strtoupper($data['code']);
        $exists = CodeReduction::where('institut_id', $this->institutId())
            ->whereRaw('UPPER(code) = ?', [$codeUp])
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'Ce code existe déjà pour votre institut.'])->withInput();
        }

        // Validation supplémentaire pour pourcentage
        if ($data['type'] === 'pourcentage' && $data['valeur'] > 100) {
            return back()->withErrors(['valeur' => 'Un pourcentage ne peut pas dépasser 100%.'])->withInput();
        }

        CodeReduction::create([
            ...$data,
            'code'        => $codeUp,
            'institut_id' => $this->institutId(),
        ]);

        return back()->with('success', 'Code de réduction créé.');
    }

    public function print(CodeReduction $codeReduction)
    {
        abort_unless($codeReduction->institut_id === $this->institutId(), 403);

        return view('dashboard.codes-reduction.print', [
            'code' => $codeReduction->load('client', 'institut'),
        ]);
    }

    public function toggle(CodeReduction $codeReduction)
    {
        // S'assurer que le code appartient à l'institut
        abort_unless($codeReduction->institut_id === $this->institutId(), 403);

        $codeReduction->update(['actif' => !$codeReduction->actif]);

        return back()->with('success', $codeReduction->actif ? 'Code activé.' : 'Code désactivé.');
    }

    public function destroy(CodeReduction $codeReduction)
    {
        abort_unless($codeReduction->institut_id === $this->institutId(), 403);

        $codeReduction->delete();

        return back()->with('success', 'Code supprimé.');
    }

    /**
     * Validation AJAX/Livewire — appelée depuis la caisse pour vérifier un code.
     */
    public function valider(Request $request)
    {
        $request->validate([
            'code'      => ['required', 'string'],
            'total'     => ['required', 'integer', 'min:0'],
            'client_id' => ['nullable', 'string'],
        ]);

        $code = CodeReduction::where('institut_id', $this->institutId())
            ->whereRaw('UPPER(code) = ?', [strtoupper($request->code)])
            ->first();

        if (!$code) {
            return response()->json(['error' => 'Code de réduction invalide.'], 422);
        }

        $erreur = $code->validerPourTotal((int) $request->total, $request->client_id ?: null);
        if ($erreur) {
            return response()->json(['error' => $erreur], 422);
        }

        $remise = $code->calculerRemise((int) $request->total);

        return response()->json([
            'id'          => $code->id,
            'code'        => $code->code,
            'type'        => $code->type,
            'valeur'      => $code->valeur,
            'remise'      => $remise,
            'description' => $code->description,
        ]);
    }
}
