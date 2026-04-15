<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminInstitutController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('q');

        // IDs des propriétaires ayant le plan demandé
        $planFilter = $request->input('plan');
        $ownerIds = null;
        if ($planFilter === '__aucun__') {
            // Propriétaires sans abonnement actif
            $avecAbo = Abonnement::where('statut', 'actif')->pluck('user_id');
            $ownerIds = User::whereNotIn('id', $avecAbo)->pluck('id');
        } elseif ($planFilter) {
            $ownerIds = Abonnement::where('statut', 'actif')
                ->whereHas('plan', fn($q) => $q->where('slug', $planFilter))
                ->pluck('user_id');
        }

        $paginator = Institut::with(['proprietaire'])
            ->withCount('users')
            ->when($search, fn($q) => $q->where('nom', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('ville', 'like', "%{$search}%"))
            ->when($request->status, fn($q, $s) => $q->where('actif', $s === 'actif'))
            ->when($ownerIds !== null, fn($q) => $q->whereIn('proprietaire_id', $ownerIds))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $grouped = $paginator->getCollection()->groupBy('proprietaire_id');
        $plans   = PlanAbonnement::where('actif', true)->orderBy('ordre')->get();

        return view('admin.instituts.index', compact('paginator', 'grouped', 'search', 'plans'));
    }

    public function show(Institut $institut)
    {
        $institut->load('users', 'proprietaire');
        // L'owner EST le proprietaire de l'institut (fonctionne pour primaire et secondaire)
        $owner = $institut->proprietaire;
        $abonnementActif  = $owner?->abonnementActif;
        $abonnementSursis = (!$abonnementActif && $owner) ? $owner->abonnementEnSursis() : null;
        $plans = PlanAbonnement::where('actif', true)->orderBy('ordre')->get();
        $historique = $owner ? Abonnement::where('user_id', $owner->id)->with('plan')->latest()->get() : collect();
        return view('admin.instituts.show', compact('institut', 'owner', 'abonnementActif', 'abonnementSursis', 'plans', 'historique'));
    }

    public function update(Request $request, Institut $institut)
    {
        $request->validate(['actif' => ['required', 'boolean']]);
        $institut->update(['actif' => $request->actif]);
        return back()->with('success', 'Institut mis à jour.');
    }

    public function toggle(Institut $institut)
    {
        $institut->update(['actif' => !$institut->actif]);
        return back()->with('success', $institut->actif ? 'Institut activé.' : 'Institut désactivé.');
    }

    public function offrirAbonnement(Request $request, Institut $institut)
    {
        $request->validate([
            'plan_id' => ['required', 'uuid'],
            'jours' => ['required', 'integer', 'min:1', 'max:1095'],
        ]);

        $owner = $institut->users->firstWhere('role', 'admin');
        if (!$owner) {
            return back()->with('error', 'Aucun propriétaire trouvé pour cet institut.');
        }

        $plan = PlanAbonnement::findOrFail($request->plan_id);

        // Expirer les abonnements précédents
        Abonnement::where('user_id', $owner->id)
            ->where('statut', 'actif')
            ->update(['statut' => 'expire']);

        Abonnement::create([
            'user_id' => $owner->id,
            'plan_id' => $plan->id,
            'montant' => 0,
            'periode' => 'mensuel',
            'reference_transfert' => 'OFFERT-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'statut' => 'actif',
            'debut_le' => now()->toDateString(),
            'expire_le' => now()->addDays($request->jours)->toDateString(),
            'valide_par' => auth()->id(),
            'notes_admin' => 'Abonnement offert par l\'administrateur.',
        ]);

        return back()->with('success', "Accès offert pour {$request->jours} jours.");
    }

    public function destroy(Institut $institut)
    {
        $nom = $institut->nom;

        DB::transaction(function () use ($institut) {
            $userIds = $institut->users()->pluck('id');

            // Supprimer les abonnements des utilisateurs liés
            Abonnement::whereIn('user_id', $userIds)->delete();

            // Supprimer les utilisateurs liés
            User::whereIn('id', $userIds)->delete();

            // Supprimer l'institut (les FK en cascade couvrent clients, ventes, dépenses, etc.)
            $institut->delete();
        });

        return redirect()->route('admin.instituts.index')
            ->with('success', "Établissement « {$nom} » supprimé définitivement.");
    }
}
