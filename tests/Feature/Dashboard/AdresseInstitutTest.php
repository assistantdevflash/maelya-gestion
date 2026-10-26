<?php

namespace Tests\Feature\Dashboard;

use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adresse postale de l'établissement : saisie (création / modification)
 * et affichage dans la fiche admin.
 */
class AdresseInstitutTest extends TestCase
{
    use RefreshDatabase;

    private function type(): EtablissementType
    {
        return EtablissementType::create([
            'code'     => 'salon_coiffure',
            'libelle'  => 'Salon de coiffure',
            'actif'    => true,
            'position' => 1,
        ]);
    }

    private function adminAvecInstitut(array $attrs = []): array
    {
        $admin = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($admin->institut_id);
        $type = $this->type();

        if ($attrs) {
            $institut->update($attrs);
        }

        return [$admin, $institut->fresh(), $type];
    }

    /**
     * Administrateur avec un abonnement sur le plan « essai ».
     *
     * Nécessaire pour la route de création : elle est protégée par
     * `feature:multi_instituts`, et le plan doit exister dans
     * config/plans-features.php pour accorder la fonctionnalité.
     */
    private function adminAvecPlanEssai(): User
    {
        $institut = $this->creerInstitut();

        $user = User::factory()->create([
            'prenom'      => 'Brice',
            'nom_famille' => 'Deco',
            'role'        => 'admin',
            'actif'       => true,
            'institut_id' => $institut->id,
        ]);

        $institut->forceFill(['proprietaire_id' => $user->id])->save();

        $plan = $this->creerPlan(['slug' => 'essai']);

        \App\Models\Abonnement::create([
            'user_id'   => $user->id,
            'plan_id'   => $plan->id,
            'montant'   => 0,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->toDateString(),
            'expire_le' => now()->addDays(30)->toDateString(),
        ]);

        return $user->fresh();
    }

    public function test_un_etablissement_peut_etre_cree_avec_une_adresse(): void
    {
        $admin = $this->adminAvecPlanEssai();
        $type = $this->type();

        $this->actingAs($admin)
            ->post(route('dashboard.mes-instituts.store'), [
                'nom'       => 'Brice Deco',
                'ville'     => 'Bouaké',
                'adresse'   => 'Quartier Air France, face à la pharmacie',
                'telephone' => '0505050505',
                'email'     => 'brice@example.com',
                'type'      => $type->code,
            ])
            ->assertRedirect();

        $institut = Institut::where('nom', 'Brice Deco')->first();

        $this->assertNotNull($institut);
        $this->assertSame('Quartier Air France, face à la pharmacie', $institut->adresse);
    }

    public function test_l_adresse_peut_etre_modifiee(): void
    {
        [$admin, $institut, $type] = $this->adminAvecInstitut();

        $this->assertNull($institut->adresse);

        $this->actingAs($admin)
            ->put(route('dashboard.mes-instituts.update', $institut), [
                'nom'       => $institut->nom,
                'ville'     => $institut->ville,
                'adresse'   => '8ème, en face de la Pharmacie des Arums',
                'telephone' => $institut->telephone,
                'email'     => $institut->email,
                'type'      => $type->code,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(
            '8ème, en face de la Pharmacie des Arums',
            $institut->fresh()->adresse
        );
    }

    public function test_l_adresse_est_visible_dans_le_detail_admin(): void
    {
        [$admin, $institut, $type] = $this->adminAvecInstitut([
            'adresse' => 'Cocody Angré, 7ème tranche',
        ]);

        $superAdmin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        $this->actingAs($superAdmin)
            ->get(route('admin.instituts.show', $institut))
            ->assertOk()
            ->assertSee('Adresse')
            ->assertSee('Cocody Angré, 7ème tranche');
    }

    public function test_l_adresse_est_optionnelle(): void
    {
        [$admin, $institut, $type] = $this->adminAvecInstitut(['adresse' => 'Une adresse']);

        // Soumission avec le champ vidé (le formulaire envoie toujours la valeur)
        $this->actingAs($admin)
            ->put(route('dashboard.mes-instituts.update', $institut), [
                'nom'       => $institut->nom,
                'ville'     => $institut->ville,
                'adresse'   => '',
                'telephone' => $institut->telephone,
                'email'     => $institut->email,
                'type'      => $type->code,
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull($institut->fresh()->adresse);
    }

    public function test_une_adresse_trop_longue_est_refusee(): void
    {
        [$admin, $institut, $type] = $this->adminAvecInstitut();

        $this->actingAs($admin)
            ->put(route('dashboard.mes-instituts.update', $institut), [
                'nom'       => $institut->nom,
                'adresse'   => str_repeat('a', 300),
                'type'      => $type->code,
            ])
            ->assertSessionHasErrors('adresse');
    }
}
