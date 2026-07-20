<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Institut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MesInstitutsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Tous les instituts dont l'utilisateur est propriétaire
        $instituts = Institut::where('proprietaire_id', $user->id)
            ->withCount('users')
            ->orderBy('nom')
            ->get();

        // Inclure l'institut principal s'il n'est pas déjà dans la liste
        $primaryInstitut = $user->institut;
        if ($primaryInstitut && !$instituts->contains('id', $primaryInstitut->id)) {
            $instituts = $instituts->prepend($primaryInstitut->loadCount('users'));
        }

        $abonnement = $user->abonnementActif;
        $plan = $abonnement?->plan;
        $maxInstituts = $plan?->max_instituts; // null = illimité (Entreprise)
        $peutCreer = $maxInstituts === null || $instituts->count() < $maxInstituts;
        $currentInstitutId = $user->currentInstitutId();

        return view('dashboard.mes-instituts.index', compact(
            'instituts', 'peutCreer', 'maxInstituts', 'currentInstitutId', 'abonnement', 'plan'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Vérifier que le plan autorise un nouvel institut
        $abonnement = $user->abonnementActif;
        $plan = $abonnement?->plan;
        $maxInstituts = $plan?->max_instituts;

        $nbActuels = Institut::where('proprietaire_id', $user->id)->count();
        // Compter aussi l'institut principal
        if ($user->institut && !Institut::where('proprietaire_id', $user->id)->where('id', $user->institut_id)->exists()) {
            $nbActuels++;
        }

        if ($maxInstituts !== null && $nbActuels >= $maxInstituts) {
            return back()->withErrors(['general' => 'Votre plan ne permet pas de créer plus d\'instituts.']);
        }

        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:100'],
            'ville'     => ['nullable', 'string', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:150'],
            'type'      => ['required', 'string', 'in:salon_coiffure,institut_beaute,barbier,centre_esthetique,boutique_mode,imprimerie,lavage_auto,pressing,business_center,depot_gaz,commerce,informatique_telephonie,autre'],
            'logo'      => ['nullable', 'image', 'max:2048'],
        ]);

        // Gérer l'upload du logo
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $institut = Institut::create([
            ...$data,
            'type'            => $data['type'],
            'actif'           => true,
        ]);

        $institut->forceFill(['proprietaire_id' => $user->id])->save();

        // Basculer automatiquement vers le nouvel institut
        session(['current_institut_id' => $institut->id]);

        return redirect()->route('dashboard.mes-instituts.index')
            ->with('success', "Institut \"{$institut->nom}\" créé. Vous gérez maintenant cet institut.");
    }

    public function update(Request $request, Institut $institut)
    {
        $user = Auth::user();
        $aAcces = $institut->proprietaire_id === $user->id || $user->institut_id === $institut->id;
        abort_unless($aAcces, 403, 'Accès refusé.');

        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:100'],
            'ville'     => ['nullable', 'string', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:150'],
            'type'      => ['required', 'string', 'in:salon_coiffure,institut_beaute,barbier,centre_esthetique,boutique_mode,imprimerie,lavage_auto,pressing,business_center,depot_gaz,commerce,autre'],
        ]);

        $institut->update($data);

        return redirect()->route('dashboard.mes-instituts.index')
            ->with('success', "Fiche de \"" . $data['nom'] . "\" mise à jour.");
    }

    public function updateLogo(Request $request, Institut $institut)
    {
        $user = Auth::user();
        $aAcces = $institut->proprietaire_id === $user->id || $user->institut_id === $institut->id;
        abort_unless($aAcces, 403, 'Accès refusé.');

        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        // Supprimer l'ancien logo si existe
        if ($institut->logo && \Storage::disk('public')->exists($institut->logo)) {
            \Storage::disk('public')->delete($institut->logo);
        }

        $logo = $request->file('logo')->store('logos', 'public');
        $institut->update(['logo' => $logo]);

        return redirect()->route('dashboard.mes-instituts.index')
            ->with('success', "Logo de \"{$institut->nom}\" mis à jour.");
    }

    public function toggleVitrine(Institut $institut)
    {
        $user = Auth::user();
        $aAcces = $institut->proprietaire_id === $user->id || $user->institut_id === $institut->id;
        abort_unless($aAcces, 403, 'Accès refusé.');

        $institut->update(['vitrine_active' => !$institut->vitrine_active]);

        // Si on désactive la vitrine, on désactive aussi la réservation en ligne
        if (!$institut->vitrine_active) {
            $institut->update(['reservation_en_ligne' => false]);
        }

        $msg = $institut->vitrine_active
            ? "Vitrine publique activée pour \"{$institut->nom}\"."
            : "Vitrine publique désactivée pour \"{$institut->nom}\".";

        return redirect()->route('dashboard.mes-instituts.index')->with('success', $msg);
    }

    public function toggleReservation(Institut $institut)
    {
        $user = Auth::user();
        $aAcces = $institut->proprietaire_id === $user->id || $user->institut_id === $institut->id;
        abort_unless($aAcces, 403, 'Accès refusé.');

        // Impossible d'activer la réservation si la vitrine est désactivée
        if (!$institut->vitrine_active && !$institut->reservation_en_ligne) {
            return redirect()->route('dashboard.mes-instituts.index')
                ->with('error', 'Activez d\'abord la page vitrine avant d\'activer la réservation en ligne.');
        }

        $institut->update(['reservation_en_ligne' => !$institut->reservation_en_ligne]);

        $msg = $institut->reservation_en_ligne
            ? "Réservation en ligne activée pour \"{$institut->nom}\"."
            : "Réservation en ligne désactivée pour \"{$institut->nom}\".";

        return redirect()->route('dashboard.mes-instituts.index')->with('success', $msg);
    }

    public function switch(Institut $institut)
    {
        $user = Auth::user();

        // Sécurité : vérifier que l'utilisateur peut accéder à cet institut
        $aAcces = $institut->proprietaire_id === $user->id
            || $user->institut_id === $institut->id;

        abort_unless($aAcces, 403, 'Vous n\'avez pas accès à cet institut.');

        // Vérifier que l'institut est actif
        abort_unless($institut->actif, 404, 'Cet institut n\'est pas disponible.');

        session(['current_institut_id' => $institut->id]);

        return redirect()->back();
    }
}
