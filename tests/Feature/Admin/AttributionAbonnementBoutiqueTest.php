<?php

namespace Tests\Feature\Admin;

use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Attribution manuelle d'un abonnement par le super-admin, avec l'option
 * boutique en ligne cochable.
 */
class AttributionAbonnementBoutiqueTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(): array
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        $owner = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($owner->institut_id);

        // Repartir d'un établissement sans option boutique
        $institut->update([
            'boutique_option_active'    => false,
            'boutique_option_expire_le' => null,
        ]);

        $plan = PlanAbonnement::create([
            'nom'           => 'Premium+',
            'slug'          => 'premium-plus',
            'duree_type'    => 'mensuel',
            'duree_jours'   => 30,
            'prix'          => 9900,
            'max_instituts' => 3,
            'max_employes'  => 10,
            'actif'         => true,
            'ordre'         => 2,
        ]);

        return [$superAdmin, $owner, $institut->fresh(), $plan];
    }

    public function test_attribuer_un_abonnement_cree_un_acces_actif(): void
    {
        [$superAdmin, $owner, $institut, $plan] = $this->contexte();

        $this->actingAs($superAdmin)
            ->post(route('admin.instituts.offrir', $institut), [
                'plan_id' => $plan->id,
                'jours'   => 45,
            ])
            ->assertSessionHas('success');

        $abo = Abonnement::where('user_id', $owner->id)
            ->where('plan_id', $plan->id)
            ->where('statut', 'actif')
            ->first();

        $this->assertNotNull($abo);
        $this->assertSame(now()->addDays(45)->toDateString(), $abo->expire_le->toDateString());
    }

    public function test_la_case_boutique_cochee_active_l_option(): void
    {
        [$superAdmin, $owner, $institut, $plan] = $this->contexte();

        $this->assertFalse($institut->hasBoutiqueOption());

        $this->actingAs($superAdmin)
            ->post(route('admin.instituts.offrir', $institut), [
                'plan_id'         => $plan->id,
                'jours'           => 30,
                'option_boutique' => '1',
            ])
            ->assertSessionHas('success');

        $institut->refresh();
        $this->assertTrue($institut->boutique_option_active);
        $this->assertTrue($institut->hasBoutiqueOption());
        $this->assertSame(
            now()->addDays(30)->toDateString(),
            $institut->boutique_option_expire_le->toDateString()
        );
    }

    public function test_la_case_boutique_decochee_laisse_l_option_desactivee(): void
    {
        [$superAdmin, $owner, $institut, $plan] = $this->contexte();

        // Option active au départ
        $institut->setBoutiqueOption(true, now()->addMonths(2)->toDateString());
        $institut->save();
        $this->assertTrue($institut->fresh()->hasBoutiqueOption());

        $this->actingAs($superAdmin)
            ->post(route('admin.instituts.offrir', $institut), [
                'plan_id' => $plan->id,
                'jours'   => 30,
            ])
            ->assertSessionHas('success');

        $institut->refresh();
        $this->assertFalse($institut->boutique_option_active);
        $this->assertFalse($institut->hasBoutiqueOption());
    }

    public function test_l_option_boutique_expire_a_la_meme_date_que_l_abonnement(): void
    {
        [$superAdmin, $owner, $institut, $plan] = $this->contexte();

        $this->actingAs($superAdmin)->post(route('admin.instituts.offrir', $institut), [
            'plan_id'         => $plan->id,
            'jours'           => 90,
            'option_boutique' => '1',
        ])->assertSessionHas('success');

        $abo = Abonnement::where('user_id', $owner->id)
            ->where('statut', 'actif')
            ->latest()
            ->first();

        $this->assertSame(
            $abo->expire_le->toDateString(),
            $institut->fresh()->boutique_option_expire_le->toDateString()
        );
    }

    public function test_la_fiche_admin_propose_la_case_boutique(): void
    {
        [$superAdmin, $owner, $institut, $plan] = $this->contexte();

        $this->actingAs($superAdmin)
            ->get(route('admin.instituts.show', $institut))
            ->assertOk()
            ->assertSee('Attribuer un abonnement')
            ->assertSee('Option boutique en ligne')
            ->assertSee('name="option_boutique"', false);
    }

    public function test_la_fiche_admin_reste_lisible_et_affiche_les_statistiques(): void
    {
        [$superAdmin, $owner, $institut, $plan] = $this->contexte();

        $this->actingAs($superAdmin)
            ->get(route('admin.instituts.show', $institut))
            ->assertOk()
            ->assertSee('Statistiques')
            ->assertSee('Ventes validées')
            ->assertSee('Boutique en ligne');
    }
}
