<?php

namespace Tests\Feature\Dashboard;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TresorerieTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_tresorerie_accessible_avec_feature_finances(): void
    {
        $admin = $this->creerAdmin();
        $premium = \App\Models\PlanAbonnement::create([
            'nom' => 'P', 'slug' => 'premium', 'duree_type' => 'mensuel',
            'duree_jours' => 30, 'prix' => 1, 'actif' => true,
        ]);
        $admin->abonnements()->update(['plan_id' => $premium->id]);

        $response = $this->actingAs($admin)->get(route('dashboard.tresorerie.index'));
        $response->assertOk();
        $response->assertSee('Trésorerie prévisionnelle');
    }
}
