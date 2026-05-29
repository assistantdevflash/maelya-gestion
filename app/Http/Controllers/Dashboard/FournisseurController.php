<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FournisseurController extends Controller
{
    public function index()
    {
        abort_unless(Auth::user()->isAdmin(), 403);
        $fournisseurs = Fournisseur::orderBy('nom')->paginate(30);
        return view('dashboard.fournisseurs.index', compact('fournisseurs'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->isAdmin(), 403);
        $data = $request->validate([
            'nom'               => ['required', 'string', 'max:150'],
            'telephone'         => ['nullable', 'string', 'max:30'],
            'email'             => ['nullable', 'email', 'max:255'],
            'adresse'           => ['nullable', 'string', 'max:255'],
            'contact_principal' => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string'],
        ]);
        Fournisseur::create($data);
        return back()->with('success', 'Fournisseur créé.');
    }

    public function update(Request $request, Fournisseur $fournisseur)
    {
        abort_unless(Auth::user()->isAdmin(), 403);
        $data = $request->validate([
            'nom'               => ['required', 'string', 'max:150'],
            'telephone'         => ['nullable', 'string', 'max:30'],
            'email'             => ['nullable', 'email', 'max:255'],
            'adresse'           => ['nullable', 'string', 'max:255'],
            'contact_principal' => ['nullable', 'string', 'max:100'],
            'notes'             => ['nullable', 'string'],
            'actif'             => ['nullable', 'boolean'],
        ]);
        $data['actif'] = $request->boolean('actif', true);
        $fournisseur->update($data);
        return back()->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Fournisseur $fournisseur)
    {
        abort_unless(Auth::user()->isAdmin(), 403);
        $fournisseur->delete();
        return back()->with('success', 'Fournisseur supprimé.');
    }
}
