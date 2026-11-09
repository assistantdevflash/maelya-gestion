<?php

namespace Tests\Feature\Dashboard;

use App\Models\EtablissementType;
use App\Models\Institut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Structure de la page d'édition d'un établissement.
 *
 * La page mélangeait plusieurs formulaires (identité, couleurs, horaires,
 * facturation) avec un bouton d'enregistrement par bloc : on ne savait plus
 * ce qui était sauvegardé. Elle est désormais découpée en sections visibles
 * et pilotée par un seul formulaire.
 */
class PageEditionInstitutTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(): array
    {
        EtablissementType::create([
            'code'     => 'salon_coiffure',
            'libelle'  => 'Salon de coiffure',
            'actif'    => true,
            'position' => 1,
        ]);

        $admin = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($admin->institut_id);

        return [$admin, $institut];
    }

    private function html(): string
    {
        [$admin, $institut] = $this->contexte();

        return $this->actingAs($admin)
            ->get(route('dashboard.mes-instituts.edit', $institut))
            ->assertOk()
            ->getContent();
    }

    public function test_la_page_expose_les_sections_attendues(): void
    {
        $html = $this->html();

        foreach (['identite', 'logo', 'apparence', 'exploitation'] as $section) {
            $this->assertStringContainsString('id="' . $section . '"', $html, "Section {$section} absente.");
        }
    }

    public function test_un_seul_formulaire_pilote_les_sections_modifiables(): void
    {
        $html = $this->html();

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Un formulaire dans un formulaire est invalide en HTML et casse la
        // soumission : c'était le cas avant la refonte.
        $this->assertSame(
            0,
            $xpath->query('//form//form')->count(),
            'Des formulaires sont imbriqués sur la page.'
        );

        $this->assertSame(
            1,
            $xpath->query('//form[@id="form-etablissement"]')->count(),
            'Le formulaire principal est absent ou dupliqué.'
        );

        // Le bouton de l'en-tête soumet bien le formulaire principal
        $this->assertSame(
            1,
            $xpath->query('//button[@form="form-etablissement"]')->count(),
            "Le bouton d'en-tête n'est pas relié au formulaire principal."
        );
    }

    public function test_les_champs_du_formulaire_principal_sont_tous_soumis(): void
    {
        $html = $this->html();

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $form = (new \DOMXPath($dom))->query('//form[@id="form-etablissement"]')->item(0);
        $this->assertNotNull($form);

        $attendus = [
            'nom', 'type', 'ville', 'adresse', 'telephone', 'email',
            'couleur_primaire', 'couleur_secondaire', 'couleur_accent',
            'heure_ouverture', 'heure_fermeture', 'jours_ouverture[]',
            'facturation_sequence', 'facture_relance_active', 'facture_relance_message',
            'rccm', 'numero_fiscal', '_facturation',
        ];

        $presents = [];
        foreach ((new \DOMXPath($dom))->query('.//input|.//select|.//textarea', $form) as $champ) {
            if ($champ->hasAttribute('name')) {
                $presents[] = $champ->getAttribute('name');
            }
        }

        foreach ($attendus as $attendu) {
            $this->assertContains($attendu, $presents, "Le champ {$attendu} n'est pas dans le formulaire principal.");
        }
    }

    public function test_le_logo_reste_dans_son_propre_formulaire(): void
    {
        $html = $this->html();

        // L'upload passe par une route dédiée : il ne doit pas être soumis
        // avec les autres réglages.
        $this->assertStringContainsString('form="form-logo"', $html);
        $this->assertStringContainsString('id="form-logo"', $html);
    }
}
