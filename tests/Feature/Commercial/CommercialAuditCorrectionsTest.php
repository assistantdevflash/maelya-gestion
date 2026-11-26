<?php

namespace Tests\Feature\Commercial;

use App\Models\Abonnement;
use App\Models\CommercialCommission;
use App\Models\CommercialParrainage;
use App\Models\CommercialProfile;
use App\Models\Institut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Corrections issues de l'audit du module Commerciaux.
 */
class CommercialAuditCorrectionsTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $commercialUser;

    private CommercialProfile $profil;

    private User $proprietaire;

    private Institut $institut;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('commercial_config')->insertOrIgnore(['taux' => 20, 'duree_mois' => 6]);

        $this->superAdmin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        $this->commercialUser = User::factory()->create([
            'role' => 'commercial', 'actif' => true,
            'prenom' => 'Hamade', 'nom_famille' => 'Attalah',
        ]);
        $this->profil = CommercialProfile::create([
            'user_id' => $this->commercialUser->id,
            'code'    => 'HAAT5170',
        ]);

        $this->proprietaire = $this->creerAdmin();
        $this->institut = Institut::find($this->proprietaire->institut_id);
    }

    private function parrainer(int $mois = 6): void
    {
        CommercialParrainage::create([
            'commercial_id'   => $this->profil->id,
            'proprietaire_id' => $this->proprietaire->id,
            'institut_id'     => $this->institut->id,
            'expire_le'       => now()->addMonths($mois)->toDateString(),
            'source'          => 'manuel',
        ]);
    }

    private function abonnementEnAttente(int $montant = 10000): Abonnement
    {
        $plan = $this->creerPlan(['slug' => 'p-' . uniqid(), 'prix' => $montant, 'duree_jours' => 30]);

        return Abonnement::create([
            'user_id'             => $this->proprietaire->id,
            'plan_id'             => $plan->id,
            'montant'             => $montant,
            'periode'             => 'mensuel',
            'statut'              => 'en_attente',
            'reference_transfert' => 'REF-' . uniqid(),
            'debut_le'            => null,
            'expire_le'           => null,
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // 1. Un commercial désactivé n'a plus accès à son espace
    // ═════════════════════════════════════════════════════════════════════════

    public function test_un_commercial_desactive_ne_peut_plus_acceder_a_son_espace(): void
    {
        $this->commercialUser->update(['actif' => false]);

        $this->actingAs($this->commercialUser)
            ->get(route('commercial.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_un_commercial_desactive_ne_peut_plus_se_connecter(): void
    {
        $this->commercialUser->update(['actif' => false]);

        $this->post('/login', [
            'login'    => $this->commercialUser->email,
            'password' => 'password',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_un_commercial_actif_accede_normalement_a_son_espace(): void
    {
        $this->actingAs($this->commercialUser)
            ->get(route('commercial.dashboard'))
            ->assertOk();
    }

    // ═════════════════════════════════════════════════════════════════════════
    // 2. Les commissions sont gelées quand le commercial est désactivé
    // ═════════════════════════════════════════════════════════════════════════

    public function test_aucune_commission_nest_generee_pour_un_commercial_desactive(): void
    {
        $this->parrainer();
        $this->commercialUser->update(['actif' => false]);

        $abonnement = $this->abonnementEnAttente();

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement))
            ->assertRedirect();

        $this->assertSame(0, CommercialCommission::count());
    }

    public function test_une_commission_est_toujours_generee_pour_un_commercial_actif(): void
    {
        $this->parrainer();

        $abonnement = $this->abonnementEnAttente();

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        $this->assertSame(1, CommercialCommission::count());
    }

    // ═════════════════════════════════════════════════════════════════════════
    // 3. La durée configurée pilote réellement le calcul
    // ═════════════════════════════════════════════════════════════════════════

    public function test_la_duree_configuree_plafonne_la_commission(): void
    {
        // 3 mois configurés, abonnement annuel de 120 000 F : la commission
        // doit porter sur 3 mois, pas sur 6.
        DB::table('commercial_config')->update(['taux' => 20, 'duree_mois' => 3]);

        $this->parrainer(3);

        $plan = $this->creerPlan(['slug' => 'annuel-' . uniqid(), 'prix' => 10000, 'duree_jours' => 365]);

        $abonnement = Abonnement::create([
            'user_id'             => $this->proprietaire->id,
            'plan_id'             => $plan->id,
            'montant'             => 120000,
            'periode'             => 'annuel',
            'statut'              => 'en_attente',
            'reference_transfert' => 'REF-' . uniqid(),
            'debut_le'            => null,
            'expire_le'           => null,
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement));

        $commission = CommercialCommission::where('abonnement_id', $abonnement->id)->first();

        $this->assertNotNull($commission);
        // 120 000 × 3/12 × 20 % = 6 000 (et non 12 000 avec l'ancien 6 en dur)
        $this->assertSame(6000, (int) $commission->montant);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // 4. Suppression protégée
    // ═════════════════════════════════════════════════════════════════════════

    public function test_suppression_refusee_si_des_commissions_existent(): void
    {
        $this->parrainer();

        CommercialCommission::create([
            'commercial_id' => $this->profil->id,
            'parrainage_id' => CommercialParrainage::first()->id,
            'abonnement_id' => $this->abonnementEnAttente()->id,
            'montant_base'  => 10000,
            'taux'          => 20,
            'montant'       => 2000,
            'statut'        => 'en_attente',
        ]);

        $this->actingAs($this->superAdmin)
            ->delete(route('admin.commerciaux.destroy', $this->commercialUser))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->commercialUser->id]);
    }

    public function test_suppression_possible_sans_commission(): void
    {
        $this->actingAs($this->superAdmin)
            ->delete(route('admin.commerciaux.destroy', $this->commercialUser))
            ->assertRedirect(route('admin.commerciaux.index'));

        $this->assertDatabaseMissing('users', ['id' => $this->commercialUser->id]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // 5. Traçabilité et devise
    // ═════════════════════════════════════════════════════════════════════════

    public function test_le_paiement_enregistre_qui_a_paye_et_la_note(): void
    {
        $this->parrainer();

        $commission = CommercialCommission::create([
            'commercial_id' => $this->profil->id,
            'parrainage_id' => CommercialParrainage::first()->id,
            'abonnement_id' => $this->abonnementEnAttente()->id,
            'montant_base'  => 10000,
            'taux'          => 20,
            'montant'       => 2000,
            'statut'        => 'en_attente',
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.commerciaux.commissions.payer', $commission), [
                'note' => 'Virement Wave 26/11',
            ]);

        $commission->refresh();

        $this->assertSame('payee', $commission->statut);
        $this->assertSame($this->superAdmin->id, $commission->paye_par);
        $this->assertSame('Virement Wave 26/11', $commission->notes_paiement);
        $this->assertNotNull($commission->payee_le);
    }

    public function test_annuler_un_paiement_conserve_le_motif(): void
    {
        $this->parrainer();

        $commission = CommercialCommission::create([
            'commercial_id' => $this->profil->id,
            'parrainage_id' => CommercialParrainage::first()->id,
            'abonnement_id' => $this->abonnementEnAttente()->id,
            'montant_base'  => 10000,
            'taux'          => 20,
            'montant'       => 2000,
            'statut'        => 'payee',
            'payee_le'      => now(),
            'paye_par'      => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.commerciaux.commissions.annuler', $commission), [
                'motif' => 'Erreur de virement',
            ]);

        $commission->refresh();

        $this->assertSame('en_attente', $commission->statut);
        $this->assertNull($commission->payee_le);
        $this->assertSame('Erreur de virement', $commission->notes_paiement);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // 6. Liste : statistiques honnêtes et recherche par code
    // ═════════════════════════════════════════════════════════════════════════

    public function test_les_stats_separent_verse_et_en_attente(): void
    {
        $this->parrainer();

        foreach ([['payee', 3000], ['en_attente', 7000]] as [$statut, $montant]) {
            CommercialCommission::create([
                'commercial_id' => $this->profil->id,
                'parrainage_id' => CommercialParrainage::first()->id,
                'abonnement_id' => $this->abonnementEnAttente()->id,
                'montant_base'  => 10000,
                'taux'          => 20,
                'montant'       => $montant,
                'statut'        => $statut,
            ]);
        }

        $html = $this->actingAs($this->superAdmin)
            ->get(route('admin.commerciaux.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Commissions versées', $html);
        $this->assertStringContainsString('3 000', $html);
        $this->assertStringContainsString('7 000', $html);
    }

    public function test_la_recherche_trouve_par_code_commercial(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.commerciaux.index', ['q' => 'HAAT5170']))
            ->assertOk()
            ->assertSee('Hamade Attalah');
    }

    public function test_le_filtre_statut_isole_les_comptes_desactives(): void
    {
        $autre = User::factory()->create(['role' => 'commercial', 'actif' => false, 'prenom' => 'Flore', 'nom_famille' => 'Dabah']);
        CommercialProfile::create(['user_id' => $autre->id, 'code' => 'FLDA6211']);

        $this->actingAs($this->superAdmin)
            ->get(route('admin.commerciaux.index', ['statut' => 'inactif']))
            ->assertOk()
            ->assertSee('Flore Dabah')
            ->assertDontSee('Hamade Attalah');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // 7. Attribution depuis la fiche du commercial
    // ═════════════════════════════════════════════════════════════════════════

    public function test_attribution_depuis_la_fiche_du_commercial(): void
    {
        $this->actingAs($this->superAdmin)
            ->post(route('admin.commerciaux.attribuer', $this->commercialUser), [
                'institut_id' => $this->institut->id,
            ])
            ->assertRedirect();

        $parrainage = CommercialParrainage::where('proprietaire_id', $this->proprietaire->id)->first();

        $this->assertNotNull($parrainage);
        $this->assertSame($this->profil->id, $parrainage->commercial_id);
        $this->assertSame('manuel', $parrainage->source);
    }

    // ═════════════════════════════════════════════════════════════════════════
    // 8. Plus de N+1 sur la liste
    // ═════════════════════════════════════════════════════════════════════════

    public function test_le_nombre_de_requetes_ne_depend_pas_du_nombre_de_commerciaux(): void
    {
        $compter = function (): int {
            $admin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);
            $n = 0;
            DB::listen(function () use (&$n) { $n++; });
            $this->actingAs($admin)->get(route('admin.commerciaux.index'))->assertOk();

            return $n;
        };

        $avecTrois = $compter();

        for ($i = 0; $i < 10; $i++) {
            $u = User::factory()->create(['role' => 'commercial', 'actif' => true]);
            CommercialProfile::create(['user_id' => $u->id, 'code' => 'X' . substr(md5(uniqid((string) $i, true)), 0, 7)]);
        }

        $avecTreize = $compter();

        // Avant correction : 4 requêtes par ligne (18 → 58 pour 10 lignes de plus).
        $this->assertLessThanOrEqual(
            $avecTrois + 1,
            $avecTreize,
            "La liste déclenche {$avecTreize} requêtes pour 13 commerciaux contre {$avecTrois} pour 3 (N+1 probable)."
        );
    }
}
