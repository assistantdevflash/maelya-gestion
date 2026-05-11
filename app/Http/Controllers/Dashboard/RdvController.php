<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\RdvAnnuleClient;
use App\Mail\RdvConfirmeClient;
use App\Mail\RdvConfirmeEtablissement;
use App\Models\Client;
use App\Models\Prestation;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class RdvController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index(Request $request)
    {
        $filtre = $request->get('filtre', 'semaine'); // today | semaine | mois | tous
        $statut = $request->get('statut', '');

        $query = RendezVous::with(['client', 'prestations', 'employe'])
            ->orderBy('debut_le', 'asc');

        $query->when($filtre === 'today', fn($q) => $q->whereDate('debut_le', today()))
              ->when($filtre === 'semaine', fn($q) => $q->whereBetween('debut_le', [now()->startOfWeek(), now()->endOfWeek()]))
              ->when($filtre === 'mois', fn($q) => $q->whereBetween('debut_le', [now()->startOfMonth(), now()->endOfMonth()]))
              ->when($statut, fn($q) => $q->where('statut', $statut));

        $rdvs = $query->get()->groupBy(fn($r) => $r->debut_le->toDateString());

        // Prochain RDV si filtre large
        $prochainRdv = RendezVous::with('prestations')
            ->aVenir()
            ->orderBy('debut_le')
            ->first();

        return view('dashboard.rdv.index', compact('rdvs', 'filtre', 'statut', 'prochainRdv'));
    }

    public function create(Request $request)
    {
        $clients     = Client::where('actif', true)->orderBy('prenom')->get();
        $prestations = Prestation::where('actif', true)->with('categorie')->orderBy('nom')->get();
        $employes    = User::where('institut_id', $this->institutId())
                           ->whereIn('role', ['admin', 'employe'])
                           ->where('actif', true)
                           ->orderBy('prenom')
                           ->get();

        $clientPreselectionne = $request->filled('client_id')
            ? Client::find($request->get('client_id'))
            : null;

        return view('dashboard.rdv.create', compact('clients', 'prestations', 'employes', 'clientPreselectionne'));
    }

    public function store(Request $request)
    {
        // Fusionner date + heure en datetime
        if ($request->filled('debut_date') && $request->filled('debut_heure')) {
            $request->merge(['debut_le' => $request->input('debut_date') . ' ' . $request->input('debut_heure') . ':00']);
        }

        $data = $request->validate([
            'client_id'        => ['nullable', 'uuid', 'exists:clients,id'],
            'client_nom'       => ['required', 'string', 'max:100'],
            'client_telephone' => ['nullable', 'string', 'max:30'],
            'client_email'     => ['nullable', 'email', 'max:255'],
            'employe_id'       => ['nullable', 'uuid', 'exists:users,id'],
            'debut_le'         => ['required', 'date', 'after:now'],
            'duree_minutes'    => ['required', 'integer', 'min:5', 'max:480'],
            'statut'           => ['required', 'in:en_attente,confirme,annule,termine'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'prestation_libre' => ['nullable', 'string', 'max:150'],
            'prestations'      => ['nullable', 'array'],
            'prestations.*'    => ['uuid', 'exists:prestations,id'],
            'envoyer_confirmation' => ['nullable', 'boolean'],
        ]);

        $rdv = RendezVous::create(\Arr::except($data, ['prestations', 'envoyer_confirmation']));

        if (!empty($data['prestations'])) {
            $rdv->prestations()->sync($data['prestations']);
        }

        // Notifications établissement
        $user = Auth::user();
        try {
            Mail::to($user->email)->send(new RdvConfirmeEtablissement($rdv));
        } catch (\Throwable $e) { \Log::warning('[RDV Mail Etablissement] ' . $e->getMessage()); }
        try {
            app(PushNotificationService::class)->sendToUser(
                $user,
                '📅 Nouveau RDV ajouté',
                ($rdv->client_nom) . ' — ' . $rdv->debut_le->format('d/m à H\hi'),
                '/dashboard/rdv/' . $rdv->id
            );
        } catch (\Throwable $e) { \Log::warning('[RDV Push] ' . $e->getMessage()); }

        // Mail de confirmation au client
        if ($request->boolean('envoyer_confirmation') && $rdv->client_email) {
            try {
                $rdv->loadMissing('prestations');
                Mail::to($rdv->client_email)->send(new RdvConfirmeClient($rdv));
            } catch (\Throwable $e) { \Log::warning('[RDV Mail Client] ' . $e->getMessage()); }
        }

        return redirect()->route('dashboard.rdv.index')
            ->with('success', 'Rendez-vous créé avec succès.');
    }

    public function show(RendezVous $rdv)
    {
        $rdv->loadMissing(['client', 'prestations', 'employe']);
        return view('dashboard.rdv.show', compact('rdv'));
    }

    public function edit(RendezVous $rdv)
    {
        $rdv->loadMissing(['prestations']);
        $clients     = Client::where('actif', true)->orderBy('prenom')->get();
        $prestations = Prestation::where('actif', true)->with('categorie')->orderBy('nom')->get();
        $employes    = User::where('institut_id', $this->institutId())
                           ->whereIn('role', ['admin', 'employe'])
                           ->where('actif', true)
                           ->orderBy('prenom')
                           ->get();

        return view('dashboard.rdv.edit', compact('rdv', 'clients', 'prestations', 'employes'));
    }

    public function update(Request $request, RendezVous $rdv)
    {
        // Fusionner date + heure en datetime
        if ($request->filled('debut_date') && $request->filled('debut_heure')) {
            $request->merge(['debut_le' => $request->input('debut_date') . ' ' . $request->input('debut_heure') . ':00']);
        }

        $data = $request->validate([
            'client_id'        => ['nullable', 'uuid', 'exists:clients,id'],
            'client_nom'       => ['required', 'string', 'max:100'],
            'client_telephone' => ['nullable', 'string', 'max:30'],
            'client_email'     => ['nullable', 'email', 'max:255'],
            'employe_id'       => ['nullable', 'uuid', 'exists:users,id'],
            'debut_le'         => ['required', 'date'],
            'duree_minutes'    => ['required', 'integer', 'min:5', 'max:480'],
            'statut'           => ['required', 'in:en_attente,confirme,annule,termine'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'prestation_libre' => ['nullable', 'string', 'max:150'],
            'prestations'      => ['nullable', 'array'],
            'prestations.*'    => ['uuid', 'exists:prestations,id'],
        ]);

        $rdv->update(\Arr::except($data, ['prestations']));
        $rdv->prestations()->sync($data['prestations'] ?? []);

        return redirect()->route('dashboard.rdv.show', $rdv)
            ->with('success', 'Rendez-vous mis à jour.');
    }

    public function annuler(RendezVous $rdv)
    {
        $rdv->update(['statut' => 'annule']);
        $rdv->loadMissing('prestations');

        // Mail annulation au client
        if ($rdv->client_email) {
            try {
                Mail::to($rdv->client_email)->send(new RdvAnnuleClient($rdv));
            } catch (\Throwable $e) { \Log::warning('[RDV Annulation Mail] ' . $e->getMessage()); }
        }

        return back()->with('success', 'Rendez-vous annulé.');
    }

    public function terminer(RendezVous $rdv)
    {
        $rdv->update(['statut' => 'termine']);
        return back()->with('success', 'Rendez-vous marqué comme terminé.');
    }
}
