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
        $isEmploye = Auth::user()->isEmploye();

        $query = RendezVous::with(['client', 'prestations', 'employe'])
            ->orderBy('debut_le', 'asc');

        // Les employés ne voient que leurs RDV assignés
        if ($isEmploye) {
            $query->where('employe_id', Auth::id());
        }

        $query->when($filtre === 'today', fn($q) => $q->whereDate('debut_le', today()))
              ->when($filtre === 'semaine', fn($q) => $q->whereBetween('debut_le', [now()->startOfWeek(), now()->endOfWeek()]))
              ->when($filtre === 'mois', fn($q) => $q->whereBetween('debut_le', [now()->startOfMonth(), now()->endOfMonth()]))
              ->when($statut, fn($q) => $q->where('statut', $statut));

        $rdvs = $query->limit($filtre === 'tous' ? 300 : 500)->get()->groupBy(fn($r) => $r->debut_le->toDateString());

        // Prochain RDV si filtre large
        $prochainQuery = RendezVous::with('prestations')->aVenir()->orderBy('debut_le');
        if ($isEmploye) {
            $prochainQuery->where('employe_id', Auth::id());
        }
        $prochainRdv = $prochainQuery->first();

        return view('dashboard.rdv.index', compact('rdvs', 'filtre', 'statut', 'prochainRdv'));
    }

    public function create(Request $request)
    {
        $clients     = Client::where('actif', true)
                           ->orderByRaw("CASE WHEN type_client = 'entreprise' THEN raison_sociale ELSE CONCAT(prenom, ' ', nom) END")
                           ->limit(300)
                           ->get();
        $prestations = Prestation::where('actif', true)->with('categorie')->orderBy('nom')->limit(500)->get();
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

        // Si l'utilisateur est un employé, il est automatiquement assigné
        if (Auth::user()->isEmploye()) {
            $data['employe_id'] = Auth::id();
        }

        $rdv = RendezVous::create(\Arr::except($data, ['prestations', 'envoyer_confirmation']));

        if (!empty($data['prestations'])) {
            $rdv->prestations()->sync($data['prestations']);
        }

        // Notifications à l'admin et à l'employé assigné
        $user = Auth::user();
        $employe = $rdv->employe_id ? User::find($rdv->employe_id) : null;

        // Liste des destinataires (admin + employé si différent)
        $destinataires = collect([$user]);
        if ($employe && $employe->id !== $user->id) {
            $destinataires->push($employe);
        }

        foreach ($destinataires as $destinataire) {
            try {
                Mail::to($destinataire->email)->send(new RdvConfirmeEtablissement($rdv));
            } catch (\Throwable $e) { \Log::warning('[RDV Mail Etablissement] ' . $e->getMessage()); }

            try {
                app(PushNotificationService::class)->sendToUser(
                    $destinataire,
                    '📅 Nouveau RDV ajouté',
                    ($rdv->client_nom) . ' — ' . $rdv->debut_le->format('d/m à H\hi'),
                    '/dashboard/rdv/' . $rdv->id
                );
            } catch (\Throwable $e) { \Log::warning('[RDV Push] ' . $e->getMessage()); }

            \App\Services\NotificationService::notifyUser(
                $destinataire,
                'rdv_confirme',
                '📅 Nouveau RDV — ' . $rdv->client_nom,
                $rdv->debut_le->format('d/m/Y à H\hi') . ($rdv->label_prestations ? ' · ' . $rdv->label_prestations : ''),
                '/dashboard/rdv/' . $rdv->id
            );
        }

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
        // Les employés ne peuvent voir que leurs RDV
        if (Auth::user()->isEmploye() && $rdv->employe_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à voir ce rendez-vous.');
        }

        $rdv->loadMissing(['client', 'prestations', 'employe']);
        return view('dashboard.rdv.show', compact('rdv'));
    }

    public function edit(RendezVous $rdv)
    {
        // Interdire l'édition des RDV terminés
        if ($rdv->statut === 'termine') {
            return back()->with('error', 'Impossible de modifier un rendez-vous terminé.');
        }

        // Les employés ne peuvent éditer que leurs RDV
        if (Auth::user()->isEmploye() && $rdv->employe_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce rendez-vous.');
        }

        $rdv->loadMissing(['prestations']);
        $clients     = Client::where('actif', true)
                           ->orderByRaw("CASE WHEN type_client = 'entreprise' THEN raison_sociale ELSE CONCAT(prenom, ' ', nom) END")
                           ->limit(300)
                           ->get();
        $prestations = Prestation::where('actif', true)->with('categorie')->orderBy('nom')->limit(500)->get();
        $employes    = User::where('institut_id', $this->institutId())
                           ->whereIn('role', ['admin', 'employe'])
                           ->where('actif', true)
                           ->orderBy('prenom')
                           ->get();

        return view('dashboard.rdv.edit', compact('rdv', 'clients', 'prestations', 'employes'));
    }

    public function update(Request $request, RendezVous $rdv)
    {
        // Interdire la modification des RDV terminés
        if ($rdv->statut === 'termine') {
            return back()->with('error', 'Impossible de modifier un rendez-vous terminé.');
        }

        // Les employés ne peuvent modifier que leurs RDV
        if (Auth::user()->isEmploye() && $rdv->employe_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier ce rendez-vous.');
        }

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

        // Les employés ne peuvent pas modifier l'assignation
        if (Auth::user()->isEmploye()) {
            unset($data['employe_id']);
        }

        $rdv->update(\Arr::except($data, ['prestations']));
        $rdv->prestations()->sync($data['prestations'] ?? []);

        return redirect()->route('dashboard.rdv.show', $rdv)
            ->with('success', 'Rendez-vous mis à jour.');
    }

    public function annuler(RendezVous $rdv)
    {
        // Les employés ne peuvent annuler que leurs RDV
        if (Auth::user()->isEmploye() && $rdv->employe_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à annuler ce rendez-vous.');
        }

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
        // Les employés ne peuvent terminer que leurs RDV
        if (Auth::user()->isEmploye() && $rdv->employe_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à terminer ce rendez-vous.');
        }

        $rdv->update(['statut' => 'termine']);

        // Générer un avis client (sondage post-visite) si pas déjà créé pour ce RDV
        if (! \App\Models\AvisClient::withoutGlobalScopes()->where('rdv_id', $rdv->id)->exists()) {
            $avis = \App\Models\AvisClient::create([
                'institut_id'     => $rdv->institut_id,
                'client_id'       => $rdv->client_id,
                'rdv_id'          => $rdv->id,
                'client_nom_snap' => $rdv->client_nom,
                'statut'          => 'en_attente',
            ]);

            if ($rdv->client_email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($rdv->client_email)
                        ->send(new \App\Mail\AvisDemande($avis, $rdv));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Avis Mail] ' . $e->getMessage());
                }
            }
        }

        return back()->with('success', 'Rendez-vous marqué comme terminé.');
    }

    /** Vue calendrier interactif (FullCalendar) */
    public function calendrier()
    {
        return view('dashboard.rdv.calendrier');
    }

    /** Flux JSON pour FullCalendar */
    public function events(Request $request)
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        $q = RendezVous::with('prestations', 'client')->whereNotNull('debut_le');

        // Les employés ne voient que leurs RDV
        if (Auth::user()->isEmploye()) {
            $q->where('employe_id', Auth::id());
        }

        if ($start) $q->where('debut_le', '>=', $start);
        if ($end)   $q->where('debut_le', '<=', $end);

        return $q->limit(500)->get()->map(function ($r) {
            $color = match ($r->statut) {
                'confirme'   => '#10b981',
                'en_attente' => $r->source === 'vitrine' ? '#8b5cf6' : '#f59e0b',
                'annule'     => '#9ca3af',
                'termine'    => '#6366f1',
                default      => '#7c3aed',
            };
            $vitrinePrefix = $r->source === 'vitrine' ? '🌐 ' : '';
            return [
                'id'              => $r->id,
                'title'           => $vitrinePrefix . trim(($r->client_nom ?: ($r->client->nom_complet ?? 'RDV')) . ' — ' . ($r->prestations->first()->nom ?? '')),
                'start'           => $r->debut_le->toIso8601String(),
                'end'             => $r->debut_le->copy()->addMinutes($r->duree_minutes ?? 30)->toIso8601String(),
                'color'           => $color,
                'borderColor'     => $r->source === 'vitrine' ? '#7c3aed' : $color,
                'url'             => route('dashboard.rdv.show', $r),
                'extendedProps'   => ['source' => $r->source],
            ];
        });
    }

    /** Drag & drop : déplacer un RDV (AJAX) */
    public function move(Request $request, RendezVous $rdv)
    {
        $data = $request->validate([
            'debut_le' => ['required', 'date'],
        ]);
        $rdv->update(['debut_le' => $data['debut_le']]);
        return response()->json(['ok' => true]);
    }
}
