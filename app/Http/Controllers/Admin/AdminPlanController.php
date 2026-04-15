<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanAbonnement;
use Illuminate\Http\Request;

class AdminPlanController extends Controller
{
    public function index()
    {
        $plans = PlanAbonnement::orderBy('ordre')->where('actif', true)->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:50'],
            'slug' => ['required', 'string', 'max:30', 'unique:plans_abonnement,slug'],
            'prix' => ['required', 'integer', 'min:0'],
            'max_employes' => ['nullable', 'integer', 'min:1'],
            'max_instituts' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
            'ordre' => ['required', 'integer', 'min:0'],
        ]);

        $data['actif'] = $request->boolean('actif', true);
        $data['mis_en_avant'] = $request->boolean('mis_en_avant');

        PlanAbonnement::create($data);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan créé.');
    }

    public function update(Request $request, PlanAbonnement $plan)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:50'],
            'prix' => ['required', 'integer', 'min:0'],
            'max_employes' => ['nullable', 'integer', 'min:1'],
            'max_instituts' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
            'ordre' => ['required', 'integer', 'min:0'],
        ]);

        $data['actif'] = $request->boolean('actif');
        $data['mis_en_avant'] = $request->boolean('mis_en_avant');

        $plan->update($data);

        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan mis à jour.');
    }

    public function destroy(PlanAbonnement $plan)
    {
        $plan->update(['actif' => false]);
        return redirect()->route('admin.plans.index')
            ->with('success', 'Plan désactivé.');
    }

    public function featurer(PlanAbonnement $plan)
    {
        // Un seul plan mis en avant à la fois
        PlanAbonnement::where('id', '!=', $plan->id)->update(['mis_en_avant' => false]);
        $plan->update(['mis_en_avant' => true]);

        return redirect()->route('admin.plans.index')
            ->with('success', "'«x{$plan->nom}» est maintenant mis en avant.");
    }
}
