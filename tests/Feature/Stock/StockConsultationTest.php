<?php

namespace Tests\Feature\Stock;

use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pages de consultation du stock : historique des mouvements et fiche produit.
 *
 * Les mouvements étaient enregistrés depuis toujours mais aucun écran ne les
 * affichait, et le produit n'avait pas de page de détail (`resource` limité).
 */
class StockConsultationTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(): array
    {
        EtablissementType::create([
            'code' => 'salon_coiffure', 'libelle' => 'Salon de coiffure',
            'actif' => true, 'position' => 1,
        ]);

        $admin = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($admin->institut_id);

        $produit = Produit::withoutGlobalScopes()->create([
            'institut_id'  => $institut->id,
            'nom'          => 'Huile de ricin',
            'reference'    => 'Ricin-01',
            'prix_achat'   => 1500,
            'prix_vente'   => 3000,
            'stock'        => 10,
            'seuil_alerte' => 2,
            'unite'        => 'flacon',
            'actif'        => true,
        ]);

        return [$admin, $institut, $produit];
    }

    public function test_l_historique_des_mouvements_affiche_les_operations(): void
    {
        [$admin, , $produit] = $this->contexte();

        $stock = app(StockService::class);
        $stock->entrer($produit, 5, ['note' => 'Livraison fournisseur']);
        $stock->sortir($produit, 3, ['note' => 'Vente comptoir']);

        $this->actingAs($admin)
            ->get(route('dashboard.stock.mouvements'))
            ->assertOk()
            ->assertSee('Mouvements de stock')
            ->assertSee('Huile de ricin')
            ->assertSee('Livraison fournisseur')
            ->assertSee('Vente comptoir');
    }

    public function test_l_historique_se_filtre_par_type(): void
    {
        [$admin, , $produit] = $this->contexte();

        $stock = app(StockService::class);
        $stock->entrer($produit, 5, ['note' => 'Arrivage']);
        $stock->sortir($produit, 3, ['note' => 'Sortie caisse']);

        $this->actingAs($admin)
            ->get(route('dashboard.stock.mouvements', ['type' => 'entree']))
            ->assertOk()
            ->assertSee('Arrivage')
            ->assertDontSee('Sortie caisse');
    }

    public function test_l_historique_se_filtre_par_produit(): void
    {
        [$admin, $institut, $produit] = $this->contexte();

        $autre = Produit::withoutGlobalScopes()->create([
            'institut_id' => $institut->id, 'nom' => 'Shampoing doux',
            'prix_achat' => 800, 'prix_vente' => 1500, 'stock' => 4,
            'seuil_alerte' => 1, 'unite' => 'flacon', 'actif' => true,
        ]);

        $stock = app(StockService::class);
        $stock->entrer($produit, 2, ['note' => 'Note ricin']);
        $stock->entrer($autre, 2, ['note' => 'Note shampoing']);

        $this->actingAs($admin)
            ->get(route('dashboard.stock.mouvements', ['q' => 'Ricin']))
            ->assertOk()
            ->assertSee('Note ricin')
            ->assertDontSee('Note shampoing');
    }

    public function test_les_mouvements_d_un_autre_etablissement_ne_fuient_pas(): void
    {
        [$admin, $institut, $produit] = $this->contexte();

        $autreInstitut = Institut::create([
            'nom' => 'Autre salon', 'slug' => 'autre-salon-mv', 'type' => 'salon_coiffure', 'actif' => true,
        ]);
        $produitAutre = Produit::withoutGlobalScopes()->create([
            'institut_id' => $autreInstitut->id, 'nom' => 'Produit confidentiel',
            'prix_achat' => 100, 'prix_vente' => 200, 'stock' => 1,
            'seuil_alerte' => 1, 'unite' => 'u', 'actif' => true,
        ]);

        app(StockService::class)->entrer($produitAutre, 1, ['note' => 'Secret autre institut']);
        app(StockService::class)->entrer($produit, 1, ['note' => 'Visible ici']);

        $this->actingAs($admin)
            ->get(route('dashboard.stock.mouvements'))
            ->assertOk()
            ->assertSee('Visible ici')
            ->assertDontSee('Secret autre institut');
    }

    public function test_la_fiche_produit_affiche_les_indicateurs_et_l_historique(): void
    {
        [$admin, , $produit] = $this->contexte();

        app(StockService::class)->entrer($produit, 5, ['note' => 'Arrivage test']);

        $this->actingAs($admin)
            ->get(route('dashboard.produits.show', $produit))
            ->assertOk()
            ->assertSee('Huile de ricin')
            ->assertSee('Valeur du stock')
            ->assertSee('Marge unitaire')
            ->assertSee('Derniers mouvements')
            ->assertSee('Arrivage test');
    }

    public function test_la_fiche_produit_est_inaccessible_a_un_autre_etablissement(): void
    {
        [$admin] = $this->contexte();

        $autreInstitut = Institut::create([
            'nom' => 'Salon tiers', 'slug' => 'salon-tiers-mv', 'type' => 'salon_coiffure', 'actif' => true,
        ]);
        $produitAutre = Produit::withoutGlobalScopes()->create([
            'institut_id' => $autreInstitut->id, 'nom' => 'Produit tiers',
            'prix_achat' => 100, 'prix_vente' => 200, 'stock' => 1,
            'seuil_alerte' => 1, 'unite' => 'u', 'actif' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.produits.show', $produitAutre))
            ->assertNotFound();
    }
}
