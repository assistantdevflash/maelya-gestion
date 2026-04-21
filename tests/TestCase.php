<?php

namespace Tests;

use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Crée un institut de test avec des données uniques.
     */
    protected function creerInstitut(array $attrs = []): Institut
    {
        return Institut::create(array_merge([
            'nom'       => 'Institut Test',
            'email'     => 'institut-' . uniqid() . '@test.com',
            'telephone' => '0102030405',
            'ville'     => 'Abidjan',
            'type'      => 'salon_coiffure',
            'actif'     => true,
        ], $attrs));
    }

    /**
     * Crée un utilisateur admin lié à un institut, avec un abonnement actif.
     */
    protected function creerAdmin(array $userAttrs = [], array $institutAttrs = []): User
    {
        $institut = $this->creerInstitut($institutAttrs);

        $user = User::factory()->create(array_merge([
            'prenom'      => 'Marie',
            'nom_famille' => 'Admin',
            'role'        => 'admin',
            'actif'       => true,
            'institut_id' => $institut->id,
        ], $userAttrs));

        $institut->forceFill(['proprietaire_id' => $user->id])->save();

        // Abonnement actif nécessaire pour passer le middleware AbonnementActif
        $plan = $this->creerPlan(['slug' => 'essai-admin-' . uniqid()]);
        Abonnement::create([
            'user_id'   => $user->id,
            'plan_id'   => $plan->id,
            'montant'   => 0,
            'periode'   => 'essai',
            'statut'    => 'actif',
            'debut_le'  => now(),
            'expire_le' => now()->addDays(30),
        ]);

        return $user;
    }

    /**
     * Crée un plan d'abonnement de test.
     */
    protected function creerPlan(array $attrs = []): PlanAbonnement
    {
        return PlanAbonnement::create(array_merge([
            'nom'        => 'Essai',
            'slug'       => 'essai-' . uniqid(),
            'duree_type' => 'essai',
            'duree_jours'=> 14,
            'prix'       => 0,
            'actif'      => true,
        ], $attrs));
    }
}
