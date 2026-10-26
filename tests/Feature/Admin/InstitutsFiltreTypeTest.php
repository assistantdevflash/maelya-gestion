<?php

namespace Tests\Feature\Admin;

use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filtre par type d'établissement sur la liste admin.
 *
 * La liste déroulante ne doit proposer que les types réellement utilisés par
 * au moins un établissement — pas les 18 types de la table.
 */
class InstitutsFiltreTypeTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'actif' => true]);
    }

    private function creerInstitutDeType(string $nom, string $type): Institut
    {
        return Institut::create([
            'nom'    => $nom,
            'email'  => strtolower(str_replace(' ', '', $nom)) . '@test.ci',
            'type'   => $type,
            'ville'  => 'Abidjan',
            'actif'  => true,
        ]);
    }

    public function test_le_filtre_type_ne_garde_que_les_etablissements_du_type(): void
    {
        $this->creerInstitutDeType('Salon Coiffure', 'salon_coiffure');
        $this->creerInstitutDeType('Onglerie Sarah', 'onglerie');

        $this->actingAs($this->superAdmin())
            ->get(route('admin.instituts.index', ['type' => 'onglerie']))
            ->assertOk()
            ->assertSee('Onglerie Sarah')
            ->assertDontSee('Salon Coiffure');
    }

    public function test_le_filtre_ne_propose_que_les_types_utilises(): void
    {
        // Types présents dans la table mais inutilisés
        EtablissementType::create(['code' => 'barbier', 'libelle' => 'Barbier', 'actif' => true, 'position' => 1]);
        EtablissementType::create(['code' => 'salon_coiffure', 'libelle' => 'Salon de coiffure', 'actif' => true, 'position' => 2]);

        $this->creerInstitutDeType('Salon Coiffure', 'salon_coiffure');

        $reponse = $this->actingAs($this->superAdmin())
            ->get(route('admin.instituts.index'))
            ->assertOk()
            ->assertSee('Salon de coiffure')
            ->assertSee('name="type"', false);

        // « Barbier » n'est utilisé par aucun établissement → absent du select
        $reponse->assertDontSee('Barbier');
    }

    public function test_le_libelle_vient_de_la_table_des_types(): void
    {
        EtablissementType::create([
            'code'     => 'salon_coiffure',
            'libelle'  => 'Salon de coiffure haut de gamme',
            'actif'    => true,
            'position' => 1,
        ]);

        $this->creerInstitutDeType('Salon Coiffure', 'salon_coiffure');

        $this->actingAs($this->superAdmin())
            ->get(route('admin.instituts.index'))
            ->assertOk()
            ->assertSee('Salon de coiffure haut de gamme');
    }

    public function test_un_code_legacy_absent_de_la_table_reste_lisible(): void
    {
        // « onglerie » n'existe plus dans etablissement_types
        $this->creerInstitutDeType('Onglerie Sarah', 'onglerie');

        $this->actingAs($this->superAdmin())
            ->get(route('admin.instituts.index'))
            ->assertOk()
            ->assertSee('Onglerie')
            ->assertDontSee('onglerie</option>', false);
    }

    public function test_la_reinitialisation_efface_le_filtre(): void
    {
        $this->creerInstitutDeType('Salon Coiffure', 'salon_coiffure');
        $this->creerInstitutDeType('Onglerie Sarah', 'onglerie');

        // Le filtre actif fait apparaître le bouton Réinitialiser
        $this->actingAs($this->superAdmin())
            ->get(route('admin.instituts.index', ['type' => 'onglerie']))
            ->assertOk()
            ->assertSee('Réinitialiser');

        // Sans filtre, les deux établissements sont visibles
        $this->actingAs($this->superAdmin())
            ->get(route('admin.instituts.index'))
            ->assertOk()
            ->assertSee('Onglerie Sarah')
            ->assertSee('Salon Coiffure');
    }

    public function test_libelle_pour_retourne_tiret_si_code_vide(): void
    {
        $this->assertSame('—', EtablissementType::libellePour(null));
        $this->assertSame('—', EtablissementType::libellePour(''));
    }
}
