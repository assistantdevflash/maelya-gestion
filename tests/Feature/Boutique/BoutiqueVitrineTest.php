<?php

namespace Tests\Feature\Boutique;

use App\Models\Abonnement;
use App\Models\CategorieProduit;
use App\Models\Produit;
use App\Models\PlanAbonnement;
use App\Models\ProduitImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_la_fiche_produit_expose_les_donnees_structurees(): void
    {
        [$institut] = $this->boutique(1);
        $produit = Produit::first();

        $this->get(route('shop.produit', [$institut->slug, $produit->id]))
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type": "Product"', false)
            ->assertSee('"priceCurrency": "XOF"', false)
            // La balise canonical évite les doublons d'URL
            ->assertSee('rel="canonical"', false);
    }

    /**
     * `x-if` et `x-for` n'acceptent qu'un seul élément racine. Avec deux
     * frères, Alpine ne rend que le premier : c'est ce qui faisait disparaître
     * la flèche « image suivante » de la galerie.
     */
    public function test_les_templates_alpine_n_ont_qu_une_seule_racine(): void
    {
        [$institut] = $this->boutique(1);
        $produit = Produit::first();

        $pages = [
            'vitrine' => route('shop.index', $institut->slug),
            'fiche'   => route('shop.produit', [$institut->slug, $produit->id]),
        ];

        foreach ($pages as $nom => $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML($html);
            libxml_clear_errors();

            $templates = (new \DOMXPath($dom))->query('//template[@x-if or @x-for]');

            $this->assertGreaterThan(0, $templates->count(), "Aucun template Alpine sur {$nom}.");

            foreach ($templates as $template) {
                $racines = 0;
                foreach ($template->childNodes as $enfant) {
                    if ($enfant->nodeType === XML_ELEMENT_NODE) {
                        $racines++;
                    }
                }

                $this->assertLessThanOrEqual(
                    1,
                    $racines,
                    "Un template Alpine de {$nom} a {$racines} racines : seul le premier sera rendu."
                );
            }
        }
    }

    public function test_la_galerie_expose_les_deux_fleches_de_navigation(): void
    {
        [$institut] = $this->boutique(1);
        $produit = Produit::first();

        $this->get(route('shop.produit', [$institut->slug, $produit->id]))
            ->assertOk()
            ->assertSee('aria-label="Image précédente"', false)
            ->assertSee('aria-label="Image suivante"', false);
    }

    public function test_la_recherche_et_le_tri_ne_sont_pas_indexes(): void
    {
        [$institut] = $this->boutique(2);

        $this->get(route('shop.index', ['slug' => $institut->slug]))
            ->assertOk()
            ->assertSee('name="robots" content="index, follow"', false);

        $this->get(route('shop.index', ['slug' => $institut->slug, 'q' => 'Produit', 'tri' => 'prix_asc']))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false)
            // La recherche doit renvoyer un résultat
            ->assertSee('Produit 1');
    }

    public function test_le_checkout_recoit_le_catalogue_pour_resynchroniser_le_panier(): void
    {
        [$institut] = $this->boutique(2);
        $produit = Produit::first();

        $html = $this->get(route('shop.commander.form', $institut->slug))
            ->assertOk()
            ->getContent();

        // Le prix réel du catalogue est transmis au navigateur
        $this->assertStringContainsString('reconcilierPanier', $html);
        $this->assertStringContainsString($produit->id, $html);
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
        $mesurer = function (int $nbProduits): int {
            [$institut] = $this->boutique($nbProduits);
            Cache::flush();

            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get(route('shop.index', $institut->slug))->assertOk();
            $requetes = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $requetes;
        };

        // Le coût en requêtes doit être constant : 3 ou 23 produits chargent
        // les mêmes relations (catégorie + image principale) en une fois.
        $petite = $mesurer(3);
        $grande = $mesurer(23);

        $this->assertSame(
            $petite,
            $grande,
            "Le nombre de requêtes dépend du nombre de produits : {$petite} puis {$grande}."
        );
        $this->assertLessThanOrEqual(14, $petite, "{$petite} requêtes pour la vitrine.");
    }
}
