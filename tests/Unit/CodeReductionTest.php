<?php

namespace Tests\Unit;

use App\Models\CodeReduction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CodeReductionTest extends TestCase
{
    use RefreshDatabase;

    private function makeCode(array $attrs = []): CodeReduction
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        return CodeReduction::create(array_merge([
            'code'        => 'TEST10',
            'type'        => 'pourcentage',
            'valeur'      => 10,
            'actif'       => true,
            'date_debut'  => now()->subDay(),
            'date_fin'    => now()->addDays(30),
            'limite_utilisation' => null,
            'nb_utilisations'    => 0,
        ], $attrs));
    }

    public function test_statut_actif_quand_valide(): void
    {
        $code = $this->makeCode();

        $this->assertSame('actif', $code->statut());
    }

    public function test_statut_expire_quand_date_fin_passee(): void
    {
        $code = $this->makeCode(['date_fin' => now()->subDay()]);

        $this->assertSame('expire', $code->statut());
    }

    public function test_est_expire(): void
    {
        $code = $this->makeCode(['date_fin' => now()->subDay()]);

        $this->assertTrue($code->estExpire());
    }

    public function test_pas_encore_valide(): void
    {
        $code = $this->makeCode(['date_debut' => now()->addDay()]);

        $this->assertTrue($code->estPasEncoreValide());
        $this->assertSame('inactif', $code->statut());
    }

    public function test_epuise_quand_utilisations_atteintes(): void
    {
        $code = $this->makeCode([
            'limite_utilisation' => 5,
            'nb_utilisations'    => 5,
        ]);

        $this->assertTrue($code->estEpuise());
        $this->assertSame('epuise', $code->statut());
    }

    public function test_pas_epuise_quand_limite_non_atteinte(): void
    {
        $code = $this->makeCode([
            'limite_utilisation' => 5,
            'nb_utilisations'    => 3,
        ]);

        $this->assertFalse($code->estEpuise());
    }

    public function test_statut_inactif_quand_actif_false(): void
    {
        $code = $this->makeCode(['actif' => false]);

        $this->assertSame('inactif', $code->statut());
    }

    public function test_sans_limite_utilisation_jamais_epuise(): void
    {
        $code = $this->makeCode(['limite_utilisation' => null, 'nb_utilisations' => 999]);

        $this->assertFalse($code->estEpuise());
    }
}
