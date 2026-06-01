<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategorieProduit;
use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduitCodeBarreTest extends TestCase
{
    use RefreshDatabase;

    private function premiumAdmin()
    {
        $admin = $this->creerAdmin();
        $premium = \App\Models\PlanAbonnement::create([
            'nom' => 'Premium', 'slug' => 'premium', 'duree_type' => 'mensuel',
            'duree_jours' => 30, 'prix' => 10000, 'actif' => true,
        ]);
        $admin->abonnements()->update(['plan_id' => $premium->id]);
        return $admin;
    }

    public function test_recherche_par_code_barre_trouve_le_produit(): void
    {
        $admin = $this->premiumAdmin();
        $cat = CategorieProduit::create(['institut_id' => $admin->institut_id, 'nom' => 'C']);
        $produit = Produit::create([
            'institut_id'  => $admin->institut_id,
            'categorie_id' => $cat->id,
            'nom'          => 'Crème jour',
            'code_barre'   => '3760123456789',
            'prix_achat'   => 2000,
            'prix_vente'   => 5000,
            'stock'        => 10,
            'seuil_alerte' => 2,
            'unite'        => 'pièce',
            'actif'        => true,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('dashboard.produits.scan', ['code' => '3760123456789']));

        $response->assertOk();
        $response->assertJson([
            'found' => true,
            'id'    => $produit->id,
            'nom'   => 'Crème jour',
            'prix'  => 5000,
        ]);
    }

    public function test_recherche_par_code_barre_inconnu_retourne_404(): void
    {
        $admin = $this->premiumAdmin();

        $response = $this->actingAs($admin)
            ->getJson(route('dashboard.produits.scan', ['code' => 'INCONNU']));

        $response->assertStatus(404);
        $response->assertJson(['found' => false]);
    }
}
