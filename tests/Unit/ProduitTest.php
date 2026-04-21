<?php

namespace Tests\Unit;

use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduitTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduit(array $attrs = []): Produit
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        return Produit::create(array_merge([
            'nom'          => 'Shampoing Test',
            'prix_achat'   => 2000,
            'prix_vente'   => 5000,
            'stock'        => 10,
            'seuil_alerte' => 3,
            'unite'        => 'flacon',
            'actif'        => true,
        ], $attrs));
    }

    public function test_prix_vente_formate_attribute(): void
    {
        $produit = $this->makeProduit(['prix_vente' => 12500]);

        $this->assertSame('12 500 FCFA', $produit->prix_vente_formatte);
    }

    public function test_is_en_alerte_quand_stock_inferieur_ou_egal_seuil(): void
    {
        $produit = $this->makeProduit(['stock' => 2, 'seuil_alerte' => 3]);

        $this->assertTrue($produit->isEnAlerte());
    }

    public function test_is_en_alerte_quand_stock_egal_seuil(): void
    {
        $produit = $this->makeProduit(['stock' => 3, 'seuil_alerte' => 3]);

        $this->assertTrue($produit->isEnAlerte());
    }

    public function test_pas_en_alerte_quand_stock_superieur_seuil(): void
    {
        $produit = $this->makeProduit(['stock' => 10, 'seuil_alerte' => 3]);

        $this->assertFalse($produit->isEnAlerte());
    }

    public function test_scope_en_alerte(): void
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        Produit::create(['nom' => 'P1', 'prix_vente' => 1000, 'prix_achat' => 0,
            'stock' => 1, 'seuil_alerte' => 3, 'unite' => 'u', 'actif' => true]);
        Produit::create(['nom' => 'P2', 'prix_vente' => 1000, 'prix_achat' => 0,
            'stock' => 10, 'seuil_alerte' => 3, 'unite' => 'u', 'actif' => true]);

        $enAlerte = Produit::withoutGlobalScopes()->enAlerte()->get();

        $this->assertEquals(1, $enAlerte->count());
        $this->assertSame('P1', $enAlerte->first()->nom);
    }

    public function test_scope_actif(): void
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        Produit::create(['nom' => 'Actif', 'prix_vente' => 1000, 'prix_achat' => 0,
            'stock' => 5, 'seuil_alerte' => 1, 'unite' => 'u', 'actif' => true]);
        Produit::create(['nom' => 'Inactif', 'prix_vente' => 1000, 'prix_achat' => 0,
            'stock' => 5, 'seuil_alerte' => 1, 'unite' => 'u', 'actif' => false]);

        $actifs = Produit::withoutGlobalScopes()->actif()->get();

        $this->assertTrue($actifs->every(fn($p) => $p->actif));
    }
}
