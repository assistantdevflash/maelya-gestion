<?php

namespace Tests\Feature\Pwa;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bannière « nouvelle version disponible ».
 *
 * Deux régressions vécues :
 *  - le bouton ne produisait rien du tout quand un rechargement automatique
 *    avait eu lieu moins de 15 s avant (garde anti-boucle appliquée aussi au
 *    clic volontaire) ;
 *  - le Service Worker en attente n'était jamais activé, donc recharger
 *    ressuscitait la bannière indéfiniment.
 */
class PwaUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function partial(): string
    {
        return file_get_contents(resource_path('views/partials/pwa-update.blade.php'));
    }

    private function serviceWorker(): string
    {
        return file_get_contents(public_path('sw.js'));
    }

    public function test_le_bouton_sappelle_telecharger(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Une nouvelle version est disponible', $html);
        $this->assertStringContainsString('>Télécharger</button>', $html);
        $this->assertStringNotContainsString('>Recharger</button>', $html);
    }

    public function test_le_clic_force_le_rechargement(): void
    {
        $partial = $this->partial();

        // reloadPage doit accepter un mode forcé, sinon la garde anti-boucle
        // de 15 s avale le clic de l'utilisateur.
        $this->assertStringContainsString('function reloadPage(force)', $partial);
        $this->assertStringContainsString('reloadPage(true)', $partial);
    }

    public function test_le_clic_demande_lactivation_du_service_worker(): void
    {
        $this->assertStringContainsString(
            "postMessage({ type: 'SKIP_WAITING' })",
            $this->partial(),
            'Le clic doit réveiller le worker en attente.'
        );

        $this->assertStringContainsString(
            "event.data.type === 'SKIP_WAITING'",
            $this->serviceWorker(),
            'Le Service Worker doit répondre au message SKIP_WAITING.'
        );
    }

    public function test_le_bouton_a_un_filet_de_securite(): void
    {
        // Si le worker ne répond pas, on recharge quand même : le bouton ne
        // doit jamais rester sans effet visible.
        $this->assertStringContainsString('setTimeout(relancer, 1500)', $this->partial());
    }

    public function test_la_banniere_est_stylisee_par_une_classe(): void
    {
        $this->assertStringContainsString("box.className = 'pwa-update'", $this->partial());

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('.pwa-update {', $css);
        $this->assertStringContainsString('.pwa-update__bouton', $css);

        // Sur mobile la bannière doit passer au-dessus de la barre de
        // navigation inférieure.
        $this->assertStringContainsString('env(safe-area-inset-bottom', $css);
    }
}
