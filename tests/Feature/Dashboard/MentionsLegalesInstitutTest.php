<?php

namespace Tests\Feature\Dashboard;

use App\Models\Commande;
use App\Models\EtablissementType;
use App\Models\Institut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mentions légales de l'établissement (RCCM et NCC).
 *
 * Ces deux champs existaient en base et étaient imprimés sur les PDF, mais
 * aucun écran ne permettait de les saisir : ils restaient donc vides.
 */
class MentionsLegalesInstitutTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(array $attrs = []): array
    {
        EtablissementType::create([
            'code'     => 'salon_coiffure',
            'libelle'  => 'Salon de coiffure',
            'actif'    => true,
            'position' => 1,
        ]);

        $admin = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($admin->institut_id);

        if ($attrs) {
            // forceFill : rccm/numero_fiscal ne sont pas encore garantis
            // fillable au moment où ce test est écrit
            $institut->forceFill($attrs)->save();
        }

        return [$admin, $institut->fresh()];
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'nom'    => 'Institut Bercail',
            'type'   => 'salon_coiffure',
            '_facturation' => '1',
            'facturation_sequence' => 'annuelle',
        ], $surcharge);
    }

    public function test_les_mentions_sont_enregistrees(): void
    {
        [$admin, $institut] = $this->contexte();

        $this->actingAs($admin)
            ->put(route('dashboard.mes-instituts.update', $institut), $this->donnees([
                'rccm'          => 'CI-ABJ-2020-B-12345',
                'numero_fiscal' => '2012345678 A',
            ]))
            ->assertRedirect(route('dashboard.mes-instituts.edit', $institut));

        $institut->refresh();

        // Le vrai piège : sans `$fillable`, update() ignore les colonnes
        // silencieusement et le test passerait au vert avec des valeurs NULL.
        $this->assertSame('CI-ABJ-2020-B-12345', $institut->rccm);
        $this->assertSame('2012345678 A', $institut->numero_fiscal);
    }

    public function test_les_mentions_sont_modifiables_et_effacables(): void
    {
        [$admin, $institut] = $this->contexte([
            'rccm'          => 'CI-ABJ-2020-B-12345',
            'numero_fiscal' => '2012345678 A',
        ]);

        $this->actingAs($admin)
            ->put(route('dashboard.mes-instituts.update', $institut), $this->donnees([
                'rccm'          => 'CI-ABJ-2021-B-99999',
                'numero_fiscal' => '',
            ]))
            ->assertRedirect();

        $institut->refresh();

        $this->assertSame('CI-ABJ-2021-B-99999', $institut->rccm);
        // Un champ vidé arrive en chaîne vide, que Laravel convertit en NULL
        $this->assertNull($institut->numero_fiscal);
    }

    public function test_le_formulaire_affiche_les_valeurs_enregistrees(): void
    {
        [$admin, $institut] = $this->contexte([
            'rccm'          => 'CI-ABJ-2020-B-12345',
            'numero_fiscal' => '2012345678 A',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.mes-instituts.edit', $institut))
            ->assertOk()
            ->assertSee('name="rccm"', false)
            ->assertSee('name="numero_fiscal"', false)
            ->assertSee('CI-ABJ-2020-B-12345')
            ->assertSee('2012345678 A');
    }

    public function test_les_mentions_sont_imprimees_sur_les_documents(): void
    {
        [, $institut] = $this->contexte([
            'rccm'          => 'CI-ABJ-2020-B-12345',
            'numero_fiscal' => '2012345678 A',
        ]);

        $commande = Commande::withoutGlobalScopes()->create([
            'institut_id'      => $institut->id,
            'numero'           => 'CMD-TEST-0001',
            'client_prenom'    => 'Awa',
            'client_nom'       => 'Kone',
            'client_telephone' => '0701020304',
            'client_adresse'   => 'Abidjan',
            'sous_total'       => 5000,
            'frais_livraison'  => 0,
            'total'            => 5000,
            'statut'           => 'nouvelle',
        ]);

        foreach (['pdf.facture-commande', 'pdf.bon-livraison'] as $vue) {
            $html = view($vue, ['commande' => $commande->load('institut')])->render();

            $this->assertStringContainsString('RCCM : CI-ABJ-2020-B-12345', $html, $vue);
            $this->assertStringContainsString('NCC : 2012345678 A', $html, $vue);
        }
    }

    public function test_les_mentions_absentes_ne_laissent_pas_de_ligne_vide(): void
    {
        [, $institut] = $this->contexte();

        $commande = Commande::withoutGlobalScopes()->create([
            'institut_id'      => $institut->id,
            'numero'           => 'CMD-TEST-0002',
            'client_prenom'    => 'Awa',
            'client_nom'       => 'Kone',
            'client_telephone' => '0701020304',
            'client_adresse'   => 'Abidjan',
            'sous_total'       => 1000,
            'frais_livraison'  => 0,
            'total'            => 1000,
            'statut'           => 'nouvelle',
        ]);

        $html = view('pdf.facture-commande', ['commande' => $commande->load('institut')])->render();

        $this->assertStringNotContainsString('RCCM :', $html);
        $this->assertStringNotContainsString('NCC :', $html);
    }

    public function test_les_mentions_sont_validees(): void
    {
        [$admin, $institut] = $this->contexte();

        $this->actingAs($admin)
            ->put(route('dashboard.mes-instituts.update', $institut), $this->donnees([
                'rccm' => str_repeat('X', 51),
            ]))
            ->assertSessionHasErrors('rccm');

        $this->assertNull($institut->fresh()->rccm);
    }
}
