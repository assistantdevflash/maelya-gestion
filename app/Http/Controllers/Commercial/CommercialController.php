<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Models\CommercialCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CommercialController extends Controller
{
    private function getProfil()
    {
        return Auth::user()->commercialProfile;
    }

    public function dashboard()
    {
        $profil = $this->getProfil();

        if (!$profil) {
            abort(403, 'Profil commercial introuvable.');
        }

        $profil->loadCount(['parrainages', 'commissions']);

        $totalGagne   = $profil->totalGagne();
        $totalEnAttente = $profil->totalEnAttente();

        $derniersParrainages = $profil->parrainages()
            ->with('proprietaire.institut')
            ->latest()
            ->limit(5)
            ->get();

        $dernieresCommissions = $profil->commissions()
            ->with('parrainage.proprietaire.institut', 'abonnement.plan')
            ->latest()
            ->limit(5)
            ->get();

        $config = \DB::table('commercial_config')->first();

        return view('commercial.dashboard', compact(
            'profil', 'totalGagne', 'totalEnAttente',
            'derniersParrainages', 'dernieresCommissions', 'config'
        ));
    }

    public function parrainages()
    {
        $profil = $this->getProfil();
        if (!$profil) abort(403);

        $parrainages = $profil->parrainages()
            ->with('proprietaire.institut', 'commissions')
            ->latest()
            ->paginate(20);

        return view('commercial.parrainages', compact('profil', 'parrainages'));
    }

    public function commissions(Request $request)
    {
        $profil = $this->getProfil();
        if (!$profil) abort(403);

        $query = $profil->commissions()
            ->with('parrainage.proprietaire.institut', 'abonnement.plan')
            ->latest();

        if ($request->statut && in_array($request->statut, ['en_attente', 'payee'])) {
            $query->where('statut', $request->statut);
        }

        $commissions = $query->paginate(30)->withQueryString();

        $totalGagne     = $profil->totalGagne();
        $totalEnAttente = $profil->totalEnAttente();

        return view('commercial.commissions', compact(
            'profil', 'commissions', 'totalGagne', 'totalEnAttente'
        ));
    }

    public function guide()
    {
        return view('commercial.guide');
    }

    public function guidePdf()
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('commercial.guide-pdf')
            ->setPaper('a4', 'portrait');

        return $pdf->download('guide-porte-a-porte-maelya.pdf');
    }

    public function profil()
    {
        $profil = $this->getProfil();
        if (!$profil) abort(403);

        $profil->loadCount(['parrainages', 'commissions']);
        $totalGagne     = $profil->totalGagne();
        $totalEnAttente = $profil->totalEnAttente();
        $config         = \DB::table('commercial_config')->first();

        return view('commercial.profil', compact(
            'profil', 'totalGagne', 'totalEnAttente', 'config'
        ));
    }

    public function updateProfil(Request $request)
    {
        $user   = Auth::user();
        $profil = $user->commercialProfile;

        $validated = $request->validate([
            'prenom'      => 'required|string|max:100',
            'nom_famille' => 'required|string|max:100',
            'email'       => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'telephone'   => 'nullable|string|max:30',
        ]);

        $user->update([
            'prenom'      => $validated['prenom'],
            'nom_famille' => $validated['nom_famille'],
            'name'        => $validated['prenom'] . ' ' . $validated['nom_famille'],
            'email'       => $validated['email'],
            'telephone'   => $validated['telephone'],
        ]);

        if ($profil) {
            $profil->update(['telephone' => $validated['telephone']]);
        }

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()
                ->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.'])
                ->withInput()
                ->with('tab', 'password');
        }

        Auth::user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success_password', 'Mot de passe modifié avec succès.');
    }
}
