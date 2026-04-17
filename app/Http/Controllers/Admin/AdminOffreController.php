<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OffrePromotionnelle;
use App\Models\PlanAbonnement;
use Illuminate\Http\Request;

class AdminOffreController extends Controller
{
    public function index()
    {
        $offres = OffrePromotionnelle::orderByDesc('priorite')
            ->orderByDesc('created_at')
            ->get();

        $plans = PlanAbonnement::where('actif', true)->orderBy('ordre')->get();

        return view('admin.offres.index', compact('offres', 'plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type_reduction' => ['required', 'in:pourcentage,montant_fixe'],
            'valeur_reduction' => ['required', 'integer', 'min:1'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'plans_concernes' => ['nullable', 'array'],
            'plans_concernes.*' => ['exists:plans_abonnement,id'],
            'periodes_concernees' => ['nullable', 'array'],
            'periodes_concernees.*' => ['in:mensuel,annuel,triennal'],
            'badge_texte' => ['required', 'string', 'max:80'],
            'badge_couleur' => ['required', 'string', 'max:20'],
            'priorite' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $data['actif'] = $request->boolean('actif', true);

        // Si aucun plan sélectionné, null = tous les plans
        if (empty($data['plans_concernes'])) {
            $data['plans_concernes'] = null;
        }
        if (empty($data['periodes_concernees'])) {
            $data['periodes_concernees'] = null;
        }

        OffrePromotionnelle::create($data);

        return redirect()->route('admin.offres.index')
            ->with('success', 'Offre promotionnelle créée.');
    }

    public function update(Request $request, OffrePromotionnelle $offre)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type_reduction' => ['required', 'in:pourcentage,montant_fixe'],
            'valeur_reduction' => ['required', 'integer', 'min:1'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'plans_concernes' => ['nullable', 'array'],
            'plans_concernes.*' => ['exists:plans_abonnement,id'],
            'periodes_concernees' => ['nullable', 'array'],
            'periodes_concernees.*' => ['in:mensuel,annuel,triennal'],
            'badge_texte' => ['required', 'string', 'max:80'],
            'badge_couleur' => ['required', 'string', 'max:20'],
            'priorite' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $data['actif'] = $request->boolean('actif');

        if (empty($data['plans_concernes'])) {
            $data['plans_concernes'] = null;
        }
        if (empty($data['periodes_concernees'])) {
            $data['periodes_concernees'] = null;
        }

        $offre->update($data);

        return redirect()->route('admin.offres.index')
            ->with('success', 'Offre mise à jour.');
    }

    public function toggleActif(OffrePromotionnelle $offre)
    {
        $offre->update(['actif' => !$offre->actif]);

        return redirect()->route('admin.offres.index')
            ->with('success', $offre->actif ? 'Offre activée.' : 'Offre désactivée.');
    }

    public function destroy(OffrePromotionnelle $offre)
    {
        $offre->delete();

        return redirect()->route('admin.offres.index')
            ->with('success', 'Offre supprimée.');
    }
}
