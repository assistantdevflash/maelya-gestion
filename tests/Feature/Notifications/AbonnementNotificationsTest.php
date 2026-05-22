<?php

namespace Tests\Feature\Notifications;

use App\Mail\AbonnementRejete;
use App\Mail\AbonnementValide;
use App\Models\Abonnement;
use App\Models\CommercialCommission;
use App\Models\CommercialParrainage;
use App\Models\CommercialProfile;
use App\Models\Notif;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests des notifications liées aux abonnements :
 * - Mail AbonnementRejete (mailable)
 * - rejeter()   → mail + push + in-app
 * - souscrire() → in-app admins (nouvelle_demande)
 * - valider()   → in-app commercial (commission_gagnee)
 */
class AbonnementNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('commercial_config')->insertOrIgnore(['taux' => 20, 'duree_mois' => 6]);
        Mail::fake();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function creerSuperAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'actif' => true]);
    }

    private function creerAbonnementEnAttente(User $user, int $montant = 5000): Abonnement
    {
        $plan = $this->creerPlan([
            'slug'        => 'mensuel-' . uniqid(),
            'duree_type'  => 'mensuel',
            'duree_jours' => 30,
            'prix'        => $montant,
        ]);

        return Abonnement::create([
            'user_id'             => $user->id,
            'plan_id'             => $plan->id,
            'montant'             => $montant,
            'periode'             => 'mensuel',
            'statut'              => 'en_attente',
            'reference_transfert' => 'REF-TEST-' . uniqid(),
        ]);
    }

    private function creerCommercialLieA(User $proprietaire): array
    {
        $commUser = User::factory()->create(['role' => 'commercial', 'actif' => true]);
        $profil   = CommercialProfile::create(['user_id' => $commUser->id, 'code' => 'COM' . rand(100, 999)]);
        $parrainage = CommercialParrainage::create([
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $proprietaire->id,
            'expire_le'       => now()->addMonths(6)->toDateString(),
        ]);
        return compact('commUser', 'profil', 'parrainage');
    }

    // =========================================================================
    // Mailable AbonnementRejete
    // =========================================================================

    public function test_abonnement_rejete_mailable_peut_etre_instancie(): void
    {
        $user       = $this->creerAdmin();
        $abonnement = $this->creerAbonnementEnAttente($user);

        $mailable = new AbonnementRejete($abonnement);

        $this->assertSame('emails.abonnement-rejete', $mailable->content()->view);
    }

    public function test_abonnement_rejete_sujet_contient_app_name(): void
    {
        $user       = $this->creerAdmin();
        $abonnement = $this->creerAbonnementEnAttente($user);

        $envelope = (new AbonnementRejete($abonnement))->envelope();

        $this->assertStringContainsStringIgnoringCase('validée', $envelope->subject);
        $this->assertStringContainsString(config('app.name'), $envelope->subject);
    }

    // =========================================================================
    // AdminAbonnementController::rejeter()
    // =========================================================================

    public function test_rejeter_envoie_mail_abonnement_rejete(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $etabl      = $this->creerAdmin();
        $abonnement = $this->creerAbonnementEnAttente($etabl);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.rejeter', $abonnement), [
                'notes_admin' => 'Preuve insuffisante.',
            ])
            ->assertRedirect();

        Mail::assertSent(AbonnementRejete::class, fn ($m) => $m->hasTo($etabl->email));
    }

    public function test_rejeter_cree_notif_inapp_type_abonnement_rejete(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $etabl      = $this->creerAdmin();
        $abonnement = $this->creerAbonnementEnAttente($etabl);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.rejeter', $abonnement), [
                'notes_admin' => 'Montant incorrect.',
            ]);

        $this->assertDatabaseHas('notifs', [
            'user_id' => $etabl->id,
            'type'    => 'abonnement_rejete',
            'lu'      => false,
        ]);
    }

    public function test_rejeter_avec_motif_inclut_motif_dans_notif(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $etabl      = $this->creerAdmin();
        $abonnement = $this->creerAbonnementEnAttente($etabl);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.rejeter', $abonnement), [
                'notes_admin' => 'Paiement non reçu.',
            ]);

        $notif = Notif::where('user_id', $etabl->id)->where('type', 'abonnement_rejete')->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('Paiement non reçu', $notif->corps);
    }

    public function test_rejeter_sans_motif_utilise_message_generique(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $etabl      = $this->creerAdmin();
        $abonnement = $this->creerAbonnementEnAttente($etabl);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.rejeter', $abonnement));

        $notif = Notif::where('user_id', $etabl->id)->where('type', 'abonnement_rejete')->first();
        $this->assertNotNull($notif);
        $this->assertNotEmpty($notif->corps);
    }

    // =========================================================================
    // AbonnementController::souscrire() — notif admins
    // =========================================================================

    public function test_souscrire_cree_notif_inapp_nouvelle_demande_pour_chaque_admin(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $etabl      = $this->creerAdmin();
        $plan       = $this->creerPlan([
            'slug'       => 'mensuel-test',
            'duree_type' => 'mensuel',
            'prix'       => 5000,
            'actif'      => true,
        ]);

        $this->actingAs($etabl)
            ->post(route('abonnement.souscrire', $plan), [
                'periode'             => 'mensuel',
                'reference_transfert' => 'REF-XYZ-001',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifs', [
            'user_id' => $superAdmin->id,
            'type'    => 'nouvelle_demande',
        ]);
    }

    public function test_souscrire_notif_contient_nom_du_plan(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $etabl      = $this->creerAdmin();
        $plan       = $this->creerPlan([
            'slug'       => 'gold-test',
            'nom'        => 'Gold',
            'duree_type' => 'mensuel',
            'prix'       => 8000,
            'actif'      => true,
        ]);

        $this->actingAs($etabl)
            ->post(route('abonnement.souscrire', $plan), [
                'periode'             => 'mensuel',
                'reference_transfert' => 'REF-456',
            ]);

        $notif = Notif::where('user_id', $superAdmin->id)->where('type', 'nouvelle_demande')->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('Gold', $notif->titre);
    }

    // =========================================================================
    // AdminAbonnementController::valider() — notif commission_gagnee
    // =========================================================================

    public function test_valider_cree_notif_commission_gagnee_pour_commercial(): void
    {
        $superAdmin  = $this->creerSuperAdmin();
        $proprietaire = $this->creerAdmin();
        ['commUser' => $commUser] = $this->creerCommercialLieA($proprietaire);

        $abonnement = $this->creerAbonnementEnAttente($proprietaire, 10000);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement))
            ->assertRedirect();

        $this->assertDatabaseHas('notifs', [
            'user_id' => $commUser->id,
            'type'    => 'commission_gagnee',
        ]);
    }

    public function test_valider_sans_commission_ne_cree_pas_notif_commission(): void
    {
        // Aucun parrainage commercial → pas de commission
        $superAdmin  = $this->creerSuperAdmin();
        $proprietaire = $this->creerAdmin();
        $abonnement  = $this->creerAbonnementEnAttente($proprietaire, 10000);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        $this->assertDatabaseMissing('notifs', ['type' => 'commission_gagnee']);
    }

    public function test_valider_cree_notif_abonnement_valide_pour_etablissement(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $etabl      = $this->creerAdmin();
        $abonnement = $this->creerAbonnementEnAttente($etabl);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        $this->assertDatabaseHas('notifs', [
            'user_id' => $etabl->id,
            'type'    => 'abonnement_valide',
        ]);
    }

    public function test_valider_envoie_mail_abonnement_valide(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $etabl      = $this->creerAdmin();
        $abonnement = $this->creerAbonnementEnAttente($etabl);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        Mail::assertSent(AbonnementValide::class, fn ($m) => $m->hasTo($etabl->email));
    }
}
