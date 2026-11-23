<?php

namespace Tests\Feature\Seo;

use App\Models\EtablissementType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les types d'établissement saisis dans l'espace super admin pilotent le
 * référencement du site public.
 *
 * Créer, renommer ou désactiver un type doit se répercuter immédiatement sur
 * la page d'accueil, le pied de page, les pages « métier » et le sitemap.
 */
class TypesEtablissementSeoTest extends TestCase
{
    use RefreshDatabase;

    private function creerType(string $code, string $libelle, int $position = 1, bool $actif = true): EtablissementType
    {
        return EtablissementType::create([
            'code' => $code,
            'libelle' => $libelle,
            'position' => $position,
            'actif' => $actif,
        ]);
    }

    // ── Page d'accueil ───────────────────────────────────────────────────────

    public function test_l_accueil_liste_les_types_actifs(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);
        $this->creerType('auto_ecole', 'Auto-école', 2);

        $reponse = $this->get(route('home'))->assertOk();

        $reponse->assertSee('Salon de coiffure');
        $reponse->assertSee('Auto-école');
        $reponse->assertSee(route('secteur', 'salon-coiffure'), false);
        $reponse->assertSee(route('secteur', 'auto-ecole'), false);
    }

    public function test_un_type_desactive_n_apparait_pas_sur_l_accueil(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);
        $this->creerType('imprimerie', 'Imprimerie', 2, false);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Salon de coiffure')
            ->assertDontSee('Imprimerie');
    }

    public function test_le_type_autre_reste_hors_du_referencement(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);
        $this->creerType('autre', 'Autre', 99);

        $reponse = $this->get(route('home'))->assertOk();

        $reponse->assertDontSee(route('secteur', 'autre'), false);
        $this->get(route('secteur', 'autre'))->assertNotFound();
    }

    public function test_un_type_cree_depuis_l_administration_apparait_immediatement(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('Nail bar');

        // Simule l'ajout depuis l'espace super admin, après le premier rendu.
        $this->creerType('onglerie', 'Nail bar', 1);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Nail bar')
            ->assertSee(route('secteur', 'onglerie'), false);
    }

    public function test_la_description_de_l_accueil_cite_les_types_actifs(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('content="Logiciel de gestion pour salon de coiffure', false);
    }

    public function test_la_description_de_l_accueil_ne_depasse_pas_la_limite(): void
    {
        foreach ([
            ['salon_coiffure', 'Salon de coiffure'],
            ['institut_beaute', 'Institut de beauté'],
            ['centre_esthetique', 'Centre esthétique'],
            ['auto_ecole', 'Auto-école'],
            ['cabinet_medical', 'Cabinet médical et paramédical'],
            ['atelier_technique', 'Atelier et service technique'],
            ['centre_formation', 'Centre de formation'],
            ['informatique_telephonie', 'Informatique et téléphonie'],
        ] as $index => [$code, $libelle]) {
            $this->creerType($code, $libelle, $index + 1);
        }

        $html = $this->get(route('home'))->assertOk()->getContent();

        preg_match('/<meta name="description" content="(.*?)">/s', $html, $correspondances);
        $description = html_entity_decode($correspondances[1] ?? '', ENT_QUOTES);

        $this->assertNotSame('', $description);
        $this->assertLessThanOrEqual(160, mb_strlen($description));
    }

    // ── Page « secteur » ─────────────────────────────────────────────────────

    public function test_la_page_secteur_repond_pour_un_type_actif(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);

        $reponse = $this->get(route('secteur', 'salon-coiffure'))->assertOk();

        $reponse->assertSee('Logiciel de gestion pour salon de coiffure');
        $reponse->assertSee('<title>Logiciel de gestion pour salon de coiffure — Maëlya Gestion</title>', false);
        $reponse->assertSee('14 jours d&#039;essai gratuit', false);
    }

    public function test_la_page_secteur_est_introuvable_pour_un_type_desactive(): void
    {
        $this->creerType('imprimerie', 'Imprimerie', 1, false);

        $this->get(route('secteur', 'imprimerie'))->assertNotFound();
    }

    public function test_la_page_secteur_est_introuvable_pour_un_code_inconnu(): void
    {
        $this->get(route('secteur', 'metier-qui-n-existe-pas'))->assertNotFound();
    }

    public function test_la_page_secteur_n_est_pas_exclue_de_l_indexation(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);

        $reponse = $this->get(route('secteur', 'salon-coiffure'))->assertOk();

        $reponse->assertDontSee('name="robots" content="noindex', false);
    }

    public function test_les_donnees_structurees_decrites_le_secteur(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);

        $reponse = $this->get(route('secteur', 'salon-coiffure'))->assertOk();

        // Service (métier), fil d'Ariane, FAQ : les trois blocs attendus.
        $reponse->assertSee('"@type":"Service"', false);
        $reponse->assertSee('"@type":"BreadcrumbList"', false);
        $reponse->assertSee('"@type":"FAQPage"', false);
        $reponse->assertSee('"@type":"Country"', false);
    }

    public function test_les_donnees_structurees_sont_un_json_valide(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);

        $html = $this->get(route('secteur', 'salon-coiffure'))->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $blocs);

        $this->assertNotEmpty($blocs[1]);

        foreach ($blocs[1] as $bloc) {
            $donnees = json_decode($bloc, true);
            $this->assertIsArray($donnees, 'Chaque bloc JSON-LD doit être un JSON valide.');
        }
    }

    public function test_un_type_sans_texte_editorial_recoit_un_contenu_generique(): void
    {
        // Code absent de config/secteurs.php : la page doit néanmoins exister.
        $this->creerType('cordonnerie', 'Cordonnerie', 1);

        $this->get(route('secteur', 'cordonnerie'))
            ->assertOk()
            ->assertSee('Logiciel de gestion pour cordonnerie')
            ->assertSee('Caisse, clients, rendez-vous, stocks et finances', false);
    }

    public function test_le_contenu_editorial_du_secteur_est_affiche(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);

        $contenu = config('secteurs.salon_coiffure');

        $this->get(route('secteur', 'salon-coiffure'))
            ->assertOk()
            ->assertSee($contenu['question']);
    }

    // ── Sitemap et liens internes ────────────────────────────────────────────

    public function test_le_sitemap_contient_les_pages_secteur(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);
        $this->creerType('imprimerie', 'Imprimerie', 2, false);

        $reponse = $this->get(route('sitemap'))->assertOk();

        $reponse->assertSee(route('secteur', 'salon-coiffure'), false);
        $reponse->assertDontSee(route('secteur', 'imprimerie'), false);
    }

    public function test_le_pied_de_page_relie_les_pages_secteur(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);

        // Page publique sans rapport avec les secteurs : les liens doivent
        // tout de même être présents dans le pied de page.
        $this->get(route('faq'))
            ->assertOk()
            ->assertSee(route('secteur', 'salon-coiffure'), false);
    }

    // ── Espace super admin ───────────────────────────────────────────────────

    public function test_l_administration_renvoie_vers_la_page_publique_du_type(): void
    {
        $this->creerType('salon_coiffure', 'Salon de coiffure', 1);

        $superAdmin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        $this->actingAs($superAdmin)
            ->get(route('admin.etablissement-types.index'))
            ->assertOk()
            ->assertSee('Voir la page publique de ce type')
            ->assertSee(route('secteur', 'salon-coiffure'), false);
    }

    public function test_l_administration_ne_lie_pas_un_type_desactive(): void
    {
        $this->creerType('imprimerie', 'Imprimerie', 1, false);

        $superAdmin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        $this->actingAs($superAdmin)
            ->get(route('admin.etablissement-types.index'))
            ->assertOk()
            ->assertDontSee(route('secteur', 'imprimerie'), false);
    }
}
