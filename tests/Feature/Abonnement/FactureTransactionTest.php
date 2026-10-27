<?php

namespace Tests\Feature\Abonnement;

use App\Models\Abonnement;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Une facture ne doit être émise que si un paiement a réellement été encaissé.
 *
 * Bug d'origine : les transactions GeniusPay en échec (ou en attente) affichaient
 * un bouton « Facture » et généraient un PDF mentionnant « paiement effectué ».
 */
class FactureTransactionTest extends TestCase
{
    use RefreshDatabase;

    private function creerAbonnement(User $user, array $attrs = []): Abonnement
    {
        $plan = $this->creerPlan(['slug' => 'premium', 'nom' => 'Premium', 'prix' => 13965]);

        return Abonnement::create(array_merge([
            'user_id'   => $user->id,
            'plan_id'   => $plan->id,
            'montant'   => 13965,
            'periode'   => 'trimestre',
            'statut'    => 'en_attente',
            'debut_le'  => now()->toDateString(),
            'expire_le' => now()->addMonths(3)->toDateString(),
        ], $attrs));
    }

    private function creerTransaction(User $user, Abonnement $abo, string $statut, ?string $paidAt = null): PaymentTransaction
    {
        return PaymentTransaction::create([
            'reference'           => 'MGP-' . uniqid(),
            'user_id'             => $user->id,
            'institut_id'         => $user->institut_id,
            'abonnement_id'       => $abo->id,
            'type'                => 'abonnement',
            'amount'              => 13965,
            'currency'            => 'XOF',
            'payment_method_code' => 'geniuspay',
            'status'              => $statut,
            'paid_at'             => $paidAt,
        ]);
    }

    // ── La facture est refusée ────────────────────────────────────────────────

    public function test_pas_de_facture_pour_une_transaction_en_echec(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, ['statut' => 'en_attente']);
        $this->creerTransaction($user, $abo, 'failed');

        $this->actingAs($user)
            ->get(route('abonnement.facture-abonnement', $abo))
            ->assertNotFound();
    }

    public function test_pas_de_facture_pour_une_transaction_annulee(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, ['statut' => 'en_attente']);
        $this->creerTransaction($user, $abo, 'cancelled');

        $this->actingAs($user)
            ->get(route('abonnement.facture-abonnement', $abo))
            ->assertNotFound();
    }

    public function test_pas_de_facture_pour_une_transaction_expiree(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, ['statut' => 'en_attente']);
        $this->creerTransaction($user, $abo, 'expired');

        $this->actingAs($user)
            ->get(route('abonnement.facture-abonnement', $abo))
            ->assertNotFound();
    }

    public function test_pas_de_facture_pour_un_paiement_en_attente_sans_validation(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, ['statut' => 'en_attente']);
        $this->creerTransaction($user, $abo, 'pending');

        $this->actingAs($user)
            ->get(route('abonnement.facture-abonnement', $abo))
            ->assertNotFound();
    }

    public function test_pas_de_facture_pour_un_abonnement_offert(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, ['montant' => 0, 'statut' => 'actif']);

        $this->actingAs($user)
            ->get(route('abonnement.facture-abonnement', $abo))
            ->assertNotFound();
    }

    public function test_pas_de_facture_pour_une_transaction_boutique_en_echec(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, ['statut' => 'en_attente']);

        $tx = PaymentTransaction::create([
            'reference'           => 'MGP-BOUTIQUE-1',
            'user_id'             => $user->id,
            'institut_id'         => $user->institut_id,
            'type'                => 'boutique_activation',
            'amount'              => 3900,
            'currency'            => 'XOF',
            'payment_method_code' => 'geniuspay',
            'status'              => 'failed',
        ]);

        $this->actingAs($user)
            ->get(route('abonnement.facture-transaction', $tx))
            ->assertNotFound();
    }

    // ── La facture est émise ──────────────────────────────────────────────────

    public function test_facture_emise_pour_un_paiement_geniuspay_reussi(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, ['statut' => 'actif']);
        $this->creerTransaction($user, $abo, 'completed', now()->toDateTimeString());

        $this->actingAs($user)
            ->get(route('abonnement.facture-abonnement', $abo))
            ->assertOk();
    }

    public function test_facture_emise_pour_un_abonnement_expire_apres_paiement(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, [
            'statut'    => 'expire',
            'expire_le' => now()->subDay()->toDateString(),
        ]);
        $this->creerTransaction($user, $abo, 'completed', now()->subMonth()->toDateTimeString());

        $this->actingAs($user)
            ->get(route('abonnement.facture-abonnement', $abo))
            ->assertOk();
    }

    public function test_facture_emise_pour_un_virement_valide_par_admin(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, ['statut' => 'actif']);

        $this->actingAs($user)
            ->get(route('abonnement.facture-abonnement', $abo))
            ->assertOk();
    }

    public function test_facture_emise_pour_un_paiement_boutique_reussi(): void
    {
        $user = $this->creerAdmin();

        $tx = PaymentTransaction::create([
            'reference'           => 'MGP-BOUTIQUE-2',
            'user_id'             => $user->id,
            'institut_id'         => $user->institut_id,
            'type'                => 'boutique_activation',
            'amount'              => 3900,
            'currency'            => 'XOF',
            'payment_method_code' => 'geniuspay',
            'status'              => 'completed',
            'paid_at'             => now(),
        ]);

        $this->actingAs($user)
            ->get(route('abonnement.facture-transaction', $tx))
            ->assertOk();
    }

    // ── Historique ────────────────────────────────────────────────────────────

    public function test_historique_masque_la_facture_des_transactions_en_echec(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, ['statut' => 'en_attente']);
        $this->creerTransaction($user, $abo, 'failed');

        $this->actingAs($user)
            ->get(route('abonnement.historique'))
            ->assertOk()
            ->assertSee('Aucune facture')
            ->assertDontSee(route('abonnement.facture-abonnement', $abo), false);
    }

    public function test_historique_affiche_la_facture_des_paiements_reussis(): void
    {
        $user = $this->creerAdmin();
        $abo  = $this->creerAbonnement($user, ['statut' => 'actif']);
        $this->creerTransaction($user, $abo, 'completed', now()->toDateTimeString());

        $this->actingAs($user)
            ->get(route('abonnement.historique'))
            ->assertOk()
            ->assertSee(route('abonnement.facture-abonnement', $abo), false);
    }
}
