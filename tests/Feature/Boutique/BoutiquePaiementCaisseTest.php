<?php

namespace Tests\Feature\Boutique;

use App\Models\CategorieProduit;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Moyens de paiement proposés au moment de la commande.
 *
 * Le paiement en ligne (GeniusPay) est désactivé tant que l'intégration n'est
 * pas validée : seul le règlement à la livraison doit apparaître.
 */
class BoutiquePaiementCaisseTest extends TestCase
{
    use RefreshDatabase;

    private function boutique(): array
    {
        $institut = $this->creerInstitut([
            'boutique_active'           => true,
            'boutique_option_active'    => true,
            'boutique_option_expire_le' => now()->addMonth()->toDateString(),
            'boutique_frais_livraison'  => 1000,
        ]);

        $owner = User::factory()->create([
            'prenom' => 'Awa', 'nom_famille' => 'Proprio',
            'role' => 'admin', 'actif' => true, 'institut_id' => $institut->id,
        ]);
        $institut->forceFill(['proprietaire_id' => $owner->id])->save();

        $plan = PlanAbonnement::create([
            'nom' => 'Premium', 'slug' => 'premium', 'duree_type' => 'mensuel',
            'duree_jours' => 30, 'prix' => 9900, 'actif' => true,
        ]);

        \App\Models\Abonnement::create([
            'user_id' => $owner->id, 'plan_id' => $plan->id, 'montant' => 0,
            'periode' => 'essai', 'statut' => 'actif',
            'debut_le' => now(), 'expire_le' => now()->addDays(30),
        ]);

        $categorie = CategorieProduit::create([
            'institut_id' => $institut->id,
            'nom'         => 'Épicerie',
        ]);

        Produit::create([
            'institut_id'  => $institut->id,
            'categorie_id' => $categorie->id,
            'nom'          => 'Riz 5 kg',
            'prix_vente'   => 4500,
            'prix_achat'   => 3800,
            'stock'        => 10,
            'seuil_alerte' => 1,
            'actif'        => true,
            'visible_boutique' => true,
        ]);

        return [$institut, $owner];
    }

    private function methodeGeniusPay(array $attributs = []): \App\Models\PaymentMethod
    {
        return \App\Models\PaymentMethod::create(array_merge([
            'code'                      => 'geniuspay',
            'name'                      => 'Genius Pay',
            'type'                      => 'gateway',
            'provider'                  => 'geniuspay',
            'description'               => 'Wave, Orange Money, MTN MoMo',
            'is_active'                 => true,
            'boutique_checkout_enabled' => false,
            'auto_validate'             => true,
            'position'                  => 1,
        ], $attributs));
    }

    public function test_le_paiement_en_ligne_est_desactive_par_defaut(): void
    {
        $methode = $this->methodeGeniusPay();

        $this->assertFalse($methode->boutique_checkout_enabled);
    }

    public function test_le_super_admin_peut_activer_le_paiement_en_ligne_du_checkout(): void
    {
        $methode = $this->methodeGeniusPay();

        $superAdmin = User::factory()->create([
            'prenom' => 'Super', 'nom_famille' => 'Admin',
            'role' => 'super_admin', 'actif' => true,
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('admin.payment-methods.toggle-boutique', $methode))
            ->assertRedirect();

        $this->assertTrue($methode->fresh()->boutique_checkout_enabled);
        // Le réglage « établissements » n'est pas touché
        $this->assertTrue($methode->fresh()->is_active);

        // Second appui : retour en arrière
        $this->actingAs($superAdmin)
            ->patch(route('admin.payment-methods.toggle-boutique', $methode));

        $this->assertFalse($methode->fresh()->boutique_checkout_enabled);
    }

    public function test_l_option_apparait_quand_le_super_admin_l_a_activee(): void
    {
        [$institut] = $this->boutique();
        $this->methodeGeniusPay(['boutique_checkout_enabled' => true]);
        config(['services.geniuspay.api_key' => 'cle-de-test']);

        $html = $this->get(route('shop.commander.form', $institut->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Payer maintenant en ligne', $html);
    }

    public function test_l_option_reste_masquee_sans_identifiants_du_prestataire(): void
    {
        [$institut] = $this->boutique();
        $this->methodeGeniusPay(['boutique_checkout_enabled' => true]);
        config(['services.geniuspay.api_key' => null]);

        $html = $this->get(route('shop.commander.form', $institut->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Payer maintenant en ligne', $html);
    }

    public function test_le_checkout_ne_propose_que_le_paiement_a_la_livraison(): void
    {
        [$institut] = $this->boutique();

        $html = $this->get(route('shop.commander.form', $institut->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Paiement à la livraison', $html);
        $this->assertStringNotContainsString('Payer maintenant en ligne', $html);
        $this->assertStringNotContainsString('GeniusPay', $html);
    }

    public function test_une_commande_ne_declenche_aucun_paiement_en_ligne_quand_l_option_est_desactivee(): void
    {
        [$institut] = $this->boutique();
        $this->methodeGeniusPay(['boutique_checkout_enabled' => false]);
        config(['services.geniuspay.api_key' => 'cle-de-test']);

        $produit = Produit::first();

        $this->post(route('shop.commander', $institut->slug), [
            'prenom' => 'Zaouri', 'nom' => 'Basile',
            'telephone' => '0748606366', 'adresse' => 'Bingerville',
            'panier' => [['produit_id' => $produit->id, 'quantite' => 1]],
            // Tentative de forcer le paiement en ligne malgré tout
            'paiement_en_ligne' => 1,
            'mode_paiement'     => 'mobile_money',
        ])->assertRedirect();

        $this->assertSame(0, \App\Models\PaymentTransaction::count(), 'Aucune transaction ne doit être créée.');

        $commande = \App\Models\Commande::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('cash', $commande->mode_paiement);
        $this->assertFalse((bool) $commande->payee);
    }
}
