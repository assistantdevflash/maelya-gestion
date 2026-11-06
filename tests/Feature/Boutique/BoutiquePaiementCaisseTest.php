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

    public function test_le_paiement_en_ligne_est_desactive_par_defaut(): void
    {
        $this->assertFalse((bool) config('services.geniuspay.boutique_enabled'));
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
