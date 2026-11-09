<?php

namespace Tests\Feature\Stock;

use App\Models\CategorieProduit;
use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Liste des produits : filtres de stock et actions groupées.
 */
class ProduitsActionsGroupeesTest extends TestCase
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

        $categorie = CategorieProduit::create([
            'institut_id' => $institut->id, 'nom' => 'Soins',
        ]);

        $creer = fn (string $nom, int $stock, int $seuil, bool $visible = true) =>
            Produit::withoutGlobalScopes()->create([
                'institut_id'      => $institut->id,
                'categorie_id'     => $categorie->id,
                'nom'              => $nom,
                'prix_achat'       => 1000,
                'prix_vente'       => 2000,
                'stock'            => $stock,
                'seuil_alerte'     => $seuil,
                'unite'            => 'u',
                'actif'            => true,
                'visible_boutique' => $visible,
            ]);

        return [
            $admin,
            $institut,
            $categorie,
            $creer('Huile', 1, 5),
            $creer('Masque', 0, 3),
            $creer('Shampoing', 40, 5),
        ];
    }

    public function test_le_filtre_rupture_ne_montre_que_les_stocks_a_zero(): void
    {
        [$admin] = $this->contexte();

        $this->actingAs($admin)
            ->get(route('dashboard.produits.index', ['stock' => 'rupture']))
            ->assertOk()
            ->assertSee('Masque')
            ->assertDontSee('Huile')
            ->assertDontSee('Shampoing');
    }

    public function test_le_filtre_stock_bas_montre_les_produits_sous_seuil(): void
    {
        [$admin] = $this->contexte();

        $this->actingAs($admin)
            ->get(route('dashboard.produits.index', ['stock' => 'bas']))
            ->assertOk()
            ->assertSee('Huile')
            ->assertSee('Masque')
            ->assertDontSee('Shampoing');
    }

    public function test_la_recherche_couvre_le_code_barre(): void
    {
        [$admin, $institut] = $this->contexte();

        Produit::withoutGlobalScopes()->create([
            'institut_id' => $institut->id, 'nom' => 'Sérum précieux',
            'reference' => 'SER-9', 'code_barre' => '3760123456789',
            'prix_achat' => 1000, 'prix_vente' => 2000, 'stock' => 5,
            'seuil_alerte' => 1, 'unite' => 'u', 'actif' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.produits.index', ['q' => '3760123456789']))
            ->assertOk()
            ->assertSee('Sérum précieux')
            ->assertDontSee('Shampoing');
    }

    public function test_l_action_groupee_masque_de_la_boutique(): void
    {
        [$admin, , , $huile, $masque] = $this->contexte();

        $this->actingAs($admin)->post(route('dashboard.produits.actions-groupees'), [
            'ids'    => [$huile->id, $masque->id],
            'action' => 'boutique_off',
        ])->assertRedirect();

        $this->assertFalse((bool) $huile->fresh()->visible_boutique);
        $this->assertFalse((bool) $masque->fresh()->visible_boutique);
    }

    public function test_l_action_groupee_change_la_categorie(): void
    {
        [$admin, $institut, , $huile, $masque] = $this->contexte();

        $nouvelle = CategorieProduit::create([
            'institut_id' => $institut->id, 'nom' => 'Accessoires',
        ]);

        $this->actingAs($admin)->post(route('dashboard.produits.actions-groupees'), [
            'ids'          => [$huile->id, $masque->id],
            'action'       => 'categorie',
            'categorie_id' => $nouvelle->id,
        ])->assertRedirect();

        $this->assertSame($nouvelle->id, $huile->fresh()->categorie_id);
        $this->assertSame($nouvelle->id, $masque->fresh()->categorie_id);
    }

    public function test_les_actions_groupees_ne_touchent_pas_aux_produits_d_un_autre_etablissement(): void
    {
        [$admin] = $this->contexte();

        $autreInstitut = Institut::create([
            'nom' => 'Salon tiers', 'slug' => 'salon-tiers-ag', 'type' => 'salon_coiffure', 'actif' => true,
        ]);
        $produitTiers = Produit::withoutGlobalScopes()->create([
            'institut_id' => $autreInstitut->id, 'nom' => 'Produit tiers',
            'prix_achat' => 100, 'prix_vente' => 200, 'stock' => 3,
            'seuil_alerte' => 1, 'unite' => 'u', 'actif' => true,
            'visible_boutique' => true,
        ]);

        $this->actingAs($admin)->post(route('dashboard.produits.actions-groupees'), [
            'ids'    => [$produitTiers->id],
            'action' => 'desactiver',
        ])->assertRedirect();

        $this->assertTrue((bool) $produitTiers->fresh()->actif);
    }
}
