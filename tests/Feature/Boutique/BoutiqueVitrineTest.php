<?php

namespace Tests\Feature\Boutique;

use App\Models\Abonnement;
use App\Models\CategorieProduit;
use App\Models\Produit;
use App\Models\PlanAbonnement;
use App\Models\ProduitImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Vitrine publique de la boutique : prix facturé, panier local et coût en
 * requêtes de la page d'accueil.
 */
class BoutiqueVitrineTest extends TestCase
{
    use RefreshDatabase;

    private function boutique(int $nbProduits = 6): array
    {
        $institut = $this->creerInstitut([
            'boutique_active'           => true,
            'boutique_option_active'    => true,
            'boutique_option_expire_le' => now()->addMonth()->toDateString(),
            'boutique_frais_livraison'  => 1000,
        ]);

        $owner = User::factory()->create([
            'prenom' => 'Awa', 'nom_famille' => 'Proprio', 'role' => 'admin',
            'actif' => true, 'institut_id' => $institut->id,
        ]);
        $institut->forceFill(['proprietaire_id' => $owner->id])->save();

        $plan = PlanAbonnement::create([
            'nom' => 'Premium', 'slug' => 'premium', 'duree_type' => 'mensuel',
            'duree_jours' => 30, 'prix' => 9900, 'actif' => true,
        ]);
        Abonnement::create([
            'user_id' => $owner->id, 'plan_id' => $plan->id, 'montant' => 0,
            'periode' => 'essai', 'statut' => 'actif',
            'debut_le' => now(), 'expire_le' => now()->addDays(30),
        ]);

        $categorie = CategorieProduit::create([
            'institut_id' => $institut->id,
            'nom'         => 'Divers',
        ]);

        for ($i = 1; $i <= $nbProduits; $i++) {
            $produit = Produit::create([
                'institut_id'      => $institut->id,
                'categorie_id'     => $categorie->id,
                'nom'              => 'Produit ' . $i,
                'prix_vente'       => 1000 * $i,
                'prix_achat'       => 500,
                'stock'            => 10,
                'seuil_alerte'     => 1,
                'actif'            => true,
                'visible_boutique' => true,
            ]);

            ProduitImage::create([
                'produit_id' => $produit->id,
                'chemin'     => 'produits/img' . $i . '.jpg',
                'principale' => true,
                'ordre'      => 0,
            ]);
        }

        return [$institut, $owner];
    }

    public function test_le_panier_recoit_le_prix_promotionnel(): void
    {
        [$institut] = $this->boutique(2);
        $produit = Produit::first();
        $produit->update(['prix_vente' => 8000, 'prix_promo' => 4000]);

        $reponse = $this->get(route('shop.index', $institut->slug))->assertOk();
        $json = collect($reponse->viewData('produitsJson'))->firstWhere('id', $produit->id);

        // Le prix ajouté au panier doit être celui que le serveur facturera
        $this->assertSame(4000, $json['prix']);
        $this->assertSame(8000, $json['prix_vente']);
    }

    public function test_le_panier_n_est_pas_vide_avant_la_confirmation_du_serveur(): void
    {
        [$institut] = $this->boutique(1);

        $html = $this->get(route('shop.commander.form', $institut->slug))
            ->assertOk()
            ->getContent();

        // Le vidage avant envoi faisait perdre la sélection en cas d'erreur
        $this->assertStringNotContainsString("localStorage.removeItem('panier_", $html);
    }

    public function test_le_panier_est_vide_apres_une_commande_reussie(): void
    {
        [$institut] = $this->boutique(1);
        $produit = Produit::first();

        $reponse = $this->post(route('shop.commander', $institut->slug), [
            'prenom' => 'Zaouri', 'nom' => 'Basile',
            'telephone' => '0748606366', 'adresse' => 'Bingerville',
            'panier' => [['produit_id' => $produit->id, 'quantite' => 1]],
        ])->assertRedirect();

        $html = $this->followingRedirects()->get($reponse->headers->get('Location'))->getContent();

        $this->assertStringContainsString("localStorage.removeItem('panier_", $html);
    }

    public function test_la_liste_ne_provoque_pas_de_requetes_en_cascade(): void
    {
        [$institut] = $this->boutique(12);

        DB::enableQueryLog();
        $this->get(route('shop.index', $institut->slug))->assertOk();
        $requetes = count(DB::getQueryLog());
        DB::disableQueryLog();

        // 12 produits avec catégorie + image : le nombre de requêtes ne doit
        // pas dépendre du nombre de produits.
        $this->assertLessThanOrEqual(10, $requetes, "{$requetes} requêtes pour la vitrine.");
    }
}
