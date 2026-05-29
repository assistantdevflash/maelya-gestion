<?php

namespace App\Http\Controllers;

use App\Models\Institut;
use App\Models\Prestation;
use App\Models\RendezVous;
use Illuminate\Http\Request;

class VitrineController extends Controller
{
    public function show(string $slug)
    {
        $institut = Institut::where('slug', $slug)
            ->where('vitrine_active', true)
            ->where('actif', true)
            ->firstOrFail();

        $prestations = $institut->prestations()
            ->where('actif', true)
            ->with('categorie')
            ->orderBy('nom')
            ->get()
            ->groupBy(fn($p) => $p->categorie?->nom ?? 'Autres');

        $produits = $institut->produits()
            ->where('actif', true)
            ->with('categorie')
            ->orderBy('nom')
            ->get()
            ->groupBy(fn($p) => $p->categorie?->nom ?? 'Autres');

        $prestationsFlat = $institut->prestations()->where('actif', true)->orderBy('nom')->get(['id', 'nom', 'prix', 'duree']);

        return view('vitrine.show', compact('institut', 'prestations', 'produits', 'prestationsFlat'));
    }

    public function reserver(Request $request, string $slug)
    {
        $institut = Institut::where('slug', $slug)
            ->where('vitrine_active', true)
            ->where('actif', true)
            ->firstOrFail();

        $data = $request->validate([
            'client_nom'       => ['required', 'string', 'max:150'],
            'client_telephone' => ['required', 'string', 'max:30'],
            'client_email'     => ['nullable', 'email', 'max:255'],
            'prestation_id'    => ['required', 'uuid'],
            'debut_le'         => ['required', 'date', 'after:now'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $prestation = Prestation::where('id', $data['prestation_id'])
            ->where('institut_id', $institut->id)
            ->where('actif', true)
            ->firstOrFail();

        $rdv = RendezVous::create([
            'institut_id'      => $institut->id,
            'client_nom'       => $data['client_nom'],
            'client_telephone' => $data['client_telephone'],
            'client_email'     => $data['client_email'] ?? null,
            'debut_le'         => $data['debut_le'],
            'duree_minutes'    => $prestation->duree ?? 30,
            'statut'           => 'en_attente',
            'notes'            => $data['notes'] ?? null,
        ]);

        $rdv->prestations()->attach($prestation->id);

        return back()->with('success', 'Votre demande de rendez-vous a bien été enregistrée. Nous vous recontacterons pour confirmation.');
    }
}
