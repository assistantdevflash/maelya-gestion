<?php

namespace App\Livewire;

use App\Models\CaisseBrouillon;
use App\Models\CategoriePrestation;
use App\Models\CategorieProduit;
use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\Prestation;
use App\Models\Produit;
use App\Models\RendezVous;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class Caisse extends Component
{
    public string $clientSearch = '';
    public ?string $clientId = null;
    public bool $submitted = false;
    public bool $showClientList = false;
    public bool $showNewClientForm = false;
    public string $newClientPrenom = '';
    public string $newClientNom = '';
    public string $newClientTelephone = '';
    public string $newClientEmail = '';
    public string $newClientNaissanceMois = '';
    public string $newClientNaissanceJour = '';
    public string $newClientNotes = '';

    /** Prestations pré-remplies (depuis un RDV) à envoyer à Alpine */
    public array $prefilledItems = [];

    /** Panier complet pré-rempli (depuis un brouillon) */
    public array $prefilledPanier = [];

    /** Crédit */
    public int $creditApport = 0;
    public int $creditNbEcheances = 3;
    public string $creditFrequence = 'mensuelle';

    /** Nom du client sélectionné (exposé à Alpine sans computed property) */
    public ?string $selectedClientNom = null;

    public function mount(?string $client = null, ?string $rdv = null, ?string $brouillon = null)
    {
        $this->clientId = $client;

        if ($rdv) {
            $rendezVous = RendezVous::with('prestations:id,nom,prix')->find($rdv);
            if ($rendezVous) {
                if (! $this->clientId && $rendezVous->client_id) {
                    $this->clientId = $rendezVous->client_id;
                }
                $this->prefilledItems = $rendezVous->prestations->map(fn ($p) => [
                    'id'   => $p->id,
                    'nom'  => $p->nom,
                    'prix' => (int) $p->prix,
                ])->values()->all();
            }
        }

        if ($brouillon) {
            $b = CaisseBrouillon::find($brouillon);
            if ($b && $b->institut_id === $this->institutId()) {
                if (! $this->clientId && $b->client_id) {
                    $this->clientId = $b->client_id;
                }
                $this->prefilledPanier = is_array($b->panier) ? $b->panier : [];
                $b->delete();
                session()->flash('success', 'Brouillon repris.');
            }
        }
    }

    private function institutId(): string
    {
        return session('current_institut_id', auth()->user()->institut_id);
    }

    // ── Client (seul cas nécessitant des requêtes Livewire) ──

    #[Computed]
    public function selectedClient()
    {
        if (! $this->clientId) {
            return null;
        }

        return Client::find($this->clientId);
    }

    #[Computed]
    public function clients()
    {
        if (! $this->showClientList && strlen($this->clientSearch) < 1) {
            return collect();
        }

        $query = Client::where('institut_id', $this->institutId())
            ->where('actif', true);

        if (strlen($this->clientSearch) >= 2) {
            $query->where(function ($q) {
                $q->where('prenom', 'like', "%{$this->clientSearch}%")
                    ->orWhere('nom', 'like', "%{$this->clientSearch}%")
                    ->orWhere('telephone', 'like', "%{$this->clientSearch}%");
            });
        }

        return $query->orderBy('prenom')->limit(8)->get();
    }

    public function selectClient(string $id): void
    {
        $this->clientId      = $id;
        $this->clientSearch  = '';
        $this->showClientList = false;
        $client = Client::find($id);
        $this->selectedClientNom = $client?->nom_complet;
    }

    #[On('client-scanne-qr')]
    public function clientScanneQr(string $id): void
    {
        $this->selectClient($id);
    }

    public function ajouterClientRapide()
    {
        $this->validate([
            'newClientPrenom'    => ['required', 'string', 'max:50'],
            'newClientNom'       => ['required', 'string', 'max:50'],
            'newClientTelephone' => ['required', 'string', 'max:30'],
            'newClientEmail'     => ['nullable', 'email', 'max:255'],
            'newClientNaissanceMois' => ['nullable', 'string', 'in:,01,02,03,04,05,06,07,08,09,10,11,12'],
            'newClientNaissanceJour' => ['nullable', 'string'],
            'newClientNotes'     => ['nullable', 'string', 'max:1000'],
        ]);

        $dateNaissance = null;
        if ($this->newClientNaissanceMois && $this->newClientNaissanceJour) {
            $dateNaissance = $this->newClientNaissanceMois . '-' . str_pad($this->newClientNaissanceJour, 2, '0', STR_PAD_LEFT);
        }

        $client = Client::create([
            'prenom'         => $this->newClientPrenom,
            'nom'            => $this->newClientNom,
            'telephone'      => $this->newClientTelephone,
            'email'          => $this->newClientEmail ?: null,
            'date_naissance' => $dateNaissance,
            'notes'          => $this->newClientNotes ?: null,
        ]);

        $this->clientId = $client->id;
        $this->resetNewClientForm();
        $this->dispatch('client-added');
    }

    private function resetNewClientForm(): void
    {
        $this->newClientPrenom = '';
        $this->newClientNom = '';
        $this->newClientTelephone = '';
        $this->newClientEmail = '';
        $this->newClientNaissanceMois = '';
        $this->newClientNaissanceJour = '';
        $this->newClientNotes = '';
        $this->showNewClientForm = false;
        $this->showClientList = false;
        $this->clientSearch = '';
    }

    // ── Code promo (retourne un résultat au lieu de stocker en propriété) ──

    public function appliquerCode(string $code, int $totalBrut): array
    {
        $input = strtoupper(trim($code));
        if ($input === '') {
            return ['erreur' => '', 'promo' => null];
        }

        $codeObj = CodeReduction::where('institut_id', $this->institutId())
            ->where('code', $input)
            ->first();

        if (! $codeObj) {
            return ['erreur' => 'Code de réduction invalide.', 'promo' => null];
        }

        $erreur = $codeObj->validerPourTotal($totalBrut, $this->clientId ?: null);
        if ($erreur) {
            return ['erreur' => $erreur, 'promo' => null];
        }

        return [
            'erreur' => '',
            'promo' => [
                'id'          => $codeObj->id,
                'code'        => $codeObj->code,
                'type'        => $codeObj->type,
                'valeur'      => $codeObj->valeur,
                'remise'      => $codeObj->calculerRemise($totalBrut),
                'description' => $codeObj->description,
            ],
        ];
    }

    // ── Validation vente (reçoit le panier depuis Alpine) ──

    public function valider(array $panier, string $modePaiement, ?int $montantRemis, string $referencePaiement, ?string $codeReductionId, bool $imprimer = false, ?int $montantCash = null, ?int $montantMobile = null, ?int $montantCarte = null, ?int $pourboire = null)
    {
        if (empty($panier)) {
            return;
        }

        $this->submitted = true;

        $totalBrut = (int) array_sum(array_map(
            fn ($item) => $item['prix'] * $item['quantite'],
            $panier,
        ));

        // Recalculer la remise côté serveur par sécurité
        $remise = 0;
        if ($codeReductionId) {
            $code = CodeReduction::find($codeReductionId);
            if ($code) {
                $remise = $code->calculerRemise($totalBrut);
            }
        }

        $total = max(0, $totalBrut - $remise);

        $items = collect($panier)->values()->map(fn ($item) => [
            'type'        => $item['type'],
            'id'          => $item['id'],
            'nom'         => $item['nom'],
            'prix'        => $item['prix'],
            'quantite'    => $item['quantite'],
            'typeLibre'   => $item['typeLibre'] ?? null,
            'categorieId' => $item['categorieId'] ?? null,
        ])->toArray();

        $this->dispatch('valider-vente', [
            'panier'              => $items,
            'client_id'           => $this->clientId,
            'mode_paiement'       => $modePaiement,
            'reference_paiement'  => $referencePaiement,
            'total'               => $total,
            'remise'              => $remise,
            'pourboire'           => max(0, (int) $pourboire),
            'code_reduction_id'   => $codeReductionId,
            'imprimer'            => $imprimer,
            'montant_cash'        => $montantCash,
            'montant_mobile'      => $montantMobile,
            'montant_carte'       => $montantCarte,
        ]);
    }

    // ── Validation vente à crédit ──

    public function validerVenteCredit(array $panier, int $apport, int $nbEcheances, string $frequence, ?string $codeReductionId = null)
    {
        if (empty($panier)) return;

        if (! $this->clientId) {
            session()->flash('error', 'Un client est obligatoire pour une vente à crédit.');
            return;
        }

        $totalBrut = (int) array_sum(array_map(fn($i) => $i['prix'] * $i['quantite'], $panier));
        $remise = 0;
        if ($codeReductionId) {
            $code = CodeReduction::find($codeReductionId);
            if ($code) $remise = $code->calculerRemise($totalBrut);
        }
        $total = max(0, $totalBrut - $remise);
        $reste = max(0, $total - $apport);

        $vente = \App\Models\Vente::create([
            'institut_id'     => $this->institutId(),
            'client_id'       => $this->clientId,
            'user_id'         => auth()->id(),
            'total'           => $total,
            'montant_paye'    => $apport,
            'remise'          => $remise,
            'code_reduction_id' => $codeReductionId,
            'mode_paiement'   => 'credit',
            'credit_statut'   => $reste > 0 ? 'en_cours' : 'solde',
            'statut'          => 'validee',
            'ip_address'      => request()->ip(),
        ]);

        foreach ($panier as $item) {
            $vente->items()->create([
                'type'          => $item['type'] ?? 'produit',
                'item_id'       => $item['id'] ?? \Illuminate\Support\Str::uuid(),
                'nom_snapshot'  => $item['nom'] ?? 'Article',
                'prix_snapshot' => $item['prix'] ?? 0,
                'quantite'      => $item['quantite'] ?? 1,
                'sous_total'    => ($item['prix'] ?? 0) * ($item['quantite'] ?? 1),
            ]);
        }

        $credit = \App\Models\Credit::create([
            'vente_id'       => $vente->id,
            'institut_id'    => $this->institutId(),
            'client_id'      => $this->clientId,
            'montant_total'  => $total,
            'apport_initial' => $apport,
            'reste_a_payer'  => $reste,
            'nb_echeances'   => $nbEcheances,
            'frequence'      => $frequence,
            'date_debut'     => now(),
            'date_fin_prevue'=> $this->calculerDateFinCredit(now(), $nbEcheances, $frequence),
            'statut'         => $reste > 0 ? 'en_cours' : 'solde',
        ]);

        // Générer les échéances
        if ($reste > 0) {
            $parEcheance = (int) round($reste / $nbEcheances);
            $date = now()->copy();
            for ($i = 0; $i < $nbEcheances; $i++) {
                $date = $frequence === 'hebdomadaire' ? $date->addWeek() : $date->addMonth();
                $montant = ($i === $nbEcheances - 1)
                    ? $reste - ($parEcheance * ($nbEcheances - 1))
                    : $parEcheance;
                \App\Models\Echeance::create([
                    'credit_id'   => $credit->id,
                    'institut_id' => $this->institutId(),
                    'numero'      => $i + 1,
                    'date_prevue' => $date->toDateString(),
                    'montant'     => max(0, $montant),
                    'statut'      => 'en_attente',
                ]);
            }
        }

        // Enregistrer l'apport comme paiement
        if ($apport > 0) {
            \App\Models\PaiementCredit::create([
                'credit_id'     => $credit->id,
                'institut_id'   => $this->institutId(),
                'montant'       => $apport,
                'mode_paiement' => 'cash',
                'encaisse_par'  => auth()->id(),
                'notes'         => 'Apport initial',
                'created_at'    => now(),
            ]);
        }

        // Réinitialiser Alpine
        $this->dispatch('reset-caisse');
        session()->flash('success', 'Vente à crédit enregistrée — reste : ' . number_format($reste, 0, ',', ' ') . ' FCFA');
    }

    private function calculerDateFinCredit($date, int $nb, string $freq): string
    {
        $d = $date->copy();
        for ($i = 0; $i < $nb; $i++) {
            $d = $freq === 'hebdomadaire' ? $d->addWeek() : $d->addMonth();
        }
        return $d->toDateString();
    }

    // ── Render : charge le catalogue (cache 1h), Alpine gère le reste ──

    public function render()
    {
        $institutId = $this->institutId();

        $catalog = \Illuminate\Support\Facades\Cache::remember(
            'caisse_catalog_' . $institutId,
            now()->addHour(),
            function () use ($institutId) {
                return [
                    'prestations' => Prestation::where('institut_id', $institutId)
                        ->where('actif', true)
                        ->with('categorie:id,nom')
                        ->orderBy('categorie_id')
                        ->orderBy('nom')
                        ->get()
                        ->map(fn ($p) => [
                            'id'            => $p->id,
                            'nom'           => $p->nom,
                            'prix'          => (int) $p->prix,
                            'duree'         => $p->duree,
                            'categorie_id'  => $p->categorie_id,
                            'categorie_nom' => $p->categorie?->nom,
                        ]),
                    'produits' => Produit::where('institut_id', $institutId)
                        ->where('actif', true)
                        ->with('categorie:id,nom')
                        ->orderBy('nom')
                        ->get()
                        ->map(fn ($p) => [
                            'id'            => $p->id,
                            'nom'           => $p->nom,
                            'prix'          => (int) $p->prix_vente,
                            'stock'         => $p->stock,
                            'photo'         => $p->photo ? asset('storage/' . $p->photo) : null,
                            'categorie_id'  => $p->categorie_id,
                            'categorie_nom' => $p->categorie?->nom,
                        ]),
                    // Catégories non vides (onglets) + toutes (vente rapide) — une seule requête, dérivé en PHP
                    'allCatPrestations' => CategoriePrestation::where('institut_id', $institutId)
                        ->orderBy('ordre')->orderBy('nom')
                        ->get()
                        ->map(fn ($c) => [
                            'id'      => $c->id,
                            'nom'     => $c->nom,
                            'nonVide' => $c->prestations()->where('actif', true)->exists(),
                        ]),
                    'allCatProduits' => CategorieProduit::where('institut_id', $institutId)
                        ->orderBy('nom')
                        ->get()
                        ->map(fn ($c) => [
                            'id'      => $c->id,
                            'nom'     => $c->nom,
                            'nonVide' => $c->produits()->where('actif', true)->exists(),
                        ]),
                ];
            }
        );

        // Dériver les listes filtrées (onglets) depuis le cache
        $catPrestations = collect($catalog['allCatPrestations'])->filter(fn ($c) => $c['nonVide'])->values();
        $catProduits    = collect($catalog['allCatProduits'])->filter(fn ($c) => $c['nonVide'])->values();
        // Nettoyer le flag nonVide avant de passer à la vue
        $allCatPrestations = collect($catalog['allCatPrestations'])->map(fn ($c) => ['id' => $c['id'], 'nom' => $c['nom']]);
        $allCatProduits    = collect($catalog['allCatProduits'])->map(fn ($c) => ['id' => $c['id'], 'nom' => $c['nom']]);

        return view('livewire.caisse', [
            'prestations'        => $catalog['prestations'],
            'produits'           => $catalog['produits'],
            'catPrestations'     => $catPrestations,
            'catProduits'        => $catProduits,
            'allCatPrestations'  => $allCatPrestations,
            'allCatProduits'     => $allCatProduits,
            'allClients' => Client::where('institut_id', $institutId)
                ->where('actif', true)
                ->orderBy('prenom')
                ->limit(200)
                ->get()
                ->map(fn ($c) => [
                    'id'        => $c->id,
                    'nom'       => $c->nom_complet,
                    'initiale'  => strtoupper(substr($c->prenom ?? $c->nom ?? '?', 0, 1)),
                    'telephone' => $c->telephone ?? '',
                    'search'    => mb_strtolower(($c->prenom ?? '') . ' ' . ($c->nom ?? '') . ' ' . ($c->telephone ?? '')),
                ]),
        ]);
    }
}
