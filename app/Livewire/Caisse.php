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
        if (strlen($this->clientSearch) < 2) {
            return collect();
        }

        return Client::where('institut_id', $this->institutId())
            ->where('actif', true)
            ->where(function ($q) {
                $q->where('prenom', 'like', "%{$this->clientSearch}%")
                    ->orWhere('nom', 'like', "%{$this->clientSearch}%")
                    ->orWhere('telephone', 'like', "%{$this->clientSearch}%");
            })
            ->limit(5)
            ->get();
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

    public function valider(array $panier, string $modePaiement, ?int $montantRemis, string $referencePaiement, ?string $codeReductionId, bool $imprimer = false)
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
