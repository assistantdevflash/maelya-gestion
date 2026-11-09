<?php

namespace Tests\Feature\Stock;

use App\Models\Institut;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Services\StockInsuffisantException;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * StockService : porte d'entrée unique des modifications de stock.
 *
 * Il garantit trois choses que l'ancien code dispersé ne garantissait pas :
 * chaque écriture laisse un mouvement, les valeurs avant/après sont réelles,
 * et une sortie au-delà du disponible est refusée.
 */
class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StockService::class);
    }

    private function produit(array $attrs = [], ?Institut $institut = null): Produit
    {
        $institut ??= $this->creerInstitut();

        return Produit::withoutGlobalScopes()->create(array_merge([
            'institut_id'  => $institut->id,
            'nom'          => 'Huile capillaire',
            'reference'    => 'HUIL-001',
            'prix_achat'   => 2000,
            'prix_vente'   => 3500,
            'stock'        => 10,
            'seuil_alerte' => 3,
            'unite'        => 'flacon',
            'actif'        => true,
        ], $attrs));
    }

    public function test_une_entree_augmente_le_stock_et_trace_le_mouvement(): void
    {
        $produit = $this->produit();

        $mouvement = $this->service->entrer($produit, 5, ['note' => 'Livraison']);

        $this->assertSame(15, $produit->fresh()->stock);
        $this->assertInstanceOf(MouvementStock::class, $mouvement);
        $this->assertSame('entree', $mouvement->type);
        $this->assertSame(10, $mouvement->stock_avant);
        $this->assertSame(15, $mouvement->stock_apres);
        $this->assertSame(5, $mouvement->quantite);
    }

    public function test_une_entree_avec_prix_recalcule_le_cmp(): void
    {
        // 10 unités à 2 000 F, puis 10 unités à 4 000 F → CMP 3 000 F
        $produit = $this->produit(['cout_moyen_pondere' => 2000]);

        $this->service->entrer($produit, 10, ['prix_unitaire' => 4000]);

        $this->assertSame(3000, (int) $produit->fresh()->cout_moyen_pondere);
        $this->assertSame(20, $produit->fresh()->stock);
    }

    public function test_une_sortie_diminue_le_stock(): void
    {
        $produit = $this->produit();

        $this->service->sortir($produit, 4);

        $this->assertSame(6, $produit->fresh()->stock);
        $this->assertDatabaseHas('mouvements_stock', [
            'produit_id'  => $produit->id,
            'type'        => 'sortie_vente',
            'stock_avant' => 10,
            'stock_apres' => 6,
        ]);
    }

    public function test_une_sortie_superieure_au_stock_est_refusee(): void
    {
        $produit = $this->produit();

        $this->expectException(StockInsuffisantException::class);

        try {
            $this->service->sortir($produit, 11);
        } finally {
            // Le stock ne doit pas bouger et aucun mouvement ne doit exister
            $this->assertSame(10, $produit->fresh()->stock);
            $this->assertSame(0, MouvementStock::withoutGlobalScopes()->count());
        }
    }

    public function test_l_etablissement_peut_autoriser_le_stock_negatif(): void
    {
        $institut = $this->creerInstitut();
        $institut->forceFill(['stock_negatif_autorise' => true])->save();

        $produit = $this->produit(['stock' => 2], $institut);

        $mouvement = $this->service->sortir($produit, 5);

        $this->assertSame(-3, $produit->fresh()->stock);

        // Le mouvement doit refléter la réalité, pas être plafonné à zéro
        $this->assertSame(2, $mouvement->stock_avant);
        $this->assertSame(-3, $mouvement->stock_apres);
    }

    public function test_un_ajustement_identique_ne_cree_pas_de_mouvement(): void
    {
        $produit = $this->produit(['stock' => 7]);

        $mouvement = $this->service->ajuster($produit, 7);

        $this->assertNull($mouvement);
        $this->assertSame(0, MouvementStock::withoutGlobalScopes()->count());
    }

    public function test_un_ajustement_trace_l_ecart(): void
    {
        $produit = $this->produit(['stock' => 7]);

        $mouvement = $this->service->ajuster($produit, 4, ['note' => 'Casse']);

        $this->assertSame(4, $produit->fresh()->stock);
        $this->assertSame('correction', $mouvement->type);
        $this->assertSame(3, $mouvement->quantite);   // écart en valeur absolue
        $this->assertSame(7, $mouvement->stock_avant);
        $this->assertSame(4, $mouvement->stock_apres);
    }

    public function test_un_ajustement_negatif_est_ramene_a_zero(): void
    {
        $produit = $this->produit(['stock' => 3]);

        $this->service->ajuster($produit, -5);

        $this->assertSame(0, $produit->fresh()->stock);
    }

    public function test_le_mouvement_est_rattache_a_l_institut_du_produit(): void
    {
        // Aucun utilisateur connecté (cas de la boutique publique) : le
        // mouvement doit quand même porter le bon établissement.
        $produit = $this->produit();

        $mouvement = $this->service->sortir($produit, 1);

        $this->assertSame($produit->institut_id, $mouvement->institut_id);
    }
}
