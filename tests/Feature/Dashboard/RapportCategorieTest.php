<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategoriePrestation;
use App\Models\CategorieProduit;
use App\Models\Prestation;
use App\Models\Produit;
use App\Models\Vente;
use App\Models\VenteItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RapportCategorieTest extends TestCase
{
    use RefreshDatabase;

    public function test_rapport_categories_agrege_par_categorie(): void
    {
        $admin = $this->creerAdmin();
        // Activer feature:finances
        $premium = \App\Models\PlanAbonnement::create([
            'nom' => 'Premium', 'slug' => 'premium', 'duree_type' => 'mensuel',
            'duree_jours' => 30, 'prix' => 10000, 'actif' => true,
        ]);
        $admin->abonnements()->update(['plan_id' => $premium->id]);

        $catPresta = CategoriePrestation::create([
            'institut_id' => $admin->institut_id, 'nom' => 'Coiffure', 'ordre' => 1,
        ]);
        $presta = Prestation::create([
            'institut_id'  => $admin->institut_id,
            'categorie_id' => $catPresta->id,
            'nom' => 'Coupe', 'prix' => 5000, 'duree' => 30, 'actif' => true,
        ]);

        $catProd = CategorieProduit::create([
            'institut_id' => $admin->institut_id, 'nom' => 'Soins',
        ]);
        $prod = Produit::create([
            'institut_id'  => $admin->institut_id,
            'categorie_id' => $catProd->id,
            'nom' => 'Shampoing', 'prix_achat' => 1000, 'prix_vente' => 3000,
            'stock' => 50, 'seuil_alerte' => 5, 'unite' => 'pièce', 'actif' => true,
        ]);

        // Crée une vente avec 2 items
        $vente = Vente::create([
            'institut_id' => $admin->institut_id,
            'user_id'     => $admin->id,
            'total'       => 8000,
            'mode_paiement' => 'cash',
            'montant_cash'  => 8000,
            'statut'        => 'validee',
        ]);
        VenteItem::create([
            'vente_id' => $vente->id, 'type' => 'prestation', 'item_id' => $presta->id,
            'nom_snapshot' => 'Coupe', 'prix_snapshot' => 5000, 'quantite' => 1, 'sous_total' => 5000,
        ]);
        VenteItem::create([
            'vente_id' => $vente->id, 'type' => 'produit', 'item_id' => $prod->id,
            'nom_snapshot' => 'Shampoing', 'prix_snapshot' => 3000, 'quantite' => 1, 'sous_total' => 3000,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.rapports.categories'));

        $response->assertOk();
        $response->assertSee('Coiffure');
        $response->assertSee('Soins');
        $response->assertSee('5 000');
        $response->assertSee('3 000');
    }
}
