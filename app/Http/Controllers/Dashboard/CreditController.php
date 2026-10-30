<?php

namespace App\Http\Controllers\Dashboard;

use App\Helpers\TexteHelper;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Echeance;
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

        return view('dashboard.credits.index', compact('credits', 'filtre', 'totaux'));
    }

    public function show(Credit $credit)
    {
        $credit->load(['client', 'vente.items', 'echeances', 'paiements.encaisseur']);
        return view('dashboard.credits.show', compact('credit'));
    }

    public function create()
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

        return view('dashboard.credits.create', compact('allClients', 'catalogue'));
    }

    /**
     * Crédits non soldés d'un client (en cours et en retard).
     * Utilisé par le formulaire de création pour alerter l'utilisateur
     * dès qu'il sélectionne un client qui a déjà un encours.
     */
    public function encoursClient(Client $client)
    {
        abort_unless($client->institut_id === $this->institutId(), 404);

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

        return response()->json([
            'credits'        => $lignes,
            'nb'             => $lignes->count(),
            'nb_retard'      => $credits->where('statut', 'retard')->count(),
            'total_du'       => $totalDu,
            'total_du_label' => number_format($totalDu, 0, ',', ' ') . ' F',
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
        $credit = Credit::where('id', $id)->firstOrFail();
        $credit->load(['client', 'vente.items', 'echeances', 'paiements.encaisseur']);
        $institut = Institut::find($credit->institut_id);

        $pdf = Pdf::loadView('pdf.credit', compact('credit', 'institut'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("fiche-credit-" . substr($credit->id, 0, 8) . ".pdf");
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

        DB::transaction(function () use ($credit, $echeance, $data) {
            PaiementCredit::create([
                'credit_id'     => $credit->id,
                'echeance_id'   => $echeance->id,
                'institut_id'   => $this->institutId(),
                'montant'       => $data['montant'],
                'mode_paiement' => $data['mode_paiement'],
                'reference'     => $data['reference'],
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
        });

        return back()->with('success', 'Paiement de ' . number_format($data['montant'], 0, ',', ' ') . ' FCFA enregistré.');
    }
}
