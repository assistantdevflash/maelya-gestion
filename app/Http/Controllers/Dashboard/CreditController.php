<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\TexteHelper;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Echeance;
use App\Services\CreditService;
use App\Models\Institut;
use App\Models\PaiementCredit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index(Request $request)
    {
        $filtre = $request->input('statut', 'tous');
        $search = $request->input('q');

        $credits = Credit::where('institut_id', $this->institutId())
            ->with(['client', 'vente.items'])
            ->when($filtre !== 'tous', fn($q) => $q->where('statut', $filtre))
            ->when($search, fn($q) => $q->whereHas('client', fn($q2) =>
                $q2->where('prenom', 'like', "%{$search}%")
                   ->orWhere('nom', 'like', "%{$search}%")
                   ->orWhere('telephone', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $totaux = Credit::where('institut_id', $this->institutId())
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                SUM(CASE WHEN statut = 'retard' THEN 1 ELSE 0 END) as en_retard,
                SUM(CASE WHEN statut = 'solde' THEN 1 ELSE 0 END) as soldes,
                COALESCE(SUM(CASE WHEN statut != 'solde' THEN reste_a_payer ELSE 0 END), 0) as total_du
            ")->first();

        // ── Pilotage du recouvrement ──────────────────────────────────────
        $service   = app(CreditService::class);
        $balance   = $service->balanceAgee($this->institutId());
        $taux      = $service->tauxRecouvrement($this->institutId());
        $delaiMoyen = $service->delaiMoyenEncaissement($this->institutId());
        $topDebiteurs = $service->topDebiteurs($this->institutId(), 5);

        return view('dashboard.credits.index', compact(
            'credits', 'filtre', 'totaux', 'balance', 'taux', 'delaiMoyen', 'topDebiteurs'
        ));
    }

    /**
     * Export CSV des encours (pour le comptable).
     */
    public function exportEncours()
    {
        $credits = Credit::where('institut_id', $this->institutId())
            ->where('statut', '!=', 'solde')
            ->with(['client', 'echeances'])
            ->orderByDesc('reste_a_payer')
            ->get();

        $service = app(CreditService::class);
        $nomFichier = 'encours-credits-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($credits, $service) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [
                'Client', 'Téléphone', 'Montant total', 'Apport', 'Déjà payé',
                'Reste à payer', 'Statut', 'Ouvert le', 'Fin prévue',
                'Échéances en retard', 'Jours de retard max',
            ], ';');

            foreach ($credits as $credit) {
                $enRetard = $credit->echeances->where('statut', 'retard');
                $retardMax = $credit->echeances
                    ->filter(fn ($e) => $e->statut !== 'payee' && $e->date_prevue?->isPast())
                    ->map(fn ($e) => (int) $e->date_prevue->diffInDays(now(), false))
                    ->max() ?? 0;

                fputcsv($out, [
                    $credit->client?->nom_complet ?? '—',
                    $credit->client?->telephone ?? '',
                    (int) $credit->montant_total,
                    (int) $credit->apport_initial,
                    (int) $credit->montant_total - (int) $credit->reste_a_payer,
                    (int) $credit->reste_a_payer,
                    $credit->statut === 'retard' ? 'En retard' : ($credit->statut === 'solde' ? 'Soldé' : 'En cours'),
                    $credit->date_debut?->format('d/m/Y'),
                    $credit->date_fin_prevue?->format('d/m/Y'),
                    $enRetard->count(),
                    max(0, (int) $retardMax),
                ], ';');
            }

            fclose($out);
        }, $nomFichier, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Paramétrage du module crédits (plafond, relances, modèle de message).
     */
    public function parametres()
    {
        $institut = Institut::findOrFail($this->institutId());

        return view('dashboard.credits.parametres', compact('institut'));
    }

    public function enregistrerParametres(Request $request)
    {
        $data = $request->validate([
            'credit_plafond_defaut' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'credit_message_relance' => ['nullable', 'string', 'max:1000'],
        ]);

        $institut = Institut::findOrFail($this->institutId());
        $institut->update([
            'credit_plafond_defaut'  => $data['credit_plafond_defaut'] ?: null,
            'credit_message_relance' => $data['credit_message_relance'] ?: null,
            'credit_relance_active'  => $request->boolean('credit_relance_active'),
        ]);

        return back()->with('success', 'Paramètres du module crédits enregistrés.');
    }

    public function show(Credit $credit)
    {
        $credit->load(['client', 'vente.items', 'echeances', 'paiements.encaisseur']);
        return view('dashboard.credits.show', compact('credit'));
    }

    public function create(Request $request)
    {
        $institutId = $this->institutId();
        $allClients = \App\Models\Client::where('institut_id', $institutId)
            ->orderBy('nom')
            ->get()
            ->map(fn($c) => [
                'id'        => $c->id,
                'prenom'    => $c->prenom,
                'nom'       => $c->nom,
                'nom_affichage' => $c->nom_affichage,
                'telephone' => $c->telephone,
                'email'     => $c->email,
                'adresse'   => $c->adresse,
                'initiale'  => strtoupper(substr($c->nom_complet, 0, 1)),
                'search'    => strtolower($c->nom . ' ' . $c->prenom . ' ' . $c->telephone),
            ]);

        $prestations = \App\Models\Prestation::where('institut_id', $institutId)->where('actif', true)
            ->orderBy('nom')->get(['id', 'nom', 'prix'])
            ->map(fn($p) => ['id' => 'p_'.$p->id, 'type' => 'prestation', 'designation' => $p->nom, 'prix' => $p->prix, 'search' => strtolower($p->nom)]);
        $produits = \App\Models\Produit::where('institut_id', $institutId)->where('actif', true)
            ->orderBy('nom')->get(['id', 'nom', 'prix_vente'])
            ->map(fn($p) => ['id' => 'prod_'.$p->id, 'type' => 'produit', 'designation' => $p->nom, 'prix' => $p->prix_vente, 'search' => strtolower($p->nom)]);
        $catalogue = $prestations->concat($produits)->values();

        // Pré-remplissage depuis une vente existante (?vente=ID)
        $prefill = null;
        $venteSource = null;

        if ($request->filled('vente')) {
            $vente = \App\Models\Vente::with('items')
                ->where('institut_id', $institutId)
                ->find($request->input('vente'));

            if ($vente) {
                $venteSource = [
                    'id'      => $vente->id,
                    'numero'  => $vente->numero ?? $vente->reference ?? null,
                    'total'   => (int) ($vente->total ?? 0),
                ];

                $prefill = [
                    'client' => $vente->client_id ? $allClients->firstWhere('id', $vente->client_id) : null,
                    'lignes' => $vente->items->map(fn($item) => [
                        'designation'   => $item->nom_snapshot,
                        'quantite'      => (int) ($item->quantite ?: 1),
                        'prix_unitaire' => (int) ($item->prix_snapshot ?: 0),
                    ])->values()->all(),
                ];
            }
        }

        return view('dashboard.credits.create', compact('allClients', 'catalogue', 'prefill', 'venteSource'));
    }

    /**
     * Crédits non soldés d'un client (en cours et en retard).
     * Utilisé par le formulaire de création pour alerter l'utilisateur
     * dès qu'il sélectionne un client qui a déjà un encours.
     */
    public function encoursClient(Client $client)
    {
        abort_unless($client->institut_id === $this->institutId(), 404);

        $service = app(CreditService::class);

        $credits = Credit::where('client_id', $client->id)
            ->where('statut', '!=', 'solde')
            ->with('echeances')
            ->orderBy('date_debut')
            ->get();

        $lignes = $credits->map(function (Credit $credit) {
            $enRetard = $credit->echeances->where('statut', 'retard');
            $prochaine = $credit->echeances
                ->whereIn('statut', ['en_attente', 'retard'])
                ->sortBy('date_prevue')
                ->first();

            $details = 'Ouvert le ' . ($credit->date_debut?->format('d/m/Y') ?? '—');

            if ($enRetard->isNotEmpty()) {
                $details .= ' · ' . TexteHelper::pluriel($enRetard->count(), 'échéance en retard', 'échéances en retard');
            } elseif ($prochaine?->date_prevue) {
                $details .= ' · prochaine échéance le ' . $prochaine->date_prevue->format('d/m/Y');
            } elseif ($credit->date_fin_prevue) {
                $details .= ' · fin prévue le ' . $credit->date_fin_prevue->format('d/m/Y');
            }

            return [
                'id'           => $credit->id,
                'statut'       => $credit->statut,
                'statut_label' => $credit->statut === 'retard' ? 'En retard' : 'En cours',
                'reste_label'  => number_format($credit->reste_a_payer, 0, ',', ' ') . ' F',
                'total_label'  => number_format($credit->montant_total, 0, ',', ' ') . ' F',
                'details'      => $details,
                'url'          => route('dashboard.credits.show', $credit),
            ];
        })->values();

        $totalDu = (int) $credits->sum('reste_a_payer');
        $plafond = $service->plafondEffectif($client);
        $score   = $service->scoreClient($client);

        return response()->json([
            'credits'        => $lignes,
            'nb'             => $lignes->count(),
            'nb_retard'      => $credits->where('statut', 'retard')->count(),
            'total_du'       => $totalDu,
            'total_du_label' => number_format($totalDu, 0, ',', ' ') . ' F',
            'plafond'        => $plafond,
            'plafond_label'  => $plafond !== null ? number_format($plafond, 0, ',', ' ') . ' F' : null,
            'reste_disponible'       => $plafond !== null ? max(0, $plafond - $totalDu) : null,
            'reste_disponible_label' => $plafond !== null ? number_format(max(0, $plafond - $totalDu), 0, ',', ' ') . ' F' : null,
            'score'          => $score,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'       => ['required', 'uuid', 'exists:clients,id'],
            'lignes'          => ['required', 'json'],
            'apport_initial'  => ['required', 'integer', 'min:0'],
            'nb_echeances'    => ['required', 'integer', 'min:1', 'max:24'],
            'frequence'       => ['required', 'in:hebdomadaire,mensuelle'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $lignes = json_decode($data['lignes'], true);
        if (! is_array($lignes) || empty($lignes)) {
            return back()->withErrors(['lignes' => 'Ajoutez au moins un article.']);
        }

        $institutId = $this->institutId();

        $totalBrut = 0;
        $itemsToSave = [];

        foreach ($lignes as $lig) {
            $nom   = strip_tags(substr((string) ($lig['designation'] ?? ''), 0, 150));
            $prix  = max(1, min(10_000_000, (int) ($lig['prix_unitaire'] ?? 0)));
            $qte   = max(1, (int) ($lig['quantite'] ?? 1));

            abort_if(! $nom || $prix <= 0, 422, 'Article invalide.');

            $sousTotal = $prix * $qte;
            $totalBrut += $sousTotal;

            $itemsToSave[] = [
                'type'          => 'libre',
                'nom_snapshot'  => $nom,
                'prix_snapshot' => $prix,
                'quantite'      => $qte,
                'sous_total'    => $sousTotal,
            ];
        }

        $total  = $totalBrut;
        $apport = $data['apport_initial'];
        $reste  = max(0, $total - $apport);

        // ── Plafond d'encours ────────────────────────────────────────────
        $client   = Client::findOrFail($data['client_id']);
        $verifPlafond = app(CreditService::class)->verifierPlafond($client, $reste);

        if ($verifPlafond['depasse'] && ! $request->boolean('forcer_plafond')) {
            return back()
                ->withErrors(['plafond' => $verifPlafond['message'] . ' Cochez « passer outre le plafond » pour continuer.'])
                ->withInput();
        }

        $credit = DB::transaction(function () use ($data, $institutId, $total, $apport, $reste, $itemsToSave) {
            $vente = \App\Models\Vente::create([
                'institut_id'   => $institutId,
                'client_id'     => $data['client_id'],
                'user_id'       => Auth::id(),
                'total'         => $total,
                'montant_paye'  => $apport,
                'mode_paiement' => 'credit',
                'credit_statut' => $reste > 0 ? 'en_cours' : 'solde',
                'statut'        => 'validee',
                'ip_address'    => request()->ip(),
            ]);

            foreach ($itemsToSave as $item) {
                $vente->items()->create($item);
            }

            $credit = Credit::create([
                'vente_id'       => $vente->id,
                'institut_id'    => $institutId,
                'client_id'      => $data['client_id'],
                'montant_total'  => $total,
                'apport_initial' => $apport,
                'reste_a_payer'  => $reste,
                'nb_echeances'   => $data['nb_echeances'],
                'frequence'      => $data['frequence'],
                'date_debut'     => now(),
                'date_fin_prevue'=> $this->calculerDateFin(now(), $data['nb_echeances'], $data['frequence']),
                'notes'          => $data['notes'] ?? null,
                'statut'         => $reste > 0 ? 'en_cours' : 'solde',
            ]);

            if ($reste > 0) {
                $parEcheance = (int) round($reste / $data['nb_echeances']);
                $date = now()->copy();
                for ($i = 0; $i < $data['nb_echeances']; $i++) {
                    $date = $data['frequence'] === 'hebdomadaire' ? $date->addWeek() : $date->addMonth();
                    $montant = ($i === $data['nb_echeances'] - 1)
                        ? $reste - ($parEcheance * ($data['nb_echeances'] - 1))
                        : $parEcheance;
                    \App\Models\Echeance::create([
                        'credit_id'   => $credit->id,
                        'institut_id' => $institutId,
                        'numero'      => $i + 1,
                        'date_prevue' => $date->copy(),
                        'montant'     => max(0, $montant),
                        'statut'      => 'en_attente',
                    ]);
                }
            }

            if ($apport > 0) {
                \App\Models\PaiementCredit::create([
                    'credit_id'     => $credit->id,
                    'institut_id'   => $institutId,
                    'montant'       => $apport,
                    'mode_paiement' => 'cash',
                    'encaisse_par'  => Auth::id(),
                    'notes'         => 'Apport initial',
                ]);
            }

            return $credit;
        });

        return redirect()->route('dashboard.credits.show', $credit)
            ->with('success', 'Crédit créé — reste à payer : ' . number_format($reste, 0, ',', ' ') . ' FCFA');
    }

    private function calculerDateFin($date, int $nb, string $freq): \Carbon\Carbon
    {
        $d = $date->copy();
        for ($i = 0; $i < $nb; $i++) {
            $d = $freq === 'hebdomadaire' ? $d->addWeek() : $d->addMonth();
        }
        return $d;
    }

    public function edit(Credit $credit)
    {
        abort_if($credit->paiements()->count() > 0, 403, 'Impossible de modifier un crédit ayant déjà reçu des paiements.');

        $institutId = $this->institutId();
        $allClients = \App\Models\Client::where('institut_id', $institutId)
            ->orderBy('nom')->get()
            ->map(fn($c) => [
                'id' => $c->id, 'prenom' => $c->prenom, 'nom' => $c->nom,
                'nom_affichage' => $c->nom_affichage, 'telephone' => $c->telephone,
                'email' => $c->email, 'adresse' => $c->adresse,
                'initiale' => strtoupper(substr($c->nom_complet, 0, 1)),
                'search' => strtolower($c->nom . ' ' . $c->prenom . ' ' . $c->telephone),
            ]);

        $prestations = \App\Models\Prestation::where('institut_id', $institutId)->where('actif', true)
            ->orderBy('nom')->get(['id', 'nom', 'prix'])
            ->map(fn($p) => ['id' => 'p_'.$p->id, 'type' => 'prestation', 'designation' => $p->nom, 'prix' => $p->prix, 'search' => strtolower($p->nom)]);
        $produits = \App\Models\Produit::where('institut_id', $institutId)->where('actif', true)
            ->orderBy('nom')->get(['id', 'nom', 'prix_vente'])
            ->map(fn($p) => ['id' => 'prod_'.$p->id, 'type' => 'produit', 'designation' => $p->nom, 'prix' => $p->prix_vente, 'search' => strtolower($p->nom)]);
        $catalogue = $prestations->concat($produits)->values();

        $credit->load(['vente.items', 'client']);
        // Préparer les données pour l'edit : lignes, client choisi, etc.
        $editData = [
            'client' => $credit->client ? [
                'id' => $credit->client->id, 'prenom' => $credit->client->prenom,
                'nom' => $credit->client->nom, 'nom_affichage' => $credit->client->nom_complet,
                'telephone' => $credit->client->telephone, 'email' => $credit->client->email,
                'initiale' => strtoupper(substr($credit->client->nom_complet, 0, 1)),
            ] : null,
            'lignes' => $credit->vente->items->map(fn($i) => [
                'designation' => $i->nom_snapshot, 'quantite' => $i->quantite,
                'prix_unitaire' => $i->prix_snapshot,
            ])->values()->toArray(),
            'apport' => $credit->apport_initial,
            'nb_echeances' => $credit->nb_echeances,
            'frequence' => $credit->frequence,
            'notes' => $credit->notes,
        ];

        return view('dashboard.credits.edit', compact('allClients', 'catalogue', 'credit', 'editData'));
    }

    public function update(Request $request, Credit $credit)
    {
        abort_if($credit->paiements()->count() > 0, 403, 'Impossible de modifier un crédit ayant déjà reçu des paiements.');

        $data = $request->validate([
            'client_id'       => ['required', 'uuid', 'exists:clients,id'],
            'lignes'          => ['required', 'json'],
            'apport_initial'  => ['required', 'integer', 'min:0'],
            'nb_echeances'    => ['required', 'integer', 'min:1', 'max:24'],
            'frequence'       => ['required', 'in:hebdomadaire,mensuelle'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $lignes = json_decode($data['lignes'], true);
        if (! is_array($lignes) || empty($lignes)) {
            return back()->withErrors(['lignes' => 'Ajoutez au moins un article.']);
        }

        $institutId = $this->institutId();

        $totalBrut = 0;
        $itemsToSave = [];
        foreach ($lignes as $lig) {
            $nom  = strip_tags(substr((string) ($lig['designation'] ?? ''), 0, 150));
            $prix = max(1, min(10_000_000, (int) ($lig['prix_unitaire'] ?? 0)));
            $qte  = max(1, (int) ($lig['quantite'] ?? 1));
            abort_if(! $nom || $prix <= 0, 422, 'Article invalide.');
            $sousTotal = $prix * $qte;
            $totalBrut += $sousTotal;
            $itemsToSave[] = ['type' => 'libre', 'nom_snapshot' => $nom, 'prix_snapshot' => $prix, 'quantite' => $qte, 'sous_total' => $sousTotal];
        }

        $total  = $totalBrut;
        $apport = $data['apport_initial'];
        $reste  = max(0, $total - $apport);

        DB::transaction(function () use ($credit, $data, $institutId, $total, $apport, $reste, $itemsToSave) {
            // Mettre à jour la vente
            $credit->vente->update([
                'client_id'     => $data['client_id'],
                'total'         => $total,
                'montant_paye'  => $apport,
                'credit_statut' => $reste > 0 ? 'en_cours' : 'solde',
            ]);

            // Remplacer les items
            $credit->vente->items()->delete();
            foreach ($itemsToSave as $item) {
                $credit->vente->items()->create($item);
            }

            // Mettre à jour le crédit
            $credit->update([
                'client_id'      => $data['client_id'],
                'montant_total'  => $total,
                'apport_initial' => $apport,
                'reste_a_payer'  => $reste,
                'nb_echeances'   => $data['nb_echeances'],
                'frequence'      => $data['frequence'],
                'date_fin_prevue'=> $this->calculerDateFin(now(), $data['nb_echeances'], $data['frequence']),
                'notes'          => $data['notes'] ?? null,
                'statut'         => $reste > 0 ? 'en_cours' : 'solde',
            ]);

            // Régénérer les échéances
            \App\Models\Echeance::where('credit_id', $credit->id)->delete();
            if ($reste > 0) {
                $parEcheance = (int) round($reste / $data['nb_echeances']);
                $date = now()->copy();
                for ($i = 0; $i < $data['nb_echeances']; $i++) {
                    $date = $data['frequence'] === 'hebdomadaire' ? $date->addWeek() : $date->addMonth();
                    $montant = ($i === $data['nb_echeances'] - 1)
                        ? $reste - ($parEcheance * ($data['nb_echeances'] - 1))
                        : $parEcheance;
                    \App\Models\Echeance::create([
                        'credit_id' => $credit->id, 'institut_id' => $institutId,
                        'numero' => $i + 1, 'date_prevue' => $date->copy(),
                        'montant' => max(0, $montant), 'statut' => 'en_attente',
                    ]);
                }
            }

            // Si l'apport a changé, supprimer l'ancien paiement d'apport et recréer
            $credit->paiements()->where('notes', 'Apport initial')->delete();
            if ($apport > 0) {
                \App\Models\PaiementCredit::create([
                    'credit_id' => $credit->id, 'institut_id' => $institutId,
                    'montant' => $apport, 'mode_paiement' => 'cash',
                    'encaisse_par' => Auth::id(), 'notes' => 'Apport initial',
                ]);
            }
        });

        return redirect()->route('dashboard.credits.show', $credit)
            ->with('success', 'Crédit modifié — reste à payer : ' . number_format($reste, 0, ',', ' ') . ' FCFA');
    }

    public function destroy(Credit $credit)
    {
        abort_if($credit->paiements()->where('notes', '!=', 'Apport initial')->count() > 0, 403, 'Impossible de supprimer un crédit ayant déjà reçu des paiements.');

        DB::transaction(function () use ($credit) {
            $credit->echeances()->delete();
            $credit->paiements()->delete();
            $credit->vente->items()->delete();
            $credit->vente->delete();
            $credit->delete();
        });

        return redirect()->route('dashboard.credits.index')
            ->with('success', 'Crédit supprimé.');
    }

    public function fichePdf(Credit $credit)
    {
        $credit->load(['client', 'vente.items', 'echeances', 'paiements.encaisseur']);
        $institut = Institut::find($this->institutId());

        $pdf = Pdf::loadView('pdf.credit', compact('credit', 'institut'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("fiche-credit-" . substr($credit->id, 0, 8) . ".pdf");
    }

    public function fichePdfPublic(string $id)
    {
        $credit = Credit::withoutGlobalScope(\App\Scopes\InstitutScope::class)
            ->whereKey($id)
            ->firstOrFail();
        $credit->load(['client', 'vente.items', 'echeances', 'paiements.encaisseur']);
        $institut = Institut::find($credit->institut_id);

        $pdf = Pdf::loadView('pdf.credit', compact('credit', 'institut'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("fiche-credit-" . substr($credit->id, 0, 8) . ".pdf");
    }

    /**
     * Enregistre une relance (canal whatsapp | email | inapp) puis redirige
     * vers WhatsApp si disponible.
     */
    public function relancer(Request $request, Credit $credit)
    {
        $data = $request->validate([
            'canal' => ['required', 'in:whatsapp,email,inapp'],
        ]);

        $service = app(CreditService::class);
        $canal = $data['canal'];

        \App\Models\CreditRelance::firstOrCreate(
            [
                'credit_id' => $credit->id,
                'palier'    => 'manuel',
                'canal'     => $canal,
            ],
            [
                'institut_id' => $credit->institut_id,
                'envoye_le'   => now(),
            ]
        );

        if ($canal === 'email' && $credit->client?->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($credit->client->email)
                    ->send(new \App\Mail\RelanceCredit($credit, $service->messageRelance($credit)));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[Relance crédit email] ' . $e->getMessage());
            }
        }

        if ($canal === 'whatsapp') {
            $lien = $service->lienWhatsApp($credit);

            if (! $lien) {
                return back()->with('error', "Ce client n'a pas de numéro de téléphone exploitable.");
            }

            return redirect()->away($lien);
        }

        return back()->with('success', 'Relance enregistrée.');
    }

    /**
     * Rééchelonne les échéances restantes d'un crédit.
     */
    public function reechelonner(Request $request, Credit $credit)
    {
        abort_if($credit->statut === 'solde', 403, 'Ce crédit est déjà soldé.');

        $data = $request->validate([
            'nb_echeances' => ['required', 'integer', 'min:1', 'max:36'],
            'frequence'    => ['required', 'in:hebdomadaire,mensuelle'],
            'date_debut'   => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        app(CreditService::class)->reechelonner(
            $credit,
            (int) $data['nb_echeances'],
            $data['frequence'],
            ! empty($data['date_debut']) ? \Illuminate\Support\Carbon::parse($data['date_debut']) : null
        );

        return back()->with('success', 'Échéancier réaménagé : '
            . $data['nb_echeances'] . ' échéance(s) ' . $data['frequence'] . '(s).');
    }

    /**
     * Reporte une échéance à une nouvelle date.
     */
    public function reporterEcheance(Request $request, Credit $credit, Echeance $echeance)
    {
        abort_if($echeance->credit_id !== $credit->id, 403);

        $data = $request->validate([
            'date_prevue' => ['required', 'date', 'after:today'],
        ]);

        app(CreditService::class)->reporterEcheance(
            $echeance,
            \Illuminate\Support\Carbon::parse($data['date_prevue'])
        );

        return back()->with('success', 'Échéance reportée au '
            . \Illuminate\Support\Carbon::parse($data['date_prevue'])->format('d/m/Y') . '.');
    }

    public function payer(Request $request, Credit $credit)
    {
        $data = $request->validate([
            'echeance_id'   => ['required', 'uuid', 'exists:echeances,id'],
            'montant'       => ['required', 'integer', 'min:1'],
            'mode_paiement' => ['required', 'in:cash,mobile_money,carte'],
            'reference'     => ['nullable', 'string', 'max:100'],
        ]);

        $echeance = Echeance::findOrFail($data['echeance_id']);
        abort_if($echeance->credit_id !== $credit->id, 403);

        $paiement = DB::transaction(function () use ($credit, $echeance, $data) {
            $paiement = PaiementCredit::create([
                'credit_id'     => $credit->id,
                'echeance_id'   => $echeance->id,
                'institut_id'   => $this->institutId(),
                'montant'       => $data['montant'],
                'mode_paiement' => $data['mode_paiement'],
                'reference'     => $data['reference'] ?? null,
                'encaisse_par'  => Auth::id(),
                'created_at'    => now(),
            ]);

            $echeance->montant_paye += $data['montant'];
            if ($echeance->montant_paye >= $echeance->montant) {
                $echeance->statut = 'payee';
                $echeance->date_paiement = now();
                $echeance->encaisse_par = Auth::id();
            }
            $echeance->save();

            $credit->reste_a_payer = max(0, $credit->reste_a_payer - $data['montant']);
            if ($credit->reste_a_payer <= 0) {
                $credit->statut = 'solde';
                $credit->vente->credit_statut = 'solde';
            } elseif ($credit->statut === 'retard') {
                $aDesRetards = $credit->echeances()
                    ->where('statut', 'retard')
                    ->where('date_prevue', '<', now()->toDateString())
                    ->exists();
                if (! $aDesRetards) {
                    $credit->statut = 'en_cours';
                    $credit->vente->credit_statut = 'en_cours';
                }
            }
            $credit->vente->montant_paye += $data['montant'];
            $credit->vente->save();
            $credit->save();

            return $paiement;
        });

        $service = app(CreditService::class);
        $recu    = $service->recuPaiement($paiement);
        $tel     = $service->numeroWhatsApp($credit->client?->telephone);

        return back()
            ->with('success', 'Paiement de ' . number_format($data['montant'], 0, ',', ' ') . ' FCFA enregistré.')
            ->with('recu_credit', $recu)
            ->with('recu_whatsapp', $tel ? 'https://wa.me/' . $tel . '?text=' . rawurlencode($recu) : null);
    }
}
