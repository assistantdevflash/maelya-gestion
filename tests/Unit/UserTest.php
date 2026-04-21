<?php

namespace Tests\Unit;

use App\Models\Abonnement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_nom_complet_attribute(): void
    {
        $user = $this->creerAdmin(['prenom' => 'Sandrine', 'nom_famille' => 'Tra']);

        $this->assertSame('Sandrine Tra', $user->nom_complet);
    }

    public function test_code_parrainage_auto_genere_a_la_creation(): void
    {
        $user = $this->creerAdmin();

        $this->assertNotNull($user->code_parrainage);
        $this->assertEquals(8, strlen($user->code_parrainage));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $user->code_parrainage);
    }

    public function test_is_admin(): void
    {
        $user = $this->creerAdmin(['role' => 'admin']);

        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isEmploye());
    }

    public function test_is_employe(): void
    {
        $user = $this->creerAdmin(['role' => 'employe']);

        $this->assertTrue($user->isEmploye());
        $this->assertFalse($user->isAdmin());
    }

    public function test_abonnement_en_sursis_si_expire_depuis_moins_de_2_jours(): void
    {
        $user = $this->creerAdmin();
        $plan = $this->creerPlan();

        Abonnement::create([
            'user_id'   => $user->id,
            'plan_id'   => $plan->id,
            'montant'   => 0,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->subDays(15)->toDateString(),
            'expire_le' => now()->subDay()->toDateString(),
        ]);

        $this->assertNotNull($user->abonnementEnSursis());
    }

    public function test_pas_de_sursis_si_expire_depuis_plus_de_2_jours(): void
    {
        $user = $this->creerAdmin();
        $plan = $this->creerPlan();

        Abonnement::create([
            'user_id'   => $user->id,
            'plan_id'   => $plan->id,
            'montant'   => 0,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->subDays(20)->toDateString(),
            'expire_le' => now()->subDays(3)->toDateString(),
        ]);

        $this->assertNull($user->abonnementEnSursis());
    }

    public function test_abonnement_actif_retourne_abonnement_valide(): void
    {
        $user = $this->creerAdmin();
        $plan = $this->creerPlan();

        Abonnement::create([
            'user_id'   => $user->id,
            'plan_id'   => $plan->id,
            'montant'   => 0,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->toDateString(),
            'expire_le' => now()->addDays(14)->toDateString(),
        ]);

        $this->assertNotNull($user->abonnementActif()->first());
    }
}
