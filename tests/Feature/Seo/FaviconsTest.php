<?php

namespace Tests\Feature\Seo;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Icônes du site.
 *
 * Google et Chrome Android ne lisent pas les favicons SVG : quand le SVG est
 * la seule icône déclarée, ils fabriquent une pastille avec l'initiale du
 * site — le fameux « M » violet. Et la notification push affichait une lettre
 * « M » parce que badge-72.svg contenait littéralement un <text>M</text>.
 *
 * Ces tests verrouillent les deux régressions.
 */
class FaviconsTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_page_daccueil_declare_des_icones_png_et_ico(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['favicon.ico', 'favicon-32x32.png', 'favicon-96x96.png', 'favicon-192x192.png'] as $fichier) {
            $this->assertStringContainsString(
                $fichier,
                $html,
                "L'icône {$fichier} doit être déclarée : Google ignore les favicons SVG."
            );
        }

        $this->assertStringContainsString('apple-touch-icon', $html);
    }

    public function test_les_fichiers_dicones_existent_vraiment(): void
    {
        foreach ([
            'favicon.ico',
            'favicon-32x32.png',
            'favicon-48x48.png',
            'favicon-96x96.png',
            'favicon-192x192.png',
            'favicon.svg',
            'icons/apple-touch-icon.png',
            'icons/icon-192.png',
            'icons/icon-512.png',
            'icons/badge-72.png',
        ] as $fichier) {
            $this->assertFileExists(
                public_path($fichier),
                "Le fichier public/{$fichier} est référencé mais absent."
            );
        }
    }

    public function test_le_badge_de_notification_nest_pas_une_lettre(): void
    {
        $svg = file_get_contents(public_path('icons/badge-72.svg'));

        $this->assertStringNotContainsString(
            '<text',
            $svg,
            'Le badge de notification ne doit pas être une lettre : Android l\'affiche comme une pastille avec l\'initiale du site.'
        );
    }

    public function test_le_logo_structure_pointe_vers_un_png(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // Google attend une image matricielle pour Organization.logo.
        $this->assertStringContainsString('"logo": "', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/"logo":\s*"[^"]*\.svg"/',
            $html,
            'Le logo du JSON-LD ne doit pas être un SVG.'
        );
    }

    public function test_la_page_de_connexion_declare_les_icones(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('favicon-96x96.png', $html);
    }
}
