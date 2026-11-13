<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategorieProduit;
use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Valorisation du stock.
 *
 * Un produit sans CMP ni prix d'achat était valorisé 0 : la « valeur du
 * stock » n'était donc que la somme des produits dont le coût était connu,
 * présentée comme si elle couvrait tout le catalogue.
 */
class StockValorisationTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private string $categorieId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->creerAdmin();

        $this->categorieId = CategorieProduit::create([
            'institut_id' => $this->admin->institut_id,
            'nom'         => 'Perruques',
            'ordre'       => 1,
        ])->id;
    }

    private function produit(array $attributs = []): Produit
    {
        return Produit::create(array_merge([
            'institut_id'  => $this->admin->institut_id,
            'categorie_id' => $this->categorieId,
            'nom'          => 'Produit ' . uniqid(),
            'prix_vente'   => 5000,
            'prix_achat'   => 0,
            'stock'        => 10,
            'seuil_alerte' => 2,
            'actif'        => true,
        ], $attributs));
    }

    public function test_un_produit_sans_prix_d_achat_n_a_pas_de_cout_connu(): void
    {
        $sansCout = $this->produit(['prix_achat' => 0]);
        $avecCout = $this->produit(['prix_achat' => 2500]);

        $this->assertFalse($sansCout->aUnCoutConnu());
        $this->assertTrue($avecCout->aUnCoutConnu());
        $this->assertSame(2500, $avecCout->cout_unitaire);
    }

    public function test_le_cmp_prime_sur_le_prix_d_achat(): void
    {
        $p = $this->produit(['prix_achat' => 2500, 'cout_moyen_pondere' => 3000]);

        $this->assertSame(3000, $p->cout_unitaire);
    }

    public function test_la_valeur_du_stock_n_additionne_que_les_produits_valorises(): void
    {
        $this->produit(['stock' => 9, 'prix_achat' => 2500]);   // 22 500
        $this->produit(['stock' => 43, 'prix_achat' => 0]);     // ignoré
        $this->produit(['stock' => 43, 'prix_achat' => 0]);     // ignoré

        $reponse = $this->actingAs($this->admin)->get(route('dashboard.stock.index'));

        $reponse->assertOk()
            ->assertViewHas('valeurStock', 22500)
            ->assertViewHas('nbValorises', 1)
            ->assertViewHas('nbSansCout', 2);
    }

    public function test_les_produits_sans_cout_sont_signales_sur_la_page_stock(): void
    {
        $this->produit(['nom' => 'Perruque rouge', 'prix_achat' => 0]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.stock.index'))
            ->assertOk()
            ->assertSee('produit sans prix d\'achat')
            ->assertSee('Perruque rouge')
            ->assertSee('à renseigner')
            ->assertSee('sur 0 produit valorisé');
    }

    public function test_un_catalogue_entierement_valorise_n_affiche_pas_d_alerte(): void
    {
        $this->produit(['prix_achat' => 2500]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.stock.index'))
            ->assertOk()
            ->assertDontSee('produit sans prix d\'achat')
            ->assertViewHas('nbSansCout', 0);
    }
}
