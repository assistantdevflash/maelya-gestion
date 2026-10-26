<?php

namespace Tests\Feature\AntiSpam;

use App\Models\MessageContact;
use App\Services\AntiSpamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Protection du formulaire de contact public.
 *
 * Le formulaire est attaqué en permanence : les règles couvrent les leviers
 * classiques (leurre, temps de remplissage, liens, mots-clés, jetables,
 * répétition) et restent silencieuses côté visiteur.
 */
class ContactAntiSpamTest extends TestCase
{
    use RefreshDatabase;

    private function soumettre(array $overrides = [], ?string $jeton = null)
    {
        return $this->post(route('contact.send'), array_merge([
            'nom'     => 'Aicha Kone',
            'email'   => 'aicha@example.com',
            'message' => 'Bonjour, je souhaite des informations sur vos tarifs pour un institut de beauté.',
            '_t'      => $jeton,
        ], $overrides));
    }

    // ─────────────────────────────────────────────────────────────────────
    // 1. Message légitime
    // ─────────────────────────────────────────────────────────────────────

    public function test_un_message_legitime_est_enregistre_non_lu(): void
    {
        $jeton = AntiSpamService::jetonHorodatage();
        $this->travel(6)->seconds();

        $this->soumettre([], $jeton)->assertRedirect(route('contact'));

        $message = MessageContact::first();
        $this->assertNotNull($message);
        $this->assertFalse($message->est_spam);
        $this->assertFalse($message->lu);
        $this->assertSame(0, $message->spam_score);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. Leurres
    // ─────────────────────────────────────────────────────────────────────

    public function test_le_champ_leurre_bloque_l_enregistrement(): void
    {
        $this->post(route('contact.send'), [
            'nom'     => 'Bot',
            'email'   => 'bot@example.com',
            'message' => 'Achetez nos produits géniaux immédiatement.',
            'website' => 'https://spam.example',
        ])->assertRedirect(route('contact'));

        $this->assertSame(0, MessageContact::count());
    }

    public function test_une_soumission_trop_rapide_est_classee_spam(): void
    {
        $jeton = AntiSpamService::jetonHorodatage();
        // Aucun temps écoulé : un humain ne peut pas remplir en < 3 s

        $this->soumettre([], $jeton)->assertRedirect(route('contact'));

        $message = MessageContact::first();
        $this->assertTrue($message->est_spam);
        $this->assertStringContainsString('trop rapide', $message->spam_raison);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Contenu
    // ─────────────────────────────────────────────────────────────────────

    public function test_un_message_avec_plusieurs_liens_est_classee_spam(): void
    {
        $jeton = AntiSpamService::jetonHorodatage();
        $this->travel(6)->seconds();

        $this->soumettre([
            'message' => 'Visitez https://spam1.example et aussi https://spam2.example pour tout savoir.',
        ], $jeton);

        $message = MessageContact::first();
        $this->assertTrue($message->est_spam);
        $this->assertStringContainsString('liens', $message->spam_raison);
    }

    public function test_un_message_avec_mots_cles_suspects_est_classee_spam(): void
    {
        $jeton = AntiSpamService::jetonHorodatage();
        $this->travel(6)->seconds();

        $this->soumettre([
            'message' => 'Nous proposons du guest post et du backlink de qualité pour votre site.',
        ], $jeton);

        $this->assertTrue(MessageContact::first()->est_spam);
    }

    public function test_une_adresse_email_jetable_est_penalisee(): void
    {
        $jeton = AntiSpamService::jetonHorodatage();
        $this->travel(6)->seconds();

        $this->soumettre(['email' => 'contact@mailinator.com'], $jeton);

        $message = MessageContact::first();
        $this->assertGreaterThanOrEqual(25, $message->spam_score);
        $this->assertStringContainsString('douteuse', $message->spam_raison);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 4. Répétition
    // ─────────────────────────────────────────────────────────────────────

    public function test_plus_de_trois_messages_en_24h_est_classee_spam(): void
    {
        // 3 messages légitimes préalables
        for ($i = 1; $i <= 3; $i++) {
            MessageContact::create([
                'nom'     => 'Aicha Kone',
                'email'   => 'aicha@example.com',
                'message' => "Message numéro {$i} concernant vos tarifs.",
                'lu'      => true,
            ]);
        }

        $jeton = AntiSpamService::jetonHorodatage();
        $this->travel(6)->seconds();

        $this->soumettre([], $jeton);

        $dernier = MessageContact::latest('created_at')->first();
        $this->assertTrue($dernier->est_spam);
        $this->assertStringContainsString('Plus de 3 messages', $dernier->spam_raison);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 5. Discrétion
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_reponse_ne_revele_jamais_la_detection(): void
    {
        $jeton = AntiSpamService::jetonHorodatage();

        // Soumission instantanée = spam, mais l'utilisateur voit un succès
        $this->soumettre([], $jeton)
            ->assertRedirect(route('contact'))
            ->assertSessionHas('success', 'Votre message a bien été envoyé ! Nous vous répondrons sous 24h.');
    }

    public function test_un_spam_ne_gonfle_pas_le_compteur_de_non_lus(): void
    {
        $jeton = AntiSpamService::jetonHorodatage();

        $this->soumettre([], $jeton);

        $this->assertTrue(MessageContact::first()->est_spam);
        $this->assertTrue(MessageContact::first()->lu);
        $this->assertSame(0, MessageContact::nonLus()->count());
        $this->assertSame(1, MessageContact::spam()->count());
    }
}
