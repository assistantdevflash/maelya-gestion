<?php

namespace Tests\Feature\Stock;

use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bascule « boutique » et « vedette » appelée en JSON depuis la liste.
 *
 * Ces deux actions étaient des formulaires POST : chacune rechargeait toute la
 * page. Elles doivent désormais répondre en JSON pour être appliquées sans
 * rafraîchissement, tout en conservant la redirection classique pour les
 * appels non-AJAX.
 */
class ProduitBasculeJsonTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(): array
    {
        EtablissementType::create([
            'code' => 'salon_coiffure', 'libelle' => 'Salon',
            'actif' => true, 'position' => 1,
        ]);

        $admin = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($admin->institut_id);

        $produit = Produit::withoutGlobalScopes()->create([
            'institut_id' => $institut->id, 'nom' => 'Huile',
            'prix_achat' => 1000, 'prix_vente' => 2000, 'stock' => 5,
            'seuil_alerte' => 1, 'unite' => 'flacon', 'actif' => true,
            'photo' => 'produits/huile.jpg',
            'visible_boutique' => false,
            'featured' => false,
        ]);

        return [$admin, $institut, $produit];
    }

    public function test_la_bascule_boutique_repond_en_json(): void
    {
        [$admin, , $produit] = $this->contexte();

        $this->actingAs($admin)
            ->postJson(route('dashboard.produits.toggle-visible', $produit))
            ->assertOk()
            ->assertJson(['ok' => true, 'visible_boutique' => true]);

        $this->assertTrue((bool) $produit->fresh()->visible_boutique);
    }

    public function test_la_bascule_vedette_repond_en_json(): void
    {
        [$admin, , $produit] = $this->contexte();

        $this->actingAs($admin)
            ->postJson(route('dashboard.produits.toggle-featured', $produit))
            ->assertOk()
            ->assertJson(['ok' => true, 'featured' => true]);

        $this->assertTrue((bool) $produit->fresh()->featured);
    }

    public function test_un_refus_repond_422_avec_un_message(): void
    {
        [$admin, $institut] = $this->contexte();

        $sansImage = Produit::withoutGlobalScopes()->create([
            'institut_id' => $institut->id, 'nom' => 'Sans visuel',
            'prix_achat' => 100, 'prix_vente' => 200, 'stock' => 1,
            'seuil_alerte' => 1, 'unite' => 'u', 'actif' => true,
            'visible_boutique' => false,
        ]);

        $this->actingAs($admin)
            ->postJson(route('dashboard.produits.toggle-visible', $sansImage))
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertFalse((bool) $sansImage->fresh()->visible_boutique);
    }

    public function test_un_appel_classique_redirige_toujours(): void
    {
        [$admin, , $produit] = $this->contexte();

        $this->actingAs($admin)
            ->post(route('dashboard.produits.toggle-visible', $produit))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue((bool) $produit->fresh()->visible_boutique);
    }
}
