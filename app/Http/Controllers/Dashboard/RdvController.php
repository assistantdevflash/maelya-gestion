<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\RdvAnnuleClient;
use App\Mail\RdvConfirmeClient;
use App\Mail\RdvConfirmeEtablissement;
use App\Models\Client;
use App\Models\Institut;
use App\Models\Prestation;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\PushNotificationService;
use App\Services\RdvService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class RdvController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    /** Expression de tri des clients par nom (compatible MySQL + SQLite) */
    private function triClientsExpr(): string
    {
        $concat = \DB::getDriverName() === 'sqlite'
            ? "COALESCE(prenom, '') || ' ' || COALESCE(nom, '')"
            : "CONCAT(prenom, ' ', nom)";

        return "CASE WHEN type_client = 'entreprise' THEN raison_sociale ELSE {$concat} END";
    }

    public function index(Request $request)
    {
        // ⚠️ `$request->get()` (Symfony, déprécié) peut renvoyer null quand le
        // paramètre est présent mais vide (?statut=&employe=), ce qui faisait
        // échouer le typage `string $statut` de appliquerFiltres() → 500.
        // On normalise systématiquement en chaîne.
        $filtre     = $this->parametre($request, 'filtre') ?: 'semaine'; // today | semaine | mois | tous
        $statut     = $this->parametre($request, 'statut');
        $employe    = $this->parametre($request, 'employe');
        $search     = $this->parametre($request, 'q');
        $isEmploye  = Auth::user()->isEmploye();
        $institutId = $this->institutId();

        $query = RendezVous::with(['client', 'prestations', 'employe'])
            ->orderBy('debut_le');

        $this->appliquerFiltres($query, $filtre, $statut, $employe, $search, $isEmploye);

        // Pagination réelle : l'ancien `->limit(300)` tronquait la liste sans
        // le dire (des RDV disparaissaient silencieusement).
        $pagines = $query->paginate(60)->withQueryString();
        $rdvs    = $pagines->getCollection()->groupBy(fn ($r) => $r->debut_le->toDateString());

        // Compteurs des onglets en UNE requête (au lieu de 4 COUNT)
        $base = RendezVous::query();
        $this->appliquerFiltres($base, null, $statut, $employe, $search, $isEmploye);
        $counts = $this->compterPeriodes($base);

        // Prochain RDV (respecte rôle, employé et statut affichés)
        $prochainQuery = RendezVous::with('prestations')->aVenir()->orderBy('debut_le');
        if ($isEmploye) {
            $prochainQuery->where('employe_id', Auth::id());
        } elseif ($employe) {
            $prochainQuery->where('employe_id', $employe);
        }
        if ($statut) {
            $prochainQuery->where('statut', $statut);
        }
        $prochainRdv = $prochainQuery->first();

        // Filtre « employé » (inutile pour un employé : il ne voit que lui)
        $employes = $isEmploye
            ? collect()
            : User::where('institut_id', $institutId)
                ->whereIn('role', ['admin', 'employe'])
                ->where('actif', true)
                ->orderBy('prenom')
                ->get(['id', 'prenom', 'nom_famille']);

        return view('dashboard.rdv.index', compact(
            'rdvs', 'pagines', 'filtre', 'statut', 'employe', 'search',
            'prochainRdv', 'counts', 'employes'
        ));
    }

    /** Lit un paramètre de requête et le normalise en chaîne (jamais null). */
    private function parametre(Request $request, string $cle): string
    {
        $valeur = $request->input($cle);

        return is_scalar($valeur) ? trim((string) $valeur) : '';
    }

    /** Applique période / statut / employé / recherche à une requête de RDV. */
    private function appliquerFiltres($query, ?string $filtre, ?string $statut, ?string $employe, ?string $search, bool $isEmploye): void
    {
        $statut  = (string) $statut;
        $employe = (string) $employe;
        $search  = (string) $search;

        if ($isEmploye) {
            $query->where('employe_id', Auth::id());
        } elseif ($employe) {
            $query->where('employe_id', $employe);
        }

        $query->when($filtre === 'today', fn ($q) => $q->whereDate('debut_le', today()))
            ->when($filtre === 'semaine', fn ($q) => $q->whereBetween('debut_le', [now()->startOfWeek(), now()->endOfWeek()]))
            ->when($filtre === 'mois', fn ($q) => $q->whereBetween('debut_le', [now()->startOfMonth(), now()->endOfMonth()]))
            ->when($statut, fn ($q) => $q->where('statut', $statut))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('client_nom', 'like', "%{$search}%")
                        ->orWhere('client_telephone', 'like', "%{$search}%")
                        ->orWhere('client_email', 'like', "%{$search}%");
                });
            });
    }

    /** Compteurs Aujourd'hui / Semaine / Mois / Tous en une seule requête. */
    private function compterPeriodes($query): array
    {
        $row = $query->selectRaw(
            'COUNT(*) as total,
             SUM(CASE WHEN debut_le >= ? AND debut_le < ? THEN 1 ELSE 0 END) as aujourdhui,
             SUM(CASE WHEN debut_le BETWEEN ? AND ? THEN 1 ELSE 0 END) as semaine,
             SUM(CASE WHEN debut_le BETWEEN ? AND ? THEN 1 ELSE 0 END) as mois',
            [
                today()->toDateString(), today()->addDay()->toDateString(),
                now()->startOfWeek()->toDateTimeString(), now()->endOfWeek()->toDateTimeString(),
                now()->startOfMonth()->toDateTimeString(), now()->endOfMonth()->toDateTimeString(),
            ]
        )->first();

        return [
            'tous'    => (int) ($row->total ?? 0),
            'today'   => (int) ($row->aujourdhui ?? 0),
            'semaine' => (int) ($row->semaine ?? 0),
            'mois'    => (int) ($row->mois ?? 0),
        ];
    }

    public function create(Request $request)
    {
        $clients     = Client::where('actif', true)
                           ->orderByRaw($this->triClientsExpr())
                           ->limit(300)
                           ->get();
        $prestations = Prestation::where('actif', true)->with('categorie')->orderBy('nom')->limit(500)->get();
        // Un employé ne peut planifier que pour lui-même : on ne lui propose
        // pas la liste complète (le serveur ignorait de toute façon son choix).
        $employes = Auth::user()->isEmploye()
            ? User::whereKey(Auth::id())->get()
            : User::where('institut_id', $this->institutId())
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
            // Les dates passées sont autorisées : on doit pouvoir enregistrer
            // un passage de ce matin ou un RDV pris par téléphone hier.
            'debut_le'         => ['required', 'date'],
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

        // Contrôles de disponibilité (horaires + chevauchement)
        if ($erreur = $this->verifierDisponibilite($data['debut_le'], (int) $data['duree_minutes'], $data['employe_id'] ?? null)) {
            return back()->withErrors(['debut_le' => $erreur])->withInput();
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
                           ->orderByRaw($this->triClientsExpr())
                           ->limit(300)
                           ->get();
        $prestations = Prestation::where('actif', true)->with('categorie')->orderBy('nom')->limit(500)->get();
        $employes    = User::where('institut_id', $this->institutId())
                           ->whereIn('role', ['admin', 'employe'])
                           ->where('actif', true)
                           ->orderBy('prenom')
                           ->get();
        $clientPreselectionne = $rdv->client_id ? $rdv->client : null;

        return view('dashboard.rdv.edit', compact('rdv', 'clients', 'prestations', 'employes', 'clientPreselectionne'));
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

        if ($erreur = $this->verifierDisponibilite(
            $data['debut_le'],
            (int) $data['duree_minutes'],
            $data['employe_id'] ?? $rdv->employe_id,
            $rdv->id
        )) {
            return back()->withErrors(['debut_le' => $erreur])->withInput();
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

        return $q->orderBy('debut_le')->limit(1000)->get()->map(function ($r) {
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

        $service = app(RdvService::class);
        $debut   = Carbon::parse($data['debut_le']);

        if ($erreur = $service->horsHoraires(Institut::find($rdv->institut_id), $debut, (int) $rdv->duree_minutes)) {
            return response()->json(['ok' => false, 'message' => $erreur], 422);
        }

        $conflit = $service->conflit(
            $rdv->institut_id,
            $rdv->employe_id,
            $debut,
            (int) $rdv->duree_minutes,
            $rdv->id
        );

        if ($conflit) {
            return response()->json(['ok' => false, 'message' => $service->messageConflit($conflit)], 409);
        }

        $rdv->update(['debut_le' => $data['debut_le']]);

        return response()->json(['ok' => true]);
    }

    /**
     * Confirme un rendez-vous (statut → « confirmé ») et prévient le client.
     *
     * Il n'existait aucun moyen de confirmer un RDV autrement qu'en ouvrant le
     * formulaire d'édition — les demandes venues de la vitrine restaient donc
     * « en attente » et le client n'était jamais rassuré.
     */
    public function confirmer(RendezVous $rdv)
    {
        if (Auth::user()->isEmploye() && $rdv->employe_id !== Auth::id()) {
            abort(403, 'Vous n\'êtes pas autorisé à confirmer ce rendez-vous.');
        }

        if ($rdv->statut === 'annule') {
            return back()->with('error', 'Un rendez-vous annulé ne peut pas être confirmé.');
        }

        $rdv->update(['statut' => 'confirme']);
        $rdv->loadMissing('prestations');

        // Demande issue de la vitrine : on crée (ou on rattache) la fiche client
        $fiche = $this->rattacherFicheClient($rdv);

        $mailEnvoye = false;
        if ($rdv->client_email) {
            try {
                Mail::to($rdv->client_email)->send(new RdvConfirmeClient($rdv));
                $mailEnvoye = true;
            } catch (\Throwable $e) {
                \Log::warning('[RDV Confirmation Mail] ' . $e->getMessage());
            }
        }

        $message = 'Rendez-vous confirmé.';
        if ($mailEnvoye) {
            $message .= ' Le client a été prévenu par e-mail.';
        } elseif ($rdv->client_telephone) {
            $message .= ' Client sans e-mail : pensez à le prévenir par WhatsApp.';
        }
        if ($fiche) {
            $message .= ' Fiche client : ' . $fiche->nom_complet . '.';
        }

        return back()->with('success', $message);
    }

    /**
     * Crée la fiche client d'un RDV vitrine, ou la rattache si elle existe déjà.
     *
     * ⚠️ Le doublon est détecté sur le téléphone OU l'e-mail : sans ce contrôle,
     * chaque réservation en ligne créait une nouvelle fiche et le client perdait
     * son historique, sa fidélité et ses crédits.
     */
    private function rattacherFicheClient(RendezVous $rdv): ?Client
    {
        if ($rdv->client_id || $rdv->source !== 'vitrine') {
            return null;
        }

        if (! $rdv->client_telephone && ! $rdv->client_email) {
            return null;
        }

        $existant = Client::trouverParTelephoneOuEmail(
            $rdv->institut_id,
            $rdv->client_telephone,
            $rdv->client_email
        );

        if ($existant) {
            $rdv->update(['client_id' => $existant->id]);

            return $existant;
        }

        $nom    = trim((string) $rdv->client_nom);
        $parties = preg_split('/\s+/', $nom, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $prenom = count($parties) > 1 ? implode(' ', array_slice($parties, 0, -1)) : null;
        $famille = $parties ? end($parties) : 'Client';

        $client = Client::create([
            'institut_id' => $rdv->institut_id,
            'type_client' => 'personne_physique',
            'prenom'      => $prenom,
            'nom'         => $famille,
            'telephone'   => $rdv->client_telephone ?: null,
            'email'       => $rdv->client_email,
        ]);

        $rdv->update(['client_id' => $client->id]);

        return $client;
    }

    /**
     * Vérifie horaires d'ouverture + chevauchement.
     * Retourne le message d'erreur, ou null si le créneau est libre.
     */
    private function verifierDisponibilite(string $debutLe, int $duree, ?string $employeId, ?string $excludeRdvId = null): ?string
    {
        $service = app(RdvService::class);
        $debut   = Carbon::parse($debutLe);

        if ($erreur = $service->horsHoraires(Institut::find($this->institutId()), $debut, $duree)) {
            return $erreur;
        }

        $conflit = $service->conflit($this->institutId(), $employeId, $debut, $duree, $excludeRdvId);

        return $conflit
            ? $service->messageConflit($conflit) . ' Choisissez un autre créneau.'
            : null;
    }
}
