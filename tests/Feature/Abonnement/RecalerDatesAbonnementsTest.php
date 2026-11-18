<?php

namespace Tests\Feature\Abonnement;

use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Commande de réparation des renouvellements enchaînés.
 *
 * Elle ne rattrape que les cas où la nouvelle période a démarré avant la fin
 * de la précédente, et laisse tranquilles les mises à niveau.
 */
class RecalerDatesAbonnementsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private PlanAbonnement $premium;
    private PlanAbonnement $premiumPlus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->creerAdmin();
        Abonnement::withoutGlobalScopes()->where('user_id', $this->owner->id)->delete();

        $this->premium = $this->creerPlan([
            'slug' => 'premium', 'nom' => 'Premium', 'duree_type' => 'mensuel', 'ordre' => 1, 'prix' => 4900,
        ]);
        $this->premiumPlus = $this->creerPlan([
            'slug' => 'premium-plus', 'nom' => 'Premium+', 'duree_type' => 'mensuel', 'ordre' => 2, 'prix' => 9900,
        ]);
    }

    private function abonnement(PlanAbonnement $plan, string $debut, string $fin, string $statut = 'actif'): Abonnement
    {
        return Abonnement::create([
            'user_id'   => $this->owner->id,
            'plan_id'   => $plan->id,
            'montant'   => $plan->prix,
            'periode'   => 'mensuel',
            'statut'    => $statut,
            'debut_le'  => $debut,
            'expire_le' => $fin,
        ]);
    }

    /** Cas réel : échéance au 21/11, renouvellement payé le 18/11. */
    private function scenarioRenouvellementPerdu(): array
    {
        $ancien = $this->abonnement(
            $this->premiumPlus,
            now()->subDays(27)->toDateString(),
            now()->addDays(3)->toDateString(),
            'expire'
        );

        $nouveau = $this->abonnement(
            $this->premiumPlus,
            now()->toDateString(),
            now()->addDays(30)->toDateString()
        );

        return [$ancien, $nouveau];
    }

    public function test_la_simulation_ne_modifie_rien(): void
    {
        [, $nouveau] = $this->scenarioRenouvellementPerdu();
        $avant = $nouveau->debut_le->toDateString() . '|' . $nouveau->expire_le->toDateString();

        $this->artisan('abonnements:recaler-dates')->assertExitCode(0);

        $apres = $nouveau->fresh();

        $this->assertSame($avant, $apres->debut_le->toDateString() . '|' . $apres->expire_le->toDateString());
    }

    public function test_la_commande_recale_la_periode_et_reactiven_l_ancienne(): void
    {
        [$ancien, $nouveau] = $this->scenarioRenouvellementPerdu();

        $this->artisan('abonnements:recaler-dates', ['--apply' => true])->assertExitCode(0);

        $nouveau->refresh();

        $this->assertSame(now()->addDays(4)->toDateString(), $nouveau->debut_le->toDateString());
        $this->assertSame(now()->addDays(34)->toDateString(), $nouveau->expire_le->toDateString());
        $this->assertSame(3, $nouveau->metadata['jours_reportes']);

        $this->assertSame('actif', $ancien->fresh()->statut, 'Les trois jours restants doivent rester accessibles.');
    }

    public function test_une_mise_a_niveau_n_est_pas_touchee(): void
    {
        $ancien = $this->abonnement(
            $this->premium,
            now()->subDays(20)->toDateString(),
            now()->addDays(10)->toDateString(),
            'expire'
        );

        $upgrade = $this->abonnement(
            $this->premiumPlus,
            now()->toDateString(),
            now()->addDays(30)->toDateString()
        );

        $this->artisan('abonnements:recaler-dates', ['--apply' => true])->assertExitCode(0);

        $this->assertSame(now()->toDateString(), $upgrade->fresh()->debut_le->toDateString());
        $this->assertSame('expire', $ancien->fresh()->statut, 'Le départ immédiat d\'une montée en gamme est voulu.');
    }

    public function test_un_renouvellement_apres_echeance_est_ignore(): void
    {
        $this->abonnement(
            $this->premiumPlus,
            now()->subDays(60)->toDateString(),
            now()->subDays(30)->toDateString(),
            'expire'
        );

        $nouveau = $this->abonnement(
            $this->premiumPlus,
            now()->toDateString(),
            now()->addDays(30)->toDateString()
        );

        $this->artisan('abonnements:recaler-dates', ['--apply' => true])->assertExitCode(0);

        $this->assertSame(now()->toDateString(), $nouveau->fresh()->debut_le->toDateString());
        $this->assertNull($nouveau->fresh()->metadata['jours_reportes'] ?? null);
    }

    public function test_une_chaine_de_renouvellements_est_recalee_de_bout_en_bout(): void
    {
        $this->abonnement($this->premiumPlus, now()->subDays(40)->toDateString(), now()->addDays(2)->toDateString(), 'expire');
        $deuxieme = $this->abonnement($this->premiumPlus, now()->toDateString(), now()->addDays(30)->toDateString());
        $troisieme = $this->abonnement($this->premiumPlus, now()->toDateString(), now()->addDays(30)->toDateString());

        $this->artisan('abonnements:recaler-dates', ['--apply' => true])->assertExitCode(0);

        $this->assertSame(now()->addDays(3)->toDateString(), $deuxieme->fresh()->debut_le->toDateString());
        $this->assertSame(now()->addDays(33)->toDateString(), $deuxieme->fresh()->expire_le->toDateString());

        // Le troisième s'enchaîne sur le deuxième corrigé, pas sur les dates d'origine.
        $this->assertSame(now()->addDays(34)->toDateString(), $troisieme->fresh()->debut_le->toDateString());
    }

    public function test_l_option_boutique_suit_la_nouvelle_echeance(): void
    {
        [, $nouveau] = $this->scenarioRenouvellementPerdu();

        $institut = Institut::findOrFail($this->owner->institut_id);
        $institut->update([
            'boutique_option_active'    => true,
            'boutique_option_expire_le' => $nouveau->expire_le->toDateString(),
        ]);

        $this->artisan('abonnements:recaler-dates', ['--apply' => true])->assertExitCode(0);

        $this->assertSame(
            $nouveau->fresh()->expire_le->toDateString(),
            $institut->fresh()->boutique_option_expire_le->toDateString()
        );
    }

    public function test_l_option_email_limite_le_traitement(): void
    {
        $autre = $this->creerAdmin();
        Abonnement::withoutGlobalScopes()->where('user_id', $autre->id)->delete();

        Abonnement::create([
            'user_id' => $autre->id, 'plan_id' => $this->premiumPlus->id, 'montant' => 9900,
            'periode' => 'mensuel', 'statut' => 'expire',
            'debut_le' => now()->subDays(27)->toDateString(), 'expire_le' => now()->addDays(3)->toDateString(),
        ]);
        $sien = Abonnement::create([
            'user_id' => $autre->id, 'plan_id' => $this->premiumPlus->id, 'montant' => 9900,
            'periode' => 'mensuel', 'statut' => 'actif',
            'debut_le' => now()->toDateString(), 'expire_le' => now()->addDays(30)->toDateString(),
        ]);

        $this->artisan('abonnements:recaler-dates', ['--email' => $autre->email, '--apply' => true])
            ->assertExitCode(0);

        $this->assertSame(now()->addDays(4)->toDateString(), $sien->fresh()->debut_le->toDateString());
    }

    public function test_un_compte_inconnu_renvoie_une_erreur(): void
    {
        $this->artisan('abonnements:recaler-dates', ['--email' => 'inconnu@example.test'])
            ->assertExitCode(1);
    }
}
