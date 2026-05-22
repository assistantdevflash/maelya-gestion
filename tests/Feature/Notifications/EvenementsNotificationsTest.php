<?php

namespace Tests\Feature\Notifications;

use App\Mail\CommercialCommissionPayee;
use App\Mail\NouvelInstitutInscrit;
use App\Models\Abonnement;
use App\Models\CommercialCommission;
use App\Models\CommercialParrainage;
use App\Models\CommercialProfile;
use App\Models\Notif;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests des notifications in-app pour les événements :
 * - RDV créé       → rdv_confirme (établissement)
 * - RDV rappel J-1 → rdv_rappel (établissement)
 * - Commission payée → commission_payee (commercial)
 * - Inscription      → bienvenue (user) + nouvel_institut (admins) + nouveau_filleul (commercial)
 */
class EvenementsNotificationsTest extends TestCase
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

    /**
     * Admin avec un plan slug "premium" pour débloquer la feature RDV.
     */
    private function creerAdminAvecRdv(): User
    {
        $user = $this->creerAdmin();
        $user->abonnements()->update(['statut' => 'expire']);

        $plan = $this->creerPlan(['slug' => 'premium', 'nom' => 'Premium']);
        Abonnement::create([
            'user_id'   => $user->id,
            'plan_id'   => $plan->id,
            'montant'   => 10000,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now(),
            'expire_le' => now()->addDays(30),
        ]);

        return $user->fresh();
    }

    private function creerCommercialProfile(string $code = 'COM001'): array
    {
        $commUser = User::factory()->create(['role' => 'commercial', 'actif' => true]);
        $profil   = CommercialProfile::create(['user_id' => $commUser->id, 'code' => $code]);
        // Un parrainage factice est nécessaire (parrainage_id NOT NULL dans commercial_commissions)
        $proprio  = $this->creerAdmin(['email' => 'proprio-' . $code . '@test.com']);
        $parrainage = CommercialParrainage::create([
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $proprio->id,
            'expire_le'       => now()->addMonths(6)->toDateString(),
        ]);
        return compact('commUser', 'profil', 'parrainage');
    }

    // =========================================================================
    // RdvController::store() — rdv_confirme
    // =========================================================================

    public function test_creation_rdv_cree_notif_rdv_confirme(): void
    {
        $user = $this->creerAdminAvecRdv();

        $this->actingAs($user)
            ->post(route('dashboard.rdv.store'), [
                'client_nom'    => 'Aminata Bah',
                'debut_date'    => now()->addDay()->toDateString(),
                'debut_heure'   => '10:00',
                'duree_minutes' => 30,
                'statut'        => 'confirme',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifs', [
            'user_id' => $user->id,
            'type'    => 'rdv_confirme',
        ]);
    }

    public function test_notif_rdv_confirme_contient_nom_client(): void
    {
        $user = $this->creerAdminAvecRdv();

        $this->actingAs($user)
            ->post(route('dashboard.rdv.store'), [
                'client_nom'    => 'Fatou Diallo',
                'debut_date'    => now()->addDay()->toDateString(),
                'debut_heure'   => '14:00',
                'duree_minutes' => 45,
                'statut'        => 'confirme',
            ]);

        $notif = Notif::where('user_id', $user->id)->where('type', 'rdv_confirme')->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('Fatou Diallo', $notif->titre);
    }

    public function test_notif_rdv_confirme_url_pointe_vers_rdv(): void
    {
        $user = $this->creerAdminAvecRdv();

        $this->actingAs($user)
            ->post(route('dashboard.rdv.store'), [
                'client_nom'    => 'Test Client',
                'debut_date'    => now()->addDay()->toDateString(),
                'debut_heure'   => '09:00',
                'duree_minutes' => 30,
                'statut'        => 'confirme',
            ]);

        $rdv   = RendezVous::where('client_nom', 'Test Client')->first();
        $notif = Notif::where('user_id', $user->id)->where('type', 'rdv_confirme')->first();

        $this->assertNotNull($rdv);
        $this->assertNotNull($notif);
        $this->assertStringContainsString($rdv->id, $notif->url);
    }

    // =========================================================================
    // Artisan rdv:rappels — rdv_rappel
    // =========================================================================

    public function test_rdv_rappels_cree_notif_rdv_rappel(): void
    {
        $user  = $this->creerAdmin();
        $demain = now()->addDay()->setHour(10)->setMinute(0)->setSecond(0);

        // Créer un RDV demain directement (hors scope auth)
        RendezVous::withoutGlobalScopes()->forceCreate([
            'id'            => \Illuminate\Support\Str::uuid(),
            'institut_id'   => $user->institut_id,
            'client_nom'    => 'Cliente Rappel',
            'debut_le'      => $demain,
            'duree_minutes' => 30,
            'statut'        => 'confirme',
            'rappel_envoye' => false,
        ]);

        $this->artisan('rdv:rappels')->assertSuccessful();

        $this->assertDatabaseHas('notifs', [
            'user_id' => $user->id,
            'type'    => 'rdv_rappel',
        ]);
    }

    public function test_rdv_rappels_marque_rappel_envoye(): void
    {
        $user   = $this->creerAdmin();
        $demain = now()->addDay()->setHour(11)->setMinute(0)->setSecond(0);

        RendezVous::withoutGlobalScopes()->forceCreate([
            'id'            => \Illuminate\Support\Str::uuid(),
            'institut_id'   => $user->institut_id,
            'client_nom'    => 'Cliente Mark',
            'debut_le'      => $demain,
            'duree_minutes' => 30,
            'statut'        => 'confirme',
            'rappel_envoye' => false,
        ]);

        $this->artisan('rdv:rappels');

        $this->assertDatabaseHas('rendez_vous', [
            'client_nom'    => 'Cliente Mark',
            'rappel_envoye' => true,
        ]);
    }

    public function test_rdv_rappels_ne_renvoie_pas_si_deja_envoye(): void
    {
        $user   = $this->creerAdmin();
        $demain = now()->addDay()->setHour(11)->setMinute(0)->setSecond(0);

        RendezVous::withoutGlobalScopes()->forceCreate([
            'id'            => \Illuminate\Support\Str::uuid(),
            'institut_id'   => $user->institut_id,
            'client_nom'    => 'Cliente Skip',
            'debut_le'      => $demain,
            'duree_minutes' => 30,
            'statut'        => 'confirme',
            'rappel_envoye' => true, // déjà envoyé
        ]);

        $this->artisan('rdv:rappels');

        $this->assertDatabaseMissing('notifs', ['type' => 'rdv_rappel']);
    }

    // =========================================================================
    // AdminCommercialController::payerCommission() — commission_payee
    // =========================================================================

    public function test_payer_commission_cree_notif_commission_payee(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        ['commUser' => $commUser, 'profil' => $profil, 'parrainage' => $parrainage] = $this->creerCommercialProfile();

        $commission = CommercialCommission::create([
            'commercial_id' => $profil->id,
            'parrainage_id' => $parrainage->id,
            'abonnement_id' => $this->creerAbonnementFactice($commUser)->id,
            'montant_base'  => 10000,
            'taux'          => 20,
            'montant'       => 2000,
            'statut'        => 'en_attente',
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('admin.commerciaux.commissions.payer', $commission))
            ->assertRedirect();

        $this->assertDatabaseHas('notifs', [
            'user_id' => $commUser->id,
            'type'    => 'commission_payee',
        ]);
    }

    public function test_payer_commission_notif_contient_montant(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        ['commUser' => $commUser, 'profil' => $profil, 'parrainage' => $parrainage] = $this->creerCommercialProfile('COM002');

        $commission = CommercialCommission::create([
            'commercial_id' => $profil->id,
            'parrainage_id' => $parrainage->id,
            'abonnement_id' => $this->creerAbonnementFactice($commUser)->id,
            'montant_base'  => 10000,
            'taux'          => 20,
            'montant'       => 2000,
            'statut'        => 'en_attente',
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('admin.commerciaux.commissions.payer', $commission));

        $notif = Notif::where('user_id', $commUser->id)->where('type', 'commission_payee')->first();
        $this->assertNotNull($notif);
        $this->assertStringContainsString('2', $notif->titre); // contient "2 000" ou "2000"
    }

    public function test_payer_commission_envoie_mail_commercial(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        ['commUser' => $commUser, 'profil' => $profil, 'parrainage' => $parrainage] = $this->creerCommercialProfile('COM003');

        $commission = CommercialCommission::create([
            'commercial_id' => $profil->id,
            'parrainage_id' => $parrainage->id,
            'abonnement_id' => $this->creerAbonnementFactice($commUser)->id,
            'montant_base'  => 5000,
            'taux'          => 20,
            'montant'       => 1000,
            'statut'        => 'en_attente',
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('admin.commerciaux.commissions.payer', $commission));

        Mail::assertSent(CommercialCommissionPayee::class, fn ($m) => $m->hasTo($commUser->email));
    }

    // =========================================================================
    // InscriptionController::store() — bienvenue + nouvel_institut + filleul
    // =========================================================================

    public function test_inscription_cree_notif_bienvenue_pour_nouvel_utilisateur(): void
    {
        $this->creerSuperAdmin(); // au moins 1 admin pour notifyAdmins

        $this->post(route('inscription.store'), $this->donneesInscription())
            ->assertRedirect(route('dashboard.index'));

        $user = User::where('email', 'test-inscription@example.com')->first();
        $this->assertNotNull($user);

        $this->assertDatabaseHas('notifs', [
            'user_id' => $user->id,
            'type'    => 'bienvenue',
        ]);
    }

    public function test_inscription_cree_notif_nouvel_institut_pour_admins(): void
    {
        $admin = $this->creerSuperAdmin();

        $this->post(route('inscription.store'), $this->donneesInscription())
            ->assertRedirect();

        $this->assertDatabaseHas('notifs', [
            'user_id' => $admin->id,
            'type'    => 'nouvel_institut',
        ]);
    }

    public function test_inscription_avec_code_commercial_cree_notif_nouveau_filleul(): void
    {
        $this->creerSuperAdmin();

        ['commUser' => $commUser, 'profil' => $profil] = $this->creerCommercialProfile('VIP001');

        $donnees = array_merge($this->donneesInscription(), ['code_parrainage' => 'VIP001']);

        $this->post(route('inscription.store'), $donnees)
            ->assertRedirect();

        $this->assertDatabaseHas('notifs', [
            'user_id' => $commUser->id,
            'type'    => 'nouveau_filleul',
        ]);
    }

    public function test_inscription_sans_code_commercial_ne_cree_pas_notif_filleul(): void
    {
        $this->creerSuperAdmin();
        ['commUser' => $commUser] = $this->creerCommercialProfile('VIP002');

        $this->post(route('inscription.store'), $this->donneesInscription());

        $this->assertDatabaseMissing('notifs', [
            'user_id' => $commUser->id,
            'type'    => 'nouveau_filleul',
        ]);
    }

    public function test_inscription_envoie_mail_bienvenue_et_nouvel_institut(): void
    {
        $this->creerSuperAdmin();

        $this->post(route('inscription.store'), $this->donneesInscription());

        Mail::assertSent(\App\Mail\BienvenueMaelya::class);
        Mail::assertSent(NouvelInstitutInscrit::class);
    }

    // =========================================================================
    // Helpers privés
    // =========================================================================

    private function donneesInscription(array $override = []): array
    {
        return array_merge([
            'nom_institut'       => 'Salon Test Notifs',
            'type_institut'      => 'salon_coiffure',
            'ville'              => 'Abidjan',
            'telephone_institut' => '0102030405',
            'prenom'             => 'Testeur',
            'nom_famille'        => 'Notifs',
            'email'              => 'test-inscription@example.com',
            'telephone'          => '0707070707',
            'password'           => 'Password123!',
            'password_confirmation' => 'Password123!',
            'cgu'                => '1',
        ], $override);
    }

    /**
     * Crée un abonnement factice pour pouvoir créer une CommercialCommission.
     * La commission référence un abonnement — on crée un abonnement fictif.
     */
    private function creerAbonnementFactice(User $user): Abonnement
    {
        $plan = $this->creerPlanPayant();
        return Abonnement::create([
            'user_id'  => $user->id,
            'plan_id'  => $plan->id,
            'montant'  => 5000,
            'periode'  => 'mensuel',
            'statut'   => 'actif',
            'debut_le' => now(),
            'expire_le'=> now()->addDays(30),
        ]);
    }

    private function creerPlanPayant(string $slugSuffix = ''): \App\Models\PlanAbonnement
    {
        return $this->creerPlan([
            'slug'       => 'comm-' . $slugSuffix . uniqid(),
            'duree_type' => 'mensuel',
            'prix'       => 5000,
        ]);
    }
}
