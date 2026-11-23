<?php

namespace Tests\Feature\Admin;

use App\Mail\EmailManuel;
use App\Models\AnnonceAdmin;
use App\Models\EmailCampagne;
use App\Models\Institut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Module « Envoyer un email » de l'espace super-admin.
 *
 * L'envoi se fait en deux temps : création de la campagne, puis traitement
 * de tranches successives. Aucun message n'est envoyé à la création.
 */
class EmailsAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $proprietaireA;
    private User $proprietaireB;
    private Institut $institutA1;
    private Institut $institutA2;
    private Institut $institutB1;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->superAdmin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        // Propriétaire A gère DEUX établissements, propriétaire B un seul.
        $this->proprietaireA = $this->creerAdmin(['role' => 'admin']);
        $this->proprietaireB = $this->creerAdmin(['role' => 'admin']);

        $this->institutA1 = Institut::findOrFail($this->proprietaireA->institut_id);
        $this->institutB1 = Institut::findOrFail($this->proprietaireB->institut_id);

        $this->institutA2 = $this->creerInstitut(['nom' => 'Succursale A']);
        $this->institutA2->forceFill(['proprietaire_id' => $this->proprietaireA->id])->save();
    }

    private function charge(array $surcharge = []): array
    {
        return array_merge([
            'mode'       => 'tous',
            'send_mode'  => 'email',
            'sujet'      => 'Nouveautés de novembre',
            'corps'      => '<p>Bonjour, voici les nouveautés.</p>',
        ], $surcharge);
    }

    // ── Accès ────────────────────────────────────────────────────────────────

    public function test_un_administrateur_simple_ne_peut_pas_composer(): void
    {
        $this->actingAs($this->proprietaireA)
            ->get(route('admin.emails.composer'))
            ->assertStatus(403);
    }

    public function test_l_historique_s_ouvre_sur_l_onglet_demande(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.emails.index', ['tab' => 'bannieres']))
            ->assertOk()
            ->assertSee("tab: 'bannieres'", false);
    }

    // ── Compositeur ──────────────────────────────────────────────────────────

    public function test_le_compositeur_ne_contient_plus_de_champ_requis_cache(): void
    {
        $contenu = $this->actingAs($this->superAdmin)
            ->get(route('admin.emails.composer'))
            ->assertOk()
            ->getContent();

        // Le bug historique : deux champs `push_titre` dont un `required`
        // masqué, qui bloquait silencieusement toute soumission.
        $this->assertSame(1, substr_count($contenu, 'id="push-titre"'));
        $this->assertStringNotContainsString('name="push_titre"', $contenu);
        $this->assertStringNotContainsString('name="banner_message"', $contenu);
        $this->assertStringNotContainsString(' required', $contenu);
    }

    public function test_le_compositeur_propose_les_trois_etapes(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.emails.composer'))
            ->assertOk()
            ->assertSee('Par quel canal ?')
            ->assertSee('Qui doit recevoir ?')
            ->assertSee('Aperçu')
            ->assertSee('envoyer un test');
    }

    // ── Création de campagne ─────────────────────────────────────────────────

    public function test_la_campagne_dedoublonne_les_destinataires(): void
    {
        $reponse = $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.demarrer'), $this->charge());

        $reponse->assertOk();

        // 3 établissements, mais seulement 2 propriétaires distincts.
        $this->assertSame(2, $reponse->json('total'));

        $campagne = EmailCampagne::firstOrFail();
        $this->assertSame('en_cours', $campagne->statut);
        $this->assertSame(2, $campagne->nb_total);
        $this->assertCount(2, $campagne->cibles);

        $emails = array_column($campagne->cibles, 'email');
        $this->assertSame(count($emails), count(array_unique($emails)));
    }

    public function test_aucun_message_n_est_envoye_a_la_creation(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.demarrer'), $this->charge())
            ->assertOk();

        Mail::assertNothingSent();
    }

    public function test_un_corps_vide_est_refuse(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.demarrer'), $this->charge(['corps' => '<p><br></p>']))
            ->assertStatus(422)
            ->assertJson(['ok' => false]);

        $this->assertSame(0, EmailCampagne::count());
    }

    public function test_le_mode_personnalise_cree_une_cible_unique(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.demarrer'), $this->charge([
                'mode'               => 'personnalise',
                'email_personnalise' => 'contact@exemple.test',
                'nom_personnalise'   => 'Marie',
            ]))
            ->assertOk()
            ->assertJson(['ok' => true, 'total' => 1]);
    }

    // ── Traitement des tranches ──────────────────────────────────────────────

    public function test_les_tranches_envoient_tout_et_cloturent_la_campagne(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.demarrer'), $this->charge())
            ->assertOk();

        $campagne = EmailCampagne::firstOrFail();

        // 2 destinataires, lots de 1 pour forcer deux appels.
        config(['admin.emails.taille_lot' => 1]);

        $etat = null;
        for ($i = 0; $i < 5 && ($etat['termine'] ?? false) === false; $i++) {
            $etat = $this->actingAs($this->superAdmin)
                ->postJson(route('admin.emails.campagne.lot', $campagne))
                ->assertOk()
                ->json();
        }

        $this->assertTrue($etat['termine']);
        $this->assertSame(2, $etat['envoyes']);
        $this->assertSame(0, $etat['echecs']);

        $campagne->refresh();
        $this->assertSame('termine', $campagne->statut);
        $this->assertSame(2, $campagne->nb_envoyes);
        $this->assertCount(2, $campagne->destinataires_emails);

        Mail::assertSent(EmailManuel::class, 2);
    }

    public function test_une_campagne_est_reprise_la_ou_elle_s_est_arretee(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.demarrer'), $this->charge())
            ->assertOk();

        $campagne = EmailCampagne::firstOrFail();

        // Simule un envoi interrompu après une cible.
        $campagne->update(['nb_envoyes' => 1]);

        $etat = $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.lot', $campagne))
            ->assertOk()
            ->json();

        $this->assertTrue($etat['termine'], 'Le reste du lot doit être traité.');
        $this->assertSame(2, $etat['envoyes']);
        Mail::assertSent(EmailManuel::class, 1, 'Seule la cible restante est renvoyée.');
    }

    public function test_un_echec_total_marque_la_campagne_en_echec(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.demarrer'), $this->charge())
            ->assertOk();

        $campagne = EmailCampagne::firstOrFail();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP indisponible'));

        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.lot', $campagne))
            ->assertOk();

        $campagne->refresh();
        $this->assertSame('echec', $campagne->statut);
        $this->assertSame(2, $campagne->nb_echecs);
    }

    // ── Bannière ─────────────────────────────────────────────────────────────

    public function test_le_mode_banniere_cree_une_annonce_sans_campagne(): void
    {
        $reponse = $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.demarrer'), $this->charge([
                'send_mode'      => 'banner',
                'banner_titre'   => 'Maintenance prévue',
                'banner_message' => 'Le service sera indisponible dimanche.',
                'banner_type'    => 'warning',
            ]))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertStringContainsString('tab=bannieres', $reponse->json('redirect'));

        $annonce = AnnonceAdmin::firstOrFail();
        $this->assertSame('Maintenance prévue', $annonce->titre);
        $this->assertSame('tous', $annonce->cible);

        $this->assertSame(0, EmailCampagne::count());
        Mail::assertNothingSent();
    }

    public function test_une_banniere_sans_titre_est_refusee(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.campagne.demarrer'), $this->charge([
                'send_mode'      => 'banner',
                'banner_message' => 'Message sans titre',
            ]))
            ->assertStatus(422);

        $this->assertSame(0, AnnonceAdmin::count());
    }

    // ── Envoi de test ────────────────────────────────────────────────────────

    public function test_l_envoi_de_test_part_vers_l_admin_connecte(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.test'), [
                'sujet' => 'Essai',
                'corps' => '<p>Ceci est un test.</p>',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        Mail::assertSent(EmailManuel::class, function (EmailManuel $mail) {
            return $mail->hasTo($this->superAdmin->email)
                && str_contains($mail->sujet, '[TEST]');
        });
    }

    public function test_un_test_sans_sujet_est_refuse(): void
    {
        $this->actingAs($this->superAdmin)
            ->postJson(route('admin.emails.test'), ['sujet' => '', 'corps' => '<p>x</p>'])
            ->assertStatus(422);

        Mail::assertNothingSent();
    }
}
