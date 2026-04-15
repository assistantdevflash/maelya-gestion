<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\User;
use App\Models\PlanAbonnement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class InscriptionController extends Controller
{
    public function index()
    {
        $plans = PlanAbonnement::where('actif', true)->orderBy('ordre')->get();
        return view('auth.inscription', compact('plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom_institut' => ['required', 'string', 'min:2', 'max:100'],
            'type_institut' => ['required', 'in:salon_coiffure,institut_beaute,nail_bar,spa,barbier,autre'],
            'ville' => ['required', 'string', 'max:100'],
            'telephone_institut' => ['nullable', 'string', 'max:30'],
            'prenom' => ['required', 'string', 'min:2', 'max:50'],
            'nom_famille' => ['required', 'string', 'min:2', 'max:50'],
            'email' => ['required', 'email', 'unique:users,email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
            'cgu' => ['required', 'accepted'],
        ], [
            'nom_institut.required' => "Le nom de l'institut est requis.",
            'type_institut.required' => "Le type d'institut est requis.",
            'ville.required' => 'La ville est requise.',
            'prenom.required' => 'Le prénom est requis.',
            'nom_famille.required' => 'Le nom est requis.',
            'email.required' => "L'email est requis.",
            'email.unique' => 'Un compte existe déjà avec cet email.',
            'password.required' => 'Le mot de passe est requis.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
            'cgu.required' => 'Vous devez accepter les CGU.',
        ]);

        DB::transaction(function () use ($request) {
            $institut = Institut::create([
                'nom' => $request->nom_institut,
                'email' => $request->email,
                'telephone' => $request->telephone_institut,
                'ville' => $request->ville,
                'type' => $request->type_institut,
                'actif' => true,
            ]);

            $user = User::create([
                'institut_id' => $institut->id,
                'prenom' => $request->prenom,
                'nom_famille' => $request->nom_famille,
                'name' => $request->prenom . ' ' . $request->nom_famille,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'password' => Hash::make($request->password),
                'role' => 'admin',
                'actif' => true,
            ]);

            // Lier le propriétaire à l'institut
            $institut->forceFill(['proprietaire_id' => $user->id])->save();

            Auth::login($user);

            // ── Plan d'essai gratuit 14 jours (lié au user) ──────────────────────
            $planEssai = PlanAbonnement::where('slug', 'essai')->first();
            if ($planEssai) {
                Abonnement::create([
                    'user_id'   => $user->id,
                    'plan_id'   => $planEssai->id,
                    'montant'   => 0,
                    'periode'   => 'mensuel',
                    'statut'    => 'actif',
                    'reference_transfert' => 'ESSAI-' . strtoupper(substr(md5($user->id), 0, 8)),
                    'debut_le'  => now()->toDateString(),
                    'expire_le' => now()->addDays(14)->toDateString(),
                ]);
            }
        });

        return redirect()->route('dashboard.index')
            ->with('success', "Bienvenue ! Votre essai gratuit de 14 jours est activé. 🎉");
    }
}
