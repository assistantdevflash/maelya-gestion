<?php

namespace Tests\Feature\Boutique;

use App\Mail\LiensCommandesClient;
use App\Models\Commande;
use App\Models\Institut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Récupération des liens de suivi par un client qui n'a pas de compte.
 *
 * Le principe testé : rien n'est affiché à l'écran, tout part par email. Un
 * visiteur qui essaie des numéros au hasard n'apprend donc jamais rien.
 */
class RetrouverCommandesTest extends TestCase
{
    use RefreshDatabase;

    private int $compteur = 0;

    private function boutique(): Institut
    {
        return $this->creerInstitut([
            'boutique_active'           => true,
            'boutique_option_active'    => true,
            'boutique_option_expire_le' => now()->addMonth()->toDateString(),
        ]);
    }

    private function commande(Institut $institut, array $attributs = []): Commande
    {
        $this->compteur++;

        return Commande::withoutGlobalScopes()->create(array_merge([
            'institut_id'      => $institut->id,
            'numero'           => 'CMD-TEST-' . $this->compteur,
            'client_prenom'    => 'Awa',
            'client_nom'       => 'Kone',
            'client_telephone' => '0701020304',
            'client_email'     => 'awa@example.com',
            'client_adresse'   => 'Abidjan, Cocody',
            'sous_total'       => 5000,
            'frais_livraison'  => 0,
            'total'            => 5000,
            'statut'           => 'nouvelle',
        ], $attributs));
    }

    public function test_le_formulaire_est_accessible(): void
    {
        $institut = $this->boutique();

        $this->get(route('shop.commandes.form', $institut->slug))
            ->assertOk()
            ->assertSee('Retrouver mes commandes')
            // Aucune donnée de commande n'est présentée avant vérification
            ->assertSee('aucune commande n\'est affichée sur cette page', false);
    }

    public function test_le_formulaire_est_inaccessible_si_la_boutique_est_fermee(): void
    {
        $institut = $this->boutique();
        $institut->forceFill(['boutique_active' => false])->save();

        $this->get(route('shop.commandes.form', $institut->slug))
            ->assertOk()
            ->assertSee('momentanément fermée');
    }

    public function test_un_telephone_connu_declenche_l_envoi_des_liens(): void
    {
        Mail::fake();

        $institut = $this->boutique();
        $commande = $this->commande($institut);

        $this->post(route('shop.commandes.envoyer', $institut->slug), [
            'contact' => '0701020304',
        ])->assertRedirect(route('shop.commandes.form', $institut->slug));

        Mail::assertSent(LiensCommandesClient::class, function (LiensCommandesClient $mail) use ($commande) {
            return $mail->hasTo('awa@example.com')
                && $mail->lignes->count() === 1
                && $mail->lignes->first()['commande']->numero === $commande->numero;
        });
    }

    public function test_le_telephone_est_reconnu_quel_que_soit_le_format(): void
    {
        Mail::fake();

        $institut = $this->boutique();
        // Numéro stocké avec des espaces et l'indicatif pays
        $this->commande($institut, ['client_telephone' => '+225 07 01 02 03 04']);

        $this->post(route('shop.commandes.envoyer', $institut->slug), [
            'contact' => '0701020304',
        ]);

        Mail::assertSent(LiensCommandesClient::class);
    }

    public function test_un_email_connu_declenche_l_envoi_des_liens(): void
    {
        Mail::fake();

        $institut = $this->boutique();
        $this->commande($institut);

        $this->post(route('shop.commandes.envoyer', $institut->slug), [
            'contact' => 'AWA@example.com',
        ]);

        Mail::assertSent(LiensCommandesClient::class, fn ($mail) => $mail->hasTo('awa@example.com'));
    }

    public function test_un_contact_inconnu_ne_revele_rien_et_n_envoie_rien(): void
    {
        Mail::fake();

        $institut = $this->boutique();
        $this->commande($institut);

        $reponse = $this->post(route('shop.commandes.envoyer', $institut->slug), [
            'contact' => '0999999999',
        ]);

        $connu = $this->post(route('shop.commandes.envoyer', $institut->slug), [
            'contact' => '0701020304',
        ]);

        Mail::assertSent(LiensCommandesClient::class, 1);

        // Message strictement identique : impossible de deviner si le numéro
        // correspond à un client.
        $this->assertSame(
            $reponse->getSession()->get('success'),
            $connu->getSession()->get('success'),
        );
        $this->assertStringContainsString('Si des commandes correspondent', $reponse->getSession()->get('success'));
    }

    public function test_un_client_ne_recoit_que_les_commandes_de_la_boutique_concernee(): void
    {
        Mail::fake();

        $boutiqueA = $this->boutique();
        $boutiqueB = $this->boutique();

        $this->commande($boutiqueA);
        $this->commande($boutiqueB);

        $this->post(route('shop.commandes.envoyer', $boutiqueA->slug), [
            'contact' => '0701020304',
        ]);

        Mail::assertSent(LiensCommandesClient::class, function (LiensCommandesClient $mail) {
            return $mail->lignes->count() === 1
                && $mail->lignes->first()['commande']->institut_id === $mail->institut->id;
        });
    }

    public function test_le_lien_envoye_permet_bien_de_suivre_la_commande(): void
    {
        Mail::fake();

        $institut = $this->boutique();
        $commande = $this->commande($institut);

        $this->post(route('shop.commandes.envoyer', $institut->slug), [
            'contact' => '0701020304',
        ]);

        $lien = null;
        Mail::assertSent(LiensCommandesClient::class, function (LiensCommandesClient $mail) use (&$lien) {
            $lien = $mail->lignes->first()['lien'];

            return true;
        });

        // Le lien signé doit ouvrir la page de suivi sans autre authentification
        $this->get($lien)
            ->assertOk()
            ->assertSee($commande->numero);
    }

    public function test_la_vue_de_l_email_se_rend(): void
    {
        $institut = $this->boutique();
        $commande = $this->commande($institut);

        // Une erreur Blade dans un email n'est détectée par aucun autre test
        $html = (new LiensCommandesClient($institut, collect([$commande])))->render();

        $this->assertStringContainsString($commande->numero, $html);
        $this->assertStringContainsString('Suivre cette commande', $html);
        $this->assertStringContainsString($institut->nom, $html);
    }

    public function test_une_saisie_trop_courte_ne_declenche_aucune_recherche(): void
    {
        Mail::fake();

        $institut = $this->boutique();
        $this->commande($institut);

        $this->post(route('shop.commandes.envoyer', $institut->slug), [
            'contact' => '0701',
        ]);

        Mail::assertNothingSent();
    }
}
