<?php

namespace Tests\Feature\Performance;

use App\Models\Institut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garde-fous sur la stratégie de chargement des assets.
 *
 * Contexte : Alpine était fourni par Livewire (`@livewireScripts`), ce qui
 * imposait ~237 Ko de JavaScript Livewire sur TOUTES les pages du dashboard
 * alors qu'un seul écran (la caisse) en a besoin. Les pages sans Livewire
 * chargent désormais Alpine seul via `resources/js/alpine-app.js`.
 *
 * ⚠️ Si ces tests cassent, c'est probablement que la page concernée n'a plus
 * d'Alpine du tout (menus déroulants morts) ou charge les deux (conflit).
 */
class AssetLoadingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->creerAdmin(['role' => 'admin']);
    }

    private function html(string $route): string
    {
        return $this->actingAs($this->user)->get(route($route))->assertOk()->getContent();
    }

    public function test_une_page_sans_livewire_charge_alpine_autonome(): void
    {
        $html = $this->html('dashboard.clients.index');

        $this->assertStringContainsString('alpine-app', $html, 'Alpine autonome manquant : les menus déroulants ne fonctionneraient pas.');
        $this->assertStringNotContainsString('livewire.js', $html, 'Livewire ne doit pas être chargé sur les pages qui ne l\'utilisent pas.');
    }

    public function test_la_caisse_charge_livewire_et_pas_alpine_en_double(): void
    {
        $html = $this->html('dashboard.caisse');

        $this->assertStringContainsString('livewire', $html, 'La caisse a besoin de Livewire (Alpine + composant POS).');
        $this->assertStringNotContainsString('alpine-app', $html, 'Alpine serait chargé deux fois : conflit garanti.');
    }

    public function test_aucune_feuille_de_style_bloquante_vers_google_fonts(): void
    {
        $html = $this->html('dashboard.clients.index');

        // Le lien doit être non bloquant (media="print" + bascule onload).
        $this->assertMatchesRegularExpression(
            '/fonts\.googleapis\.com[^>]*media="print"/s',
            $html,
            'Les Google Fonts doivent être chargées en mode non bloquant.'
        );
    }

    public function test_le_bundle_global_ne_contient_plus_quill(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);

        $this->assertArrayHasKey('resources/js/alpine-app.js', $manifest);
        $this->assertArrayHasKey('resources/js/quill-editor.js', $manifest);

        // app.js ne doit dépendre d'aucun chunk Quill
        $appChunks = array_map(
            fn ($c) => str_starts_with((string) $c, '_quill') ? 'quill' : $c,
            $manifest['resources/js/app.js']['imports'] ?? []
        );
        $this->assertNotContains('quill', $appChunks, 'Quill ne doit jamais être importé par le bundle global.');

        $quillChunks = $manifest['resources/js/quill-editor.js']['imports'] ?? [];
        $this->assertTrue(
            (bool) array_filter($quillChunks, fn ($c) => str_starts_with((string) $c, '_quill')),
            'Quill doit rester isolé dans son propre chunk, chargé uniquement par quill-editor.js.'
        );
    }
}
