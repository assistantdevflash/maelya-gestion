<?php

namespace Tests;

use App\Models\Abonnement;
use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use Database\Seeders\EtablissementTypeSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Peuple la table etablissement_types.
     *
     * La validation du formulaire d'inscription utilise
     * Rule::exists('etablissement_types', 'code'). En production cette table
     * est peuplée par EtablissementTypeSeeder ; sans ce seed, toute inscription
     * échoue avec « Le champ type institut sélectionné est invalide ».
     *
     * Opt-in (et non automatique) car certains tests créent eux-mêmes
     * leurs types d'établissement et entreraient en conflit d'unicité.
     */
    protected function seedTypesEtablissement(): void
    {
        if (Schema::hasTable('etablissement_types') && EtablissementType::query()->count() === 0) {
            $this->seed(EtablissementTypeSeeder::class);
        }
    }

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
        $plan = $this->creerPlan();
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
     *
     * ⚠️ Le slug doit correspondre à une clé de config/plans-features.php,
     * sinon User::aFonctionnalite() ne trouve aucune feature et les routes
     * protégées par le middleware `feature:` redirigent (302) au lieu de
     * répondre 200. 'essai' vaut ['*'] → toutes les fonctionnalités.
     */
    protected function creerPlan(array $attrs = []): PlanAbonnement
    {
        return PlanAbonnement::create(array_merge([
            'nom'        => 'Essai',
            'slug'       => 'essai',
            'duree_type' => 'essai',
            'duree_jours'=> 14,
            'prix'       => 0,
            'actif'      => true,
        ], $attrs));
    }
}
