<?php

namespace Tests\Feature\Abonnement;

use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use App\Services\PlanLimitesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Rétrogradation de plan : quotas d'établissements, d'employés et
 * fonctionnalités exclusives.
 *
 * Scénario de référence : un compte Premium+ (3 établissements, 10 employés)
 * dont l'abonnement expire totalement, puis qui souscrit à Premium
 * (1 établissement, 3 employés).
 */
class LimitesPlanRetrogradationTest extends TestCase
{
    use RefreshDatabase;

    private PlanLimitesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PlanLimitesService::class);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Jeu de données
    // ─────────────────────────────────────────────────────────────────────

    private function planPremium(array $attrs = []): PlanAbonnement
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

    private function planPremiumPlus(array $attrs = []): PlanAbonnement
    {
        return PlanAbonnement::create(array_merge([
            'nom'           => 'Premium+',
            'slug'          => 'premium-plus',
            'duree_type'    => 'mensuel',
            'duree_jours'   => 30,
            'prix'          => 9900,
            'max_instituts' => 3,
            'max_employes'  => 10,
            'actif'         => true,
            'ordre'         => 2,
        ], $attrs));
    }

    private function planUltra(array $attrs = []): PlanAbonnement
    {
        return PlanAbonnement::create(array_merge([
            'nom'           => 'Ultra',
            'slug'          => 'ultra',
            'duree_type'    => 'mensuel',
            'duree_jours'   => 30,
            'prix'          => 24900,
            'max_instituts' => null,
            'max_employes'  => null,
            'actif'         => true,
            'ordre'         => 3,
        ], $attrs));
    }

    /**
     * Crée un compte propriétaire avec N établissements et M employés chacun.
     *
     * @return array{0: User, 1: array<int, Institut>}
     */
    private function creerCompte(int $nbInstituts = 1, int $nbEmployes = 0): array
    {
        $instituts = [];

        for ($i = 1; $i <= $nbInstituts; $i++) {
            $institut = Institut::create([
                'nom'       => "Institut {$i}",
                'email'     => "institut{$i}@test.ci",
                'telephone' => '070000000' . $i,
                'ville'     => 'Abidjan',
                'type'      => 'salon_coiffure',
                'actif'     => true,
            ]);

            // Horodatage explicite : l'ordre de mise en sommeil en dépend.
            DB::table('instituts')->where('id', $institut->id)
                ->update(['created_at' => now()->addSeconds($i)]);

            $instituts[] = $institut->fresh();
        }

        $owner = User::factory()->create([
            'prenom'      => 'Kone',
            'nom_famille' => 'Test',
            'role'        => 'admin',
            'actif'       => true,
            'institut_id' => $instituts[0]->id,
        ]);

        foreach ($instituts as $institut) {
            $institut->forceFill(['proprietaire_id' => $owner->id])->save();
        }

        foreach ($instituts as $institut) {
            for ($e = 1; $e <= $nbEmployes; $e++) {
                $employe = User::factory()->create([
                    'prenom'      => "Emp{$e}",
                    'nom_famille' => "Institut{$institut->id}",
                    'role'        => 'employe',
                    'actif'       => true,
                    'institut_id' => $institut->id,
                ]);

                DB::table('users')->where('id', $employe->id)
                    ->update(['created_at' => now()->addSeconds($e)]);
            }
        }

        return [$owner->fresh(), $instituts];
    }

    private function abonner(User $owner, PlanAbonnement $plan, string $statut = 'actif', ?string $expireLe = null): Abonnement
    {
        return Abonnement::create([
            'user_id'   => $owner->id,
            'plan_id'   => $plan->id,
            'montant'   => $plan->prix,
            'periode'   => 'mensuel',
            'statut'    => $statut,
            'debut_le'  => now()->subDays(40)->toDateString(),
            'expire_le' => $expireLe ?? now()->addDays(30)->toDateString(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 1. Analyse de l'impact
    // ─────────────────────────────────────────────────────────────────────

    public function test_analyse_detecte_les_etablissements_en_excedent(): void
    {
        [$owner, $instituts] = $this->creerCompte(3);
        $plan = $this->planPremium();

        $analyse = $this->service->analyser($plan, $owner);

        $this->assertSame(3, $analyse['instituts']['total']);
        $this->assertSame(1, $analyse['instituts']['autorises']);
        $this->assertSame(2, $analyse['instituts']['excedent']);
        $this->assertTrue($analyse['impact']);
        $this->assertTrue($analyse['necessite_confirmation']);

        // L'établissement principal ne doit jamais être candidat au sommeil
        $idsEnExces = array_column($analyse['instituts']['liste'], 'id');
        $this->assertNotContains($instituts[0]->id, $idsEnExces);
        $this->assertContains($instituts[2]->id, $idsEnExces);
    }

    public function test_analyse_ne_signale_aucun_impact_pour_une_montee_de_plan(): void
    {
        [$owner] = $this->creerCompte(1);
        $this->abonner($owner, $this->planPremium());

        $analyse = $this->service->analyser($this->planPremiumPlus(), $owner->fresh());

        $this->assertFalse($analyse['est_retrogradation']);
        $this->assertFalse($analyse['impact']);
        $this->assertFalse($analyse['necessite_confirmation']);
        $this->assertSame([], $analyse['features_perdues']);
    }

    public function test_analyse_liste_les_fonctionnalites_exclusives_perdues(): void
    {
        [$owner] = $this->creerCompte(1);
        $this->abonner($owner, $this->planPremiumPlus());

        $analyse = $this->service->analyser($this->planPremium(), $owner->fresh());

        $this->assertTrue($analyse['est_retrogradation']);

        // Fonctionnalités réservées aux plans supérieurs
        $this->assertContains('multi_instituts', $analyse['features_perdues']);
        $this->assertContains('devis_factures', $analyse['features_perdues']);
        $this->assertContains('credits', $analyse['features_perdues']);

        // Les fonctionnalités conservées ne doivent pas apparaître
        $this->assertNotContains('caisse', $analyse['features_perdues']);
        $this->assertNotContains('clients', $analyse['features_perdues']);
    }

    public function test_analyse_detecte_les_employes_en_excedent(): void
    {
        [$owner] = $this->creerCompte(1, 5);
        $plan = $this->planPremium(); // 3 employés max

        $analyse = $this->service->analyser($plan, $owner);

        $this->assertSame(5, $analyse['employes']['total']);
        $this->assertSame(3, $analyse['employes']['autorises']);
        $this->assertSame(2, $analyse['employes']['excedent']);
        $this->assertTrue($analyse['impact']);
    }

    public function test_analyse_ignore_les_employes_des_etablissements_en_sommeil(): void
    {
        [$owner, $instituts] = $this->creerCompte(2, 5);
        $instituts[1]->update(['en_sommeil' => true]);

        $analyse = $this->service->analyser($this->planPremium(), $owner);

        // Seul l'établissement éveillé compte
        $this->assertSame(5, $analyse['employes']['total']);
        $this->assertSame(2, $analyse['employes']['excedent']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. Application des limites (mise en sommeil)
    // ─────────────────────────────────────────────────────────────────────

    public function test_appliquer_met_en_sommeil_les_etablissements_en_excedent(): void
    {
        [$owner, $instituts] = $this->creerCompte(3);

        $resultat = $this->service->appliquer($this->planPremium(), $owner);

        $this->assertSame(2, $resultat['instituts_endormis']);

        // L'établissement principal reste actif
        $this->assertFalse($instituts[0]->fresh()->en_sommeil);
        $this->assertNull($instituts[0]->fresh()->sommeil_motif);

        // Les deux suivants passent en sommeil, avec un motif explicite
        $this->assertTrue($instituts[1]->fresh()->en_sommeil);
        $this->assertTrue($instituts[2]->fresh()->en_sommeil);
        $this->assertStringContainsString('Premium', $instituts[1]->fresh()->sommeil_motif);

        // Aucune donnée supprimée
        $this->assertSame(3, Institut::where('proprietaire_id', $owner->id)->count());
    }

    public function test_appliquer_met_en_sommeil_les_employes_en_excedent(): void
    {
        [$owner, $instituts] = $this->creerCompte(1, 5);

        $resultat = $this->service->appliquer($this->planPremium(), $owner);

        $this->assertSame(2, $resultat['employes_endormis']);
        $this->assertSame(3, User::where('institut_id', $instituts[0]->id)
            ->where('role', 'employe')->where('en_sommeil', false)->count());
        $this->assertSame(5, User::where('institut_id', $instituts[0]->id)
            ->where('role', 'employe')->count());
    }

    public function test_appliquer_est_idempotent(): void
    {
        [$owner] = $this->creerCompte(3, 5);
        $plan = $this->planPremium();

        $premier = $this->service->appliquer($plan, $owner);
        $second  = $this->service->appliquer($plan, $owner->fresh());

        $this->assertSame(2, $premier['instituts_endormis']);
        $this->assertSame(0, $second['instituts_endormis']);
        $this->assertSame(0, $second['instituts_reveilles']);
        $this->assertSame(0, $second['employes_endormis']);
    }

    public function test_un_plan_illimite_ne_met_jamais_rien_en_sommeil(): void
    {
        [$owner] = $this->creerCompte(4, 12);

        $resultat = $this->service->appliquer($this->planUltra(), $owner);

        $this->assertSame(0, $resultat['instituts_endormis']);
        $this->assertSame(0, $resultat['employes_endormis']);
        $this->assertSame(0, Institut::where('en_sommeil', true)->count());
        $this->assertSame(0, User::where('en_sommeil', true)->count());
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Réveil automatique en cas de montée de plan
    // ─────────────────────────────────────────────────────────────────────

    public function test_une_montee_de_plan_reveille_les_etablissements_et_employes_en_sommeil(): void
    {
        [$owner] = $this->creerCompte(3, 5);

        // Rétrogradation
        $this->service->appliquer($this->planPremium(), $owner);
        $this->assertSame(2, Institut::where('en_sommeil', true)->count());

        // Montée de plan vers Ultra (illimité)
        $resultat = $this->service->appliquer($this->planUltra(), $owner->fresh());

        $this->assertSame(2, $resultat['instituts_reveilles']);
        $this->assertSame(0, Institut::where('en_sommeil', true)->count());
        $this->assertSame(0, User::where('en_sommeil', true)->count());
    }

    public function test_une_montee_de_plan_partielle_ne_reveille_que_le_quota_disponible(): void
    {
        [$owner] = $this->creerCompte(5);

        $this->service->appliquer($this->planPremium(), $owner); // quota 1 → 4 endormis
        $this->assertSame(4, Institut::where('en_sommeil', true)->count());

        // Premium+ : quota 3 → 2 réveillés, 2 restent en sommeil
        $resultat = $this->service->appliquer($this->planPremiumPlus(), $owner->fresh());

        $this->assertSame(2, $resultat['instituts_reveilles']);
        $this->assertSame(2, Institut::where('en_sommeil', true)->count());
        $this->assertSame(3, Institut::where('en_sommeil', false)->count());
    }

    // ─────────────────────────────────────────────────────────────────────
    // 4. Garde-fou à la souscription
    // ─────────────────────────────────────────────────────────────────────

    public function test_souscription_en_retrogradation_sans_confirmation_est_refusee(): void
    {
        [$owner] = $this->creerCompte(3);
        $premiumPlus = $this->planPremiumPlus();
        $premium = $this->planPremium();

        // Abonnement Premium+ expiré complètement
        $this->abonner($owner, $premiumPlus, 'expire', now()->subDays(10)->toDateString());

        $response = $this->actingAs($owner->fresh())->post(route('abonnement.souscrire', $premium), [
            'periode'             => 'mensuel',
            'methode_paiement'    => 'bank_transfer',
            'reference_transfert' => 'REF-12345',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('abonnements', [
            'user_id' => $owner->id,
            'plan_id' => $premium->id,
        ]);
    }

    public function test_souscription_en_retrogradation_avec_confirmation_est_acceptee(): void
    {
        [$owner] = $this->creerCompte(3);
        $premiumPlus = $this->planPremiumPlus();
        $premium = $this->planPremium();

        $this->abonner($owner, $premiumPlus, 'expire', now()->subDays(10)->toDateString());

        $response = $this->actingAs($owner->fresh())->post(route('abonnement.souscrire', $premium), [
            'periode'                => 'mensuel',
            'methode_paiement'       => 'bank_transfer',
            'reference_transfert'    => 'REF-12345',
            'confirmation_downgrade' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('abonnements', [
            'user_id' => $owner->id,
            'plan_id' => $premium->id,
            'statut'  => 'en_attente',
        ]);
    }

    public function test_une_montee_de_plan_n_exige_jamais_de_confirmation(): void
    {
        [$owner] = $this->creerCompte(3);
        $premium = $this->planPremium();
        $premiumPlus = $this->planPremiumPlus();

        $this->abonner($owner, $premium, 'actif');

        $response = $this->actingAs($owner->fresh())->post(route('abonnement.souscrire', $premiumPlus), [
            'periode'             => 'mensuel',
            'methode_paiement'    => 'bank_transfer',
            'reference_transfert' => 'REF-67890',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('abonnements', [
            'user_id' => $owner->id,
            'plan_id' => $premiumPlus->id,
            'statut'  => 'en_attente',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 5. Validation admin → application effective des quotas
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_validation_admin_applique_les_quotas_du_nouveau_plan(): void
    {
        $superAdmin = User::factory()->create([
            'role'  => 'super_admin',
            'actif' => true,
        ]);

        [$owner] = $this->creerCompte(3, 5);
        $premiumPlus = $this->planPremiumPlus();
        $premium = $this->planPremium();

        // Ancien abonnement Premium+ expiré + demande Premium en attente
        $this->abonner($owner, $premiumPlus, 'expire', now()->subDays(10)->toDateString());
        $demande = $this->abonner($owner, $premium, 'en_attente');

        $this->actingAs($superAdmin)
            ->patch(route('admin.abonnements.valider', $demande))
            ->assertSessionHasNoErrors();

        $this->assertSame('actif', $demande->fresh()->statut);

        // 3 établissements → quota 1 → 2 en sommeil
        $this->assertSame(2, Institut::where('en_sommeil', true)->count());
        // 5 employés par établissement éveillé → quota 3 → 2 en sommeil
        $this->assertSame(2, User::where('en_sommeil', true)->count());
    }

    // ─────────────────────────────────────────────────────────────────────
    // 6. Réactivation manuelle
    // ─────────────────────────────────────────────────────────────────────

    public function test_reactivation_autorisee_si_le_quota_le_permet(): void
    {
        [$owner, $instituts] = $this->creerCompte(3);
        $this->abonner($owner, $this->planPremiumPlus());

        $this->service->appliquer($this->planPremium(), $owner);
        $endormi = Institut::where('en_sommeil', true)->first();

        $response = $this->actingAs($owner->fresh())
            ->patch(route('dashboard.mes-instituts.reactiver', $endormi));

        $response->assertSessionHas('success');
        $this->assertFalse($endormi->fresh()->en_sommeil);
    }

    public function test_reactivation_refusee_si_le_quota_est_atteint(): void
    {
        [$owner] = $this->creerCompte(3);
        $this->abonner($owner, $this->planPremium()); // quota 1

        $this->service->appliquer($this->planPremium(), $owner);
        $endormi = Institut::where('en_sommeil', true)->first();

        $response = $this->actingAs($owner->fresh())
            ->patch(route('dashboard.mes-instituts.reactiver', $endormi));

        $response->assertSessionHas('error');
        $this->assertTrue($endormi->fresh()->en_sommeil);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 7. Un établissement en sommeil n'est pas utilisable
    // ─────────────────────────────────────────────────────────────────────

    public function test_switch_vers_un_etablissement_en_sommeil_est_refuse(): void
    {
        [$owner, $instituts] = $this->creerCompte(3);
        $this->abonner($owner, $this->planPremiumPlus());

        $this->service->appliquer($this->planPremium(), $owner);
        $endormi = Institut::where('en_sommeil', true)->first();

        $response = $this->actingAs($owner->fresh())
            ->post(route('dashboard.mes-instituts.switch', $endormi));

        $response->assertSessionHas('error');
        $this->assertNotSame($endormi->id, session('current_institut_id'));
    }
}
