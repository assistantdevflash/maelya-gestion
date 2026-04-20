<?php

namespace App\Livewire;

use App\Models\CategoriePrestation;
use App\Models\CategorieProduit;
use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\Prestation;
use App\Models\Produit;
use Livewire\Attributes\Computed;
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

    public function mount(?string $client = null)
    {
        $this->clientId = $client;
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
            ->whereRaw('UPPER(code) = ?', [$input])
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

    public function valider(array $panier, string $modePaiement, ?int $montantRemis, string $referencePaiement, ?string $codeReductionId, bool $imprimer = false, ?int $montantCash = null, ?int $montantMobile = null, ?int $montantCarte = null)
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
            'type'     => $item['type'],
            'id'       => $item['id'],
            'nom'      => $item['nom'],
            'prix'     => $item['prix'],
            'quantite' => $item['quantite'],
        ])->toArray();

        $this->dispatch('valider-vente', [
            'panier'              => $items,
            'client_id'           => $this->clientId,
            'mode_paiement'       => $modePaiement,
            'reference_paiement'  => $referencePaiement,
            'total'               => $total,
            'remise'              => $remise,
            'code_reduction_id'   => $codeReductionId,
            'imprimer'            => $imprimer,
            'montant_cash'        => $montantCash,
            'montant_mobile'      => $montantMobile,
            'montant_carte'       => $montantCarte,
        ]);
    }

    // ── Render : charge le catalogue une fois, Alpine gère le reste ──

    public function render()
    {
        $institutId = $this->institutId();

        return view('livewire.caisse', [
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
                    'categorie_id'  => $p->categorie_id,
                    'categorie_nom' => $p->categorie?->nom,
                ]),
            'catPrestations' => CategoriePrestation::where('institut_id', $institutId)
                ->whereHas('prestations', fn ($q) => $q->where('actif', true))
                ->orderBy('ordre')->orderBy('nom')
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'nom' => $c->nom]),
            'catProduits' => CategorieProduit::where('institut_id', $institutId)
                ->whereHas('produits', fn ($q) => $q->where('actif', true))
                ->orderBy('nom')
                ->get()
                ->map(fn ($c) => ['id' => $c->id, 'nom' => $c->nom]),
        ]);
    }
}
