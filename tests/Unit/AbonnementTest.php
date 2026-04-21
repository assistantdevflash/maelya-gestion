<?php

namespace Tests\Unit;

use App\Models\Abonnement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbonnementTest extends TestCase
{
    use RefreshDatabase;

    private function makeAbonnement(array $attrs = []): Abonnement
    {
        $user = $this->creerAdmin();
        $plan = $this->creerPlan();

        return Abonnement::create(array_merge([
            'user_id'   => $user->id,
            'plan_id'   => $plan->id,
            'montant'   => 0,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->toDateString(),
            'expire_le' => now()->addDays(14)->toDateString(),
        ], $attrs));
    }

    public function test_is_actif_quand_statut_actif_et_pas_expire(): void
    {
        $abo = $this->makeAbonnement([
            'expire_le' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertTrue($abo->isActif());
    }

    public function test_is_actif_false_quand_expire(): void
    {
        $abo = $this->makeAbonnement([
            'expire_le' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($abo->isActif());
    }

    public function test_is_actif_false_quand_statut_non_actif(): void
    {
        $abo = $this->makeAbonnement([
            'statut'    => 'annule',
            'expire_le' => now()->addDays(5)->toDateString(),
        ]);

        $this->assertFalse($abo->isActif());
    }

    public function test_jours_restants_retourne_valeur_correcte(): void
    {
        $abo = $this->makeAbonnement([
            'expire_le' => now()->addDays(7)->toDateString(),
        ]);

        $this->assertEqualsWithDelta(7, $abo->joursRestants(), 1);
    }

    public function test_jours_restants_retourne_zero_si_expire(): void
    {
        $abo = $this->makeAbonnement([
            'expire_le' => now()->subDay()->toDateString(),
        ]);

        $this->assertEquals(0, $abo->joursRestants());
    }

    public function test_en_periode_sursis_si_expire_depuis_moins_2_jours(): void
    {
        $abo = $this->makeAbonnement([
            'expire_le' => now()->subDay()->toDateString(),
        ]);

        $this->assertTrue($abo->enPeriodeSursis());
    }

    public function test_pas_sursis_si_expire_depuis_plus_2_jours(): void
    {
        $abo = $this->makeAbonnement([
            'expire_le' => now()->subDays(3)->toDateString(),
        ]);

        $this->assertFalse($abo->enPeriodeSursis());
    }
}
