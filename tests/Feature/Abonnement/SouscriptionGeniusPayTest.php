<?php

namespace Tests\Feature\Abonnement;

use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\PaymentMethod;
use App\Models\PlanAbonnement;
use App\Models\User;
use App\Services\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Souscription à un plan payé par GeniusPay.
 *
 * Le garde-fou de rétrogradation calcule `$analyseLimites` dans `souscrire()`,
 * puis délègue à `souscrireViaGeniusPay()`. Cette analyse n'était pas transmise
 * à la méthode déléguée : la métadonnée « limites » de l'abonnement lisait une
 * variable inexistante et la page tombait en erreur 500 au moment de payer.
 */
class SouscriptionGeniusPayTest extends TestCase
{
    use RefreshDatabase;

    private function planCible(array $attrs = []): PlanAbonnement
    {
        return PlanAbonnement::create(array_merge([
            'nom'           => 'Premium',
            'slug'          => 'premium',
            'duree_type'    => 'mensuel',
            'duree_jours'   => 30,
            'prix'          => 4900,
            'max_instituts' => 1,
            'max_employes'  => 3,
            'actif'         => true,
            'ordre'         => 1,
        ], $attrs));
    }

    private function methodeGeniusPay(): PaymentMethod
    {
        return PaymentMethod::create([
            'code'      => 'geniuspay',
            'name'      => 'GeniusPay',
            'is_active' => true,
        ]);
    }

    private function proprietaire(): User
    {
        $institut = Institut::create([
            'nom'  => 'Institut Bercail',
            'slug'  => 'institut-bercail-genius',
            'type'  => 'salon_coiffure',
            'actif' => true,
        ]);

        $user = User::factory()->create([
            'prenom'      => 'Awa',
            'nom_famille' => 'Kone',
            'role'        => 'admin',
            'actif'       => true,
            'institut_id' => $institut->id,
        ]);

        $institut->forceFill(['proprietaire_id' => $user->id])->save();

        return $user;
    }

    private function simulerPasserelle(array $retour = ['checkout_url' => 'https://geniuspay.test/checkout/abc']): void
    {
        $this->mock(PaymentGatewayManager::class, function ($mock) use ($retour) {
            $mock->shouldReceive('initiate')->once()->andReturn($retour);
        });
    }

    public function test_la_souscription_geniuspay_ne_plante_pas(): void
    {
        $user  = $this->proprietaire();
        $plan  = $this->planCible();
        $this->methodeGeniusPay();
        $this->simulerPasserelle();

        $reponse = $this->actingAs($user)->post(route('abonnement.souscrire', $plan), [
            'periode'          => 'mensuel',
            'methode_paiement' => 'geniuspay',
        ]);

        $reponse->assertRedirect('https://geniuspay.test/checkout/abc');
    }

    public function test_l_abonnement_conserve_l_analyse_des_limites(): void
    {
        $user = $this->proprietaire();
        $plan = $this->planCible();
        $this->methodeGeniusPay();
        $this->simulerPasserelle();

        $this->actingAs($user)->post(route('abonnement.souscrire', $plan), [
            'periode'          => 'mensuel',
            'methode_paiement' => 'geniuspay',
        ]);

        $abonnement = Abonnement::where('user_id', $user->id)->latest()->first();

        $this->assertNotNull($abonnement);
        $this->assertSame('en_attente', $abonnement->statut);
        $this->assertSame('geniuspay', $abonnement->metadata['payment_method'] ?? null);

        // La clé « limites » doit être complète, sans quoi le webhook et
        // l'écran d'administration ne peuvent pas appliquer la rétrogradation.
        $this->assertArrayHasKey('limites', $abonnement->metadata);
        $this->assertArrayHasKey('instituts_en_sommeil', $abonnement->metadata['limites']);
        $this->assertArrayHasKey('employes_en_sommeil', $abonnement->metadata['limites']);
        $this->assertArrayHasKey('features_perdues', $abonnement->metadata['limites']);
    }

    public function test_une_retrogradation_non_confirmee_est_bloquee_avant_tout_paiement(): void
    {
        $user = $this->proprietaire();
        $plan = $this->planCible();

        // Le compte possède deux établissements, la cible n'en autorise qu'un
        Institut::create([
            'nom'             => 'Deuxième salon',
            'slug'            => 'deuxieme-salon-genius',
            'type'            => 'salon_coiffure',
            'actif'           => true,
            'proprietaire_id' => $user->id,
        ]);

        $this->methodeGeniusPay();

        // Aucune passerelle ne doit être appelée : on bloque en amont
        $this->mock(PaymentGatewayManager::class, function ($mock) {
            $mock->shouldNotReceive('initiate');
        });

        $this->actingAs($user)
            ->post(route('abonnement.souscrire', $plan), [
                'periode'          => 'mensuel',
                'methode_paiement' => 'geniuspay',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull(Abonnement::where('user_id', $user->id)->first());
    }
}
