<?php

namespace Tests\Feature\Dashboard;

use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Non-régression : les routes statiques `mes-instituts/apparence*` doivent être
 * déclarées AVANT `mes-instituts/{institut}`, sinon Laravel les capte et la
 * liaison de modèle part en 404 (« apparence » interprété comme un UUID).
 */
class MesInstitutsApparenceRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function adminAvecInstitut(): array
    {
        $admin = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($admin->institut_id);

        return [$admin, $institut];
    }

    public function test_la_route_apparence_n_est_pas_masquee_par_la_route_dynamique(): void
    {
        // Aucune route « apparence » ne doit exiger un paramètre {institut}
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($r) => $r->getName() === 'dashboard.mes-instituts.apparence.update');

        $this->assertNotNull($route);
        $this->assertStringNotContainsString('{institut}', $route->uri());
        $this->assertSame('dashboard/mes-instituts/apparence', $route->uri());
    }

    public function test_update_apparence_met_a_jour_les_couleurs(): void
    {
        [$admin, $institut] = $this->adminAvecInstitut();

        $this->actingAs($admin)
            ->put(route('dashboard.mes-instituts.apparence.update'), [
                'couleur_primaire'   => '#112233',
                'couleur_secondaire' => '#445566',
                'couleur_accent'     => '#778899',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $institut->refresh();
        $this->assertSame('#112233', $institut->couleur_primaire);
        $this->assertSame('#445566', $institut->couleur_secondaire);
        $this->assertSame('#778899', $institut->couleur_accent);
    }

    public function test_reset_apparence_restaure_les_couleurs_par_defaut(): void
    {
        [$admin, $institut] = $this->adminAvecInstitut();

        $institut->update([
            'couleur_primaire'   => '#112233',
            'couleur_secondaire' => '#445566',
            'couleur_accent'     => '#778899',
        ]);

        $this->actingAs($admin)
            ->post(route('dashboard.mes-instituts.apparence.reset'))
            ->assertRedirect();

        $institut->refresh();
        $this->assertSame('#7c3aed', $institut->couleur_primaire);
        $this->assertSame('#ec4899', $institut->couleur_secondaire);
        $this->assertSame('#f59e0b', $institut->couleur_accent);
    }

    public function test_update_fiche_institut_fonctionne_toujours(): void
    {
        // Vérifie que le déplacement des routes n'a pas cassé la route dynamique
        [$admin, $institut] = $this->adminAvecInstitut();

        $type = EtablissementType::create([
            'code'      => 'salon_coiffure',
            'libelle'   => 'Salon de coiffure',
            'actif'     => true,
            'position'  => 1,
        ]);

        $this->actingAs($admin)
            ->put(route('dashboard.mes-instituts.update', $institut), [
                'nom'       => 'Institut Renommé',
                'type'      => $type->code,
                'ville'     => $institut->ville,
                'telephone' => $institut->telephone,
                'email'     => $institut->email,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Institut Renommé', $institut->fresh()->nom);
    }
}
