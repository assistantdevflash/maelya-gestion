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
 * Tests de génération automatique des commissions lors de la validation d'abonnements.
 */
class CommissionGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('commercial_config')->insertOrIgnore(['taux' => 20, 'duree_mois' => 6]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function creerCommercialAvecParrainage(User $proprietaire, int $taux = 20): array
    {
        $commUser = User::factory()->create(['role' => 'commercial', 'actif' => true]);
        $profil   = CommercialProfile::create([
            'user_id' => $commUser->id,
            'code'    => 'CM' . rand(1000, 9999),
        ]);
        $parrainage = CommercialParrainage::create([
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $proprietaire->id,
            'expire_le'       => now()->addMonths(6)->toDateString(),
        ]);

        return compact('commUser', 'profil', 'parrainage');
    }

    private function creerAbonnementEnAttente(User $user, int $montant = 10000): Abonnement
    {
        $plan = $this->creerPlan(['slug' => 'mensuel-' . uniqid(), 'prix' => $montant, 'duree_jours' => 30]);

        return Abonnement::create([
            'user_id'              => $user->id,
            'plan_id'              => $plan->id,
            'montant'              => $montant,
            'periode'              => 'mensuel',
            'statut'               => 'en_attente',
            'reference_transfert'  => 'REF-' . uniqid(),
            'debut_le'             => null,
            'expire_le'            => null,
        ]);
    }

    private function creerSuperAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'actif' => true]);
    }

    // =========================================================================
    // Génération de commission
    // =========================================================================

    public function test_commission_generee_lors_validation_abonnement_paye(): void
    {
        $superAdmin  = $this->creerSuperAdmin();
        $proprietaire = $this->creerAdmin();
        ['profil' => $profil, 'parrainage' => $parrainage] = $this->creerCommercialAvecParrainage($proprietaire);

        $abonnement = $this->creerAbonnementEnAttente($proprietaire, 10000);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement))
            ->assertRedirect();

        $this->assertDatabaseHas('commercial_commissions', [
            'commercial_id' => $profil->id,
            'parrainage_id' => $parrainage->id,
            'abonnement_id' => $abonnement->id,
            'statut'        => 'en_attente',
        ]);
    }

    public function test_montant_commission_calcule_correctement(): void
    {
        DB::table('commercial_config')->update(['taux' => 20]);

        $superAdmin   = $this->creerSuperAdmin();
        $proprietaire = $this->creerAdmin();
        $this->creerCommercialAvecParrainage($proprietaire);

        $abonnement = $this->creerAbonnementEnAttente($proprietaire, 15000);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        $commission = CommercialCommission::where('abonnement_id', $abonnement->id)->first();
        $this->assertNotNull($commission);

        $this->assertEquals(15000, $commission->montant_base);
        $this->assertEquals(20, $commission->taux);
        $this->assertEquals(3000, $commission->montant); // 15000 * 20 / 100
    }

    public function test_commission_utilise_le_taux_config_du_moment(): void
    {
        DB::table('commercial_config')->update(['taux' => 15]);

        $superAdmin   = $this->creerSuperAdmin();
        $proprietaire = $this->creerAdmin();
        $this->creerCommercialAvecParrainage($proprietaire);

        $abonnement = $this->creerAbonnementEnAttente($proprietaire, 10000);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        $commission = CommercialCommission::where('abonnement_id', $abonnement->id)->first();
        $this->assertNotNull($commission);
        $this->assertEquals(15, $commission->taux);
        $this->assertEquals(1500, $commission->montant);
    }

    public function test_pas_de_commission_pour_abonnement_gratuit(): void
    {
        $superAdmin   = $this->creerSuperAdmin();
        $proprietaire = $this->creerAdmin();
        $this->creerCommercialAvecParrainage($proprietaire);

        $abonnement = $this->creerAbonnementEnAttente($proprietaire, 0);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        $this->assertDatabaseMissing('commercial_commissions', [
            'abonnement_id' => $abonnement->id,
        ]);
    }

    public function test_pas_de_commission_sans_parrainage_commercial(): void
    {
        $superAdmin   = $this->creerSuperAdmin();
        $proprietaire = $this->creerAdmin();
        // Aucun parrainage commercial créé

        $abonnement = $this->creerAbonnementEnAttente($proprietaire, 10000);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        $this->assertDatabaseMissing('commercial_commissions', [
            'abonnement_id' => $abonnement->id,
        ]);
    }

    public function test_pas_de_commission_si_parrainage_expire(): void
    {
        $superAdmin   = $this->creerSuperAdmin();
        $proprietaire = $this->creerAdmin();

        $commUser = User::factory()->create(['role' => 'commercial', 'actif' => true]);
        $profil   = CommercialProfile::create([
            'user_id' => $commUser->id,
            'code'    => 'EXPIREE',
        ]);
        // Parrainage expiré depuis hier
        CommercialParrainage::create([
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $proprietaire->id,
            'expire_le'       => now()->subDay()->toDateString(),
        ]);

        $abonnement = $this->creerAbonnementEnAttente($proprietaire, 10000);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        $this->assertDatabaseMissing('commercial_commissions', [
            'abonnement_id' => $abonnement->id,
        ]);
    }

    public function test_pas_de_double_commission_pour_meme_abonnement(): void
    {
        $superAdmin   = $this->creerSuperAdmin();
        $proprietaire = $this->creerAdmin();
        $this->creerCommercialAvecParrainage($proprietaire);

        $abonnement = $this->creerAbonnementEnAttente($proprietaire, 10000);

        // Valider une première fois
        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        // Forcer un deuxième appel (protection firstOrCreate)
        $abonnement->update(['statut' => 'en_attente', 'debut_le' => null, 'expire_le' => null]);

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        $this->assertCount(1, CommercialCommission::where('abonnement_id', $abonnement->id)->get());
    }

    // =========================================================================
    // Accesseur formaté
    // =========================================================================

    public function test_montant_formatte_retourne_chaine_fcfa(): void
    {
        $superAdmin   = $this->creerSuperAdmin();
        $proprietaire = $this->creerAdmin();
        $this->creerCommercialAvecParrainage($proprietaire);

        $abonnement = $this->creerAbonnementEnAttente($proprietaire, 15000);
        $this->actingAs($superAdmin)->patch(route('admin.abonnements.valider', $abonnement));

        $commission = CommercialCommission::where('abonnement_id', $abonnement->id)->first();
        $this->assertNotNull($commission);
        $this->assertStringContainsString('FCFA', $commission->montant_formatte);
    }

    // =========================================================================
    // Modèles — méthodes agrégat
    // =========================================================================

    public function test_total_gagne_ne_compte_que_les_commissions_payees(): void
    {
        $proprietaire = $this->creerAdmin();
        $commUser     = User::factory()->create(['role' => 'commercial', 'actif' => true]);
        $profil       = CommercialProfile::create(['user_id' => $commUser->id, 'code' => 'TGTEST']);
        $parrainage   = CommercialParrainage::create([
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $proprietaire->id,
            'expire_le'       => now()->addMonths(6)->toDateString(),
        ]);

        $plan = $this->creerPlan();
        $abo1 = Abonnement::create(['user_id' => $proprietaire->id, 'plan_id' => $plan->id, 'montant' => 5000, 'periode' => 'mensuel', 'statut' => 'actif', 'debut_le' => now()->toDateString(), 'expire_le' => now()->addDays(30)->toDateString()]);
        $abo2 = Abonnement::create(['user_id' => $proprietaire->id, 'plan_id' => $plan->id, 'montant' => 5000, 'periode' => 'mensuel', 'statut' => 'actif', 'debut_le' => now()->toDateString(), 'expire_le' => now()->addDays(60)->toDateString()]);

        CommercialCommission::create(['commercial_id' => $profil->id, 'parrainage_id' => $parrainage->id, 'abonnement_id' => $abo1->id, 'montant_base' => 5000, 'taux' => 20, 'montant' => 1000, 'statut' => 'payee']);
        CommercialCommission::create(['commercial_id' => $profil->id, 'parrainage_id' => $parrainage->id, 'abonnement_id' => $abo2->id, 'montant_base' => 5000, 'taux' => 20, 'montant' => 1000, 'statut' => 'en_attente']);

        $this->assertEquals(1000, $profil->totalGagne());
        $this->assertEquals(1000, $profil->totalEnAttente());
    }

    // =========================================================================
    // isActif sur CommercialParrainage
    // =========================================================================

    public function test_parrainage_actif_retourne_true(): void
    {
        $proprietaire = $this->creerAdmin();
        $commUser = User::factory()->create(['role' => 'commercial', 'actif' => true]);
        $profil   = CommercialProfile::create(['user_id' => $commUser->id, 'code' => 'ISACTIF']);
        $parrainage = CommercialParrainage::create([
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $proprietaire->id,
            'expire_le'       => now()->addDays(10)->toDateString(),
        ]);

        $this->assertTrue($parrainage->isActif());
    }

    public function test_parrainage_expire_retourne_false(): void
    {
        $proprietaire = $this->creerAdmin();
        $commUser = User::factory()->create(['role' => 'commercial', 'actif' => true]);
        $profil   = CommercialProfile::create(['user_id' => $commUser->id, 'code' => 'ISEXP']);
        $parrainage = CommercialParrainage::create([
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $proprietaire->id,
            'expire_le'       => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($parrainage->isActif());
    }
}
