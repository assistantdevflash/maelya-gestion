<?php

namespace Tests\Feature\Commercial;

use App\Models\Abonnement;
use App\Models\CommercialCommission;
use App\Models\CommercialParrainage;
use App\Models\CommercialProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests du module commercial — accès, CRUD admin, espace commercial.
 */
class CommercialModuleTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function creerCommercial(array $attrs = []): User
    {
        $user = User::factory()->create(array_merge([
            'prenom'      => 'Jean',
            'nom_famille' => 'Dupont',
            'role'        => 'commercial',
            'actif'       => true,
        ], $attrs));

        CommercialProfile::create([
            'user_id'   => $user->id,
            'code'      => 'JD' . rand(1000, 9999),
            'telephone' => '0102030405',
        ]);

        return $user;
    }

    private function creerSuperAdmin(): User
    {
        return User::factory()->create([
            'role'  => 'super_admin',
            'actif' => true,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Seed la table de config
        DB::table('commercial_config')->insertOrIgnore(['taux' => 20, 'duree_mois' => 6]);
    }

    // =========================================================================
    // Accès espace commercial
    // =========================================================================

    public function test_commercial_est_redirige_vers_son_espace_apres_login(): void
    {
        $commercial = $this->creerCommercial();

        $this->post(route('login'), [
            'login'    => $commercial->email,
            'password' => 'password',
        ])->assertRedirect(route('commercial.dashboard'));
    }

    public function test_commercial_peut_acceder_dashboard(): void
    {
        $commercial = $this->creerCommercial();

        $this->actingAs($commercial)
            ->get(route('commercial.dashboard'))
            ->assertOk();
    }

    public function test_commercial_peut_acceder_parrainages(): void
    {
        $commercial = $this->creerCommercial();

        $this->actingAs($commercial)
            ->get(route('commercial.parrainages'))
            ->assertOk();
    }

    public function test_commercial_peut_acceder_commissions(): void
    {
        $commercial = $this->creerCommercial();

        $this->actingAs($commercial)
            ->get(route('commercial.commissions'))
            ->assertOk();
    }

    public function test_admin_ne_peut_pas_acceder_espace_commercial(): void
    {
        $admin = $this->creerAdmin();

        $this->actingAs($admin)
            ->get(route('commercial.dashboard'))
            ->assertForbidden(); // intercepté par role middleware → 403
    }

    public function test_invite_ne_peut_pas_acceder_espace_commercial(): void
    {
        $this->get(route('commercial.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_commercial_redirige_si_tente_acces_dashboard_etablissement(): void
    {
        $commercial = $this->creerCommercial();

        // Le middleware AbonnementActif doit intercepter et rediriger vers commercial.dashboard
        $this->actingAs($commercial)
            ->get(route('dashboard.index'))
            ->assertRedirect(route('commercial.dashboard'));
    }

    // =========================================================================
    // CRUD Admin — commerciaux
    // =========================================================================

    public function test_super_admin_peut_lister_commerciaux(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $this->creerCommercial();

        $this->actingAs($superAdmin)
            ->get(route('admin.commerciaux.index'))
            ->assertOk()
            ->assertSeeText('Commerciaux');
    }

    public function test_super_admin_peut_creer_un_commercial(): void
    {
        $superAdmin = $this->creerSuperAdmin();

        $this->actingAs($superAdmin)
            ->post(route('admin.commerciaux.store'), [
                'prenom'      => 'Marie',
                'nom_famille' => 'Kouassi',
                'email'       => 'marie.k@example.com',
                'telephone'   => '0707070707',
                'password'    => 'Secret1234!',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'marie.k@example.com',
            'role'  => 'commercial',
        ]);

        $user = User::where('email', 'marie.k@example.com')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('commercial_profiles', [
            'user_id' => $user->id,
        ]);

        // Le code généré doit commencer par MA (Marie + K...)
        $profil = CommercialProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profil);
        $this->assertEquals(8, strlen($profil->code)); // MA+KO (4 chars) + 4 chiffres = 8 chars
        $this->assertStringStartsWith('MA', $profil->code);
    }

    public function test_super_admin_peut_voir_detail_commercial(): void
    {
        $superAdmin  = $this->creerSuperAdmin();
        $commercial  = $this->creerCommercial();

        $this->actingAs($superAdmin)
            ->get(route('admin.commerciaux.show', $commercial))
            ->assertOk()
            ->assertSeeText($commercial->prenom);
    }

    public function test_super_admin_peut_desactiver_commercial(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $commercial = $this->creerCommercial(['actif' => true]);

        $this->actingAs($superAdmin)
            ->patch(route('admin.commerciaux.toggle', $commercial))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id'    => $commercial->id,
            'actif' => false,
        ]);
    }

    public function test_super_admin_peut_supprimer_commercial(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $commercial = $this->creerCommercial();

        $this->actingAs($superAdmin)
            ->delete(route('admin.commerciaux.destroy', $commercial))
            ->assertRedirect(route('admin.commerciaux.index'));

        $this->assertDatabaseMissing('users', ['id' => $commercial->id]);
    }

    public function test_super_admin_peut_mettre_a_jour_config(): void
    {
        $superAdmin = $this->creerSuperAdmin();

        $this->actingAs($superAdmin)
            ->patch(route('admin.commerciaux.config'), [
                'taux'       => 15,
                'duree_mois' => 12,
            ])
            ->assertRedirect();

        $config = DB::table('commercial_config')->first();
        $this->assertEquals(15, $config->taux);
        $this->assertEquals(12, $config->duree_mois);
    }

    // =========================================================================
    // Gestion des commissions
    // =========================================================================

    public function test_super_admin_peut_marquer_commission_payee(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $commercial = $this->creerCommercial();
        $profil     = $commercial->commercialProfile;

        $proprietaire = $this->creerAdmin();
        $parrainage = CommercialParrainage::create([
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $proprietaire->id,
            'expire_le'       => now()->addMonths(6)->toDateString(),
        ]);

        $plan = $this->creerPlan();
        $abonnement = Abonnement::create([
            'user_id'   => $proprietaire->id,
            'plan_id'   => $plan->id,
            'montant'   => 10000,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->toDateString(),
            'expire_le' => now()->addDays(30)->toDateString(),
        ]);

        $commission = CommercialCommission::create([
            'commercial_id' => $profil->id,
            'parrainage_id' => $parrainage->id,
            'abonnement_id' => $abonnement->id,
            'montant_base'  => 10000,
            'taux'          => 20,
            'montant'       => 2000,
            'statut'        => 'en_attente',
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('admin.commerciaux.commissions.payer', $commission))
            ->assertRedirect();

        $this->assertDatabaseHas('commercial_commissions', [
            'id'     => $commission->id,
            'statut' => 'payee',
        ]);
    }

    public function test_super_admin_peut_annuler_paiement_commission(): void
    {
        $superAdmin = $this->creerSuperAdmin();
        $commercial = $this->creerCommercial();
        $profil     = $commercial->commercialProfile;

        $proprietaire = $this->creerAdmin();
        $parrainage = CommercialParrainage::create([
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $proprietaire->id,
            'expire_le'       => now()->addMonths(6)->toDateString(),
        ]);

        $plan = $this->creerPlan();
        $abonnement = Abonnement::create([
            'user_id'   => $proprietaire->id,
            'plan_id'   => $plan->id,
            'montant'   => 10000,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->toDateString(),
            'expire_le' => now()->addDays(30)->toDateString(),
        ]);

        $commission = CommercialCommission::create([
            'commercial_id' => $profil->id,
            'parrainage_id' => $parrainage->id,
            'abonnement_id' => $abonnement->id,
            'montant_base'  => 10000,
            'taux'          => 20,
            'montant'       => 2000,
            'statut'        => 'payee',
            'payee_le'      => now(),
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('admin.commerciaux.commissions.annuler', $commission))
            ->assertRedirect();

        $this->assertDatabaseHas('commercial_commissions', [
            'id'      => $commission->id,
            'statut'  => 'en_attente',
            'payee_le'=> null,
        ]);
    }

    // =========================================================================
    // Code généré
    // =========================================================================

    public function test_code_genere_est_unique_et_alphanumerique(): void
    {
        $superAdmin = $this->creerSuperAdmin();

        $this->actingAs($superAdmin)->post(route('admin.commerciaux.store'), [
            'prenom'      => 'Alice',
            'nom_famille' => 'Martin',
            'email'       => 'alice@example.com',
            'password'    => 'Secret1234!',
        ]);

        $user   = User::where('email', 'alice@example.com')->first();
        $profil = CommercialProfile::where('user_id', $user->id)->first();

        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $profil->code);
        $this->assertLessThanOrEqual(10, strlen($profil->code));
    }

    public function test_deux_commerciaux_ont_des_codes_differents(): void
    {
        $superAdmin = $this->creerSuperAdmin();

        foreach (['alice1@example.com', 'alice2@example.com'] as $email) {
            $this->actingAs($superAdmin)->post(route('admin.commerciaux.store'), [
                'prenom'      => 'Alice',
                'nom_famille' => 'Martin',
                'email'       => $email,
                'password'    => 'Secret1234!',
            ]);
        }

        $codes = CommercialProfile::pluck('code')->toArray();
        $this->assertCount(count(array_unique($codes)), $codes, 'Des codes dupliqués existent.');
    }
}
