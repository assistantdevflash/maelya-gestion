<?php

namespace Tests\Feature\Stock;

use App\Models\MouvementStock;
use App\Models\Produit;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Commande `stock:verifier` : détecte les fiches dont le stock ne correspond
 * plus au dernier mouvement connu (héritage des flux non tracés).
 */
class VerifierStockCommandeTest extends TestCase
{
    use RefreshDatabase;

    private function produit(int $stock): Produit
    {
        return Produit::withoutGlobalScopes()->create([
            'institut_id'  => $this->creerInstitut()->id,
            'nom'          => 'Sérum',
            'prix_achat'   => 1000,
            'prix_vente'   => 2000,
            'stock'        => $stock,
            'seuil_alerte' => 2,
            'unite'        => 'flacon',
            'actif'        => true,
        ]);
    }

    public function test_un_stock_coherent_ne_signale_rien(): void
    {
        $produit = $this->produit(10);
        app(StockService::class)->entrer($produit, 5);

        $this->artisan('stock:verifier')
            ->expectsOutputToContain('Aucun écart')
            ->assertSuccessful();
    }

    public function test_un_stock_divergent_est_signale(): void
    {
        $produit = $this->produit(10);
        app(StockService::class)->entrer($produit, 5);   // mouvement : 10 → 15

        // Écriture directe, comme le faisaient les flux non tracés
        $produit->forceFill(['stock' => 3])->save();

        $this->artisan('stock:verifier')
            ->expectsOutputToContain('incohérent')
            ->assertSuccessful();
    }

    public function test_l_option_corriger_cree_un_mouvement_sans_toucher_au_stock(): void
    {
        $produit = $this->produit(10);
        app(StockService::class)->entrer($produit, 5);
        $produit->forceFill(['stock' => 3])->save();

        $avant = MouvementStock::withoutGlobalScopes()->count();

        $this->artisan('stock:verifier --corriger')->assertSuccessful();

        $this->assertSame(3, $produit->fresh()->stock);                     // stock inchangé
        $this->assertSame($avant + 1, MouvementStock::withoutGlobalScopes()->count());
        $this->assertSame(
            3,
            (int) MouvementStock::withoutGlobalScopes()->orderByDesc('created_at')->orderByDesc('id')->value('stock_apres')
        );
    }
}
