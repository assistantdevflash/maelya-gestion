<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CaisseBrouillon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaisseBrouillonController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index()
    {
        $brouillons = CaisseBrouillon::where('institut_id', $this->institutId())
            ->with(['user:id,prenom,nom_famille', 'client:id,prenom,nom'])
            ->latest()
            ->get();

        return view('dashboard.caisse.brouillons', compact('brouillons'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'panier'          => ['required', 'array', 'min:1'],
            'client_id'       => ['nullable', 'string'],
            'libelle'         => ['nullable', 'string', 'max:80'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'total_indicatif' => ['nullable', 'integer', 'min:0'],
        ]);

        $brouillon = CaisseBrouillon::create([
            'institut_id'     => $this->institutId(),
            'user_id'         => Auth::id(),
            'client_id'       => $data['client_id'] ?? null,
            'libelle'         => $data['libelle'] ?? null,
            'panier'          => $data['panier'],
            'total_indicatif' => $data['total_indicatif'] ?? 0,
            'notes'           => $data['notes'] ?? null,
        ]);

        return response()->json(['ok' => true, 'id' => $brouillon->id]);
    }

    public function destroy(CaisseBrouillon $brouillon)
    {
        abort_unless($brouillon->institut_id === $this->institutId(), 403);
        $brouillon->delete();
        return back()->with('success', 'Brouillon supprimé.');
    }
}
