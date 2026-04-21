<?php

namespace Tests\Unit;

use App\Models\Depense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepenseTest extends TestCase
{
    use RefreshDatabase;

    private function makeDepense(array $attrs = []): Depense
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        return Depense::create(array_merge([
            'user_id'     => $user->id,
            'description' => 'Achat fournitures',
            'categorie'   => 'fournitures',
            'montant'     => 8500,
            'date'        => now()->toDateString(),
        ], $attrs));
    }

    public function test_montant_formate_attribute(): void
    {
        $depense = $this->makeDepense(['montant' => 8500]);

        $this->assertSame('8 500 FCFA', $depense->montant_formatte);
    }

    public function test_montant_cast_en_integer(): void
    {
        $depense = $this->makeDepense(['montant' => 3000]);

        $this->assertIsInt($depense->montant);
        $this->assertEquals(3000, $depense->montant);
    }

    public function test_date_cast_en_carbon(): void
    {
        $depense = $this->makeDepense(['date' => '2026-04-21']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $depense->date);
        $this->assertSame('2026-04-21', $depense->date->toDateString());
    }

    public function test_relation_user(): void
    {
        $user    = $this->creerAdmin();
        $this->actingAs($user);
        $depense = Depense::create([
            'user_id'     => $user->id,
            'description' => 'Test',
            'categorie'   => 'autres',
            'montant'     => 1000,
            'date'        => now()->toDateString(),
        ]);

        $this->assertSame($user->id, $depense->user->id);
    }
}
