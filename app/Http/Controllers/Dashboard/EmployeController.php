<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class EmployeController extends Controller
{
    public function index()
    {
        $user      = Auth::user();
        $institutId = $user->currentInstitutId();

        $employes = User::where('institut_id', $institutId)
            ->where('role', 'employe')
            ->orderBy('prenom')
            ->paginate(20);

        $abonnement  = $user->abonnementActif;
        $maxEmployes = $abonnement?->plan?->max_employes; // null = illimité
        $nbEmployes  = User::where('institut_id', $institutId)->where('role', 'employe')->count();
        $limitAtteinte = $maxEmployes !== null && $nbEmployes >= $maxEmployes;

        return view('dashboard.employes.index', compact('employes', 'maxEmployes', 'nbEmployes', 'limitAtteinte'));
    }

    public function create()
    {
        return view('dashboard.employes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'prenom' => ['required', 'string', 'max:50'],
            'nom_famille' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
        ]);

        // Vérifier la limite d'employés selon le plan
        $user = Auth::user();
        $institutId = $user->currentInstitutId();
        $abonnement = $user->abonnementActif;
        if ($abonnement && $abonnement->plan && $abonnement->plan->max_employes !== null) {
            $nbEmployes = User::where('institut_id', $institutId)->where('role', 'employe')->count();
            if ($nbEmployes >= $abonnement->plan->max_employes) {
                return back()->with('error', "Limite atteinte : votre plan autorise {$abonnement->plan->max_employes} employé(s). Passez au plan supérieur.");
            }
        }

        User::create([
            'institut_id' => $institutId,
            'prenom' => $request->prenom,
            'nom_famille' => $request->nom_famille,
            'name' => $request->prenom . ' ' . $request->nom_famille,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
            'role' => 'employe',
            'actif' => true,
        ]);

        return redirect()->route('dashboard.employes.index')
            ->with('success', 'Compte employé créé.');
    }

    public function edit(User $employe)
    {
        return view('dashboard.employes.edit', compact('employe'));
    }

    public function update(Request $request, User $employe)
    {
        $request->validate([
            'prenom' => ['required', 'string', 'max:50'],
            'nom_famille' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'unique:users,email,' . $employe->id],
            'telephone' => ['nullable', 'string', 'max:30'],
        ]);

        $employe->update([
            'prenom' => $request->prenom,
            'nom_famille' => $request->nom_famille,
            'name' => $request->prenom . ' ' . $request->nom_famille,
            'email' => $request->email,
            'telephone' => $request->telephone,
        ]);

        return redirect()->route('dashboard.employes.index')
            ->with('success', 'Employé mis à jour.');
    }

    public function destroy(User $employe)
    {
        $employe->delete();
        return redirect()->route('dashboard.employes.index')
            ->with('success', 'Compte employé supprimé.');
    }

    public function toggle(User $employe)
    {
        $employe->update(['actif' => !$employe->actif]);
        return back()->with('success', $employe->actif ? 'Compte activé.' : 'Compte désactivé.');
    }
}
