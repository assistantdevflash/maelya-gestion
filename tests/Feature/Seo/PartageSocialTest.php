<?php

namespace Tests\Feature\Seo;

use App\Helpers\SeoHelper;
use App\Models\Institut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Visuels de partage des liens (Open Graph / Twitter Card).
 *
 * Le lien de l'application affichait un « M » blanc sur fond noir. Il doit
 * désormais montrer le logo officiel ; un établissement montre son propre
 * logo, avec repli sur celui de Maëlya Gestion.
 */
class PartageSocialTest extends TestCase
{
    use RefreshDatabase;

    private Institut $institut;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $admin = $this->creerAdmin();
        $this->institut = Institut::findOrFail($admin->institut_id);
    }

    private function logoStocke(string $nom = 'logos/boutique.png'): string
    {
        Storage::disk('public')->put($nom, 'contenu-image');

        return $nom;
    }

    // ── Visuel officiel ──────────────────────────────────────────────────────

    public function test_le_visuel_officiel_existe_et_est_au_bon_format(): void
    {
        $chemin = public_path(SeoHelper::IMAGE_DEFAUT);

        $this->assertFileExists($chemin, 'Le visuel de partage doit être présent dans public/.');

        [$largeur, $hauteur] = getimagesize($chemin);

        $this->assertSame(SeoHelper::LARGEUR_DEFAUT, $largeur);
        $this->assertSame(SeoHelper::HAUTEUR_DEFAUT, $hauteur);
        $this->assertSame('image/png', getimagesize($chemin)['mime'], 'WhatsApp n\'affiche pas les SVG.');
    }

    public function test_l_accueil_annonce_le_logo_officiel(): void
    {
        $reponse = $this->get(route('home'))->assertOk();

        $reponse->assertSee('images/og-maelya.png', false);
        // L'ancienne vignette « M blanc sur fond noir » ne doit plus être servie.
        $reponse->assertDontSee('og-image.png', false);
        $reponse->assertDontSee('og-image.svg', false);
    }

    public function test_aucune_vue_ne_reference_l_ancienne_vignette(): void
    {
        $fautives = [];

        foreach (glob(resource_path('views/**/*.blade.php')) as $fichier) {
            $contenu = file_get_contents($fichier);
            if (str_contains($contenu, 'og-image.png') || str_contains($contenu, 'og-image.svg')) {
                $fautives[] = str_replace(resource_path('views/'), '', $fichier);
            }
        }

        $this->assertSame([], $fautives, 'Ces vues utilisent encore l\'ancien visuel.');
    }

    // ── Établissement avec logo ──────────────────────────────────────────────

    public function test_la_vitrine_utilise_le_logo_de_l_etablissement(): void
    {
        $logo = $this->logoStocke();
        $this->institut->update(['logo' => $logo, 'vitrine_active' => true]);

        $this->get(route('vitrine.show', $this->institut->slug))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($logo), false)
            ->assertDontSee('images/og-maelya.png', false);
    }

    public function test_la_boutique_utilise_le_logo_de_l_etablissement(): void
    {
        $logo = $this->logoStocke();
        $this->institut->update(['logo' => $logo, 'boutique_active' => true]);

        $this->get(route('shop.index', $this->institut->slug))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($logo), false);
    }

    // ── Replis ───────────────────────────────────────────────────────────────

    public function test_sans_logo_la_vitrine_montre_le_logo_officiel(): void
    {
        $this->institut->update(['logo' => null, 'vitrine_active' => true]);

        $this->get(route('vitrine.show', $this->institut->slug))
            ->assertOk()
            ->assertSee(SeoHelper::imageDefaut(), false);
    }

    public function test_un_logo_absent_du_disque_retombe_sur_le_logo_officiel(): void
    {
        // Chemin en base, fichier supprimé : cas classique d'une vignette vide.
        $this->institut->update(['logo' => 'logos/disparu.png', 'vitrine_active' => true]);

        $this->get(route('vitrine.show', $this->institut->slug))
            ->assertOk()
            ->assertSee(SeoHelper::imageDefaut(), false)
            ->assertDontSee('logos/disparu.png', false);
    }

    public function test_le_helper_choisit_toujours_une_image_existante(): void
    {
        $this->assertSame(SeoHelper::imageDefaut(), SeoHelper::imagePourInstitut(null));

        $this->institut->update(['logo' => null]);
        $this->assertSame(SeoHelper::imageDefaut(), SeoHelper::imagePourInstitut($this->institut));

        $logo = $this->logoStocke('logos/present.png');
        $this->institut->update(['logo' => $logo]);
        $this->assertSame(Storage::disk('public')->url($logo), SeoHelper::imagePourInstitut($this->institut));

        $this->institut->update(['logo' => 'logos/absent.png']);
        $this->assertSame(SeoHelper::imageDefaut(), SeoHelper::imagePourInstitut($this->institut));
    }

    public function test_les_dimensions_ne_sont_declarees_que_pour_le_visuel_officiel(): void
    {
        $this->assertTrue(SeoHelper::estImageDefaut(SeoHelper::imageDefaut()));
        $this->assertFalse(SeoHelper::estImageDefaut(asset('storage/logos/autre.png')));
    }
}
