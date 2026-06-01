<?php

namespace Tests\Feature\Dashboard;

use App\Models\CaisseBrouillon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaisseBrouillonTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_panier_peut_etre_mis_en_attente(): void
    {
        $admin = $this->creerAdmin();

        $payload = [
            'panier' => [
                ['type' => 'prestation', 'id' => 'p-1', 'nom' => 'Brushing', 'prix' => 2000, 'quantite' => 1],
                ['type' => 'produit',    'id' => 'q-1', 'nom' => 'Shampoing', 'prix' => 1500, 'quantite' => 2],
            ],
            'total_indicatif' => 5000,
        ];

        $this->actingAs($admin)
            ->postJson(route('dashboard.caisse.brouillons.store'), $payload)
            ->assertOk()
            ->assertJsonStructure(['ok', 'id']);

        $this->assertSame(1, CaisseBrouillon::count());
        $b = CaisseBrouillon::first();
        $this->assertSame($admin->institut_id, $b->institut_id);
        $this->assertCount(2, $b->panier);
        $this->assertSame(5000, (int) $b->total_indicatif);
    }

    public function test_brouillon_peut_etre_supprime(): void
    {
        $admin = $this->creerAdmin();
        $b = CaisseBrouillon::create([
            'institut_id'     => $admin->institut_id,
            'user_id'         => $admin->id,
            'panier'          => [['type' => 'produit', 'id' => 'x', 'nom' => 'Test', 'prix' => 100, 'quantite' => 1]],
            'total_indicatif' => 100,
        ]);

        $this->actingAs($admin)
            ->delete(route('dashboard.caisse.brouillons.destroy', $b->id))
            ->assertRedirect();

        $this->assertSame(0, CaisseBrouillon::count());
    }

    public function test_brouillon_d_un_autre_institut_est_refuse(): void
    {
        $admin1 = $this->creerAdmin();
        $admin2 = $this->creerAdmin();

        $b = CaisseBrouillon::create([
            'institut_id'     => $admin1->institut_id,
            'user_id'         => $admin1->id,
            'panier'          => [['type' => 'produit', 'id' => 'x', 'nom' => 'T', 'prix' => 1, 'quantite' => 1]],
            'total_indicatif' => 1,
        ]);

        $this->actingAs($admin2)
            ->delete(route('dashboard.caisse.brouillons.destroy', $b->id))
            ->assertNotFound();

        $this->assertSame(1, CaisseBrouillon::withoutGlobalScopes()->count());
    }
}
