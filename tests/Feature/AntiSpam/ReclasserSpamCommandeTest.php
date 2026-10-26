<?php

namespace Tests\Feature\AntiSpam;

use App\Models\MessageContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Commande `messages:reclasser-spam` : rejeu du moteur sur l'historique.
 */
class ReclasserSpamCommandeTest extends TestCase
{
    use RefreshDatabase;

    private function message(array $attrs = [], ?string $date = null): MessageContact
    {
        $message = MessageContact::create(array_merge([
            'nom'     => 'Aicha Kone',
            'email'   => 'aicha@example.com',
            'message' => 'Bonjour, je souhaite connaître vos tarifs pour un institut de beauté.',
            'lu'      => false,
            'est_spam' => false,
        ], $attrs));

        if ($date) {
            DB::table('messages_contact')->where('id', $message->id)
                ->update(['created_at' => $date, 'updated_at' => $date]);
            $message = $message->fresh();
        }

        return $message;
    }

    public function test_un_message_historique_avec_liens_est_reclasse(): void
    {
        $spam = $this->message([
            'email'   => 'robot@mailinator.com',
            'message' => 'Achetez maintenant sur https://spam.example et https://spam2.example.',
        ], now()->subMonths(2)->toDateTimeString());

        $this->artisan('messages:reclasser-spam')->assertSuccessful();

        $spam->refresh();
        $this->assertTrue($spam->est_spam);
        $this->assertTrue($spam->lu);
        $this->assertGreaterThanOrEqual(40, $spam->spam_score);
        $this->assertNotNull($spam->spam_raison);
    }

    public function test_un_message_legitime_reste_intact(): void
    {
        $legitime = $this->message();

        $this->artisan('messages:reclasser-spam')->assertSuccessful();

        $legitime->refresh();
        $this->assertFalse($legitime->est_spam);
        $this->assertFalse($legitime->lu);
    }

    public function test_le_dry_run_ne_modifie_rien(): void
    {
        $spam = $this->message([
            'email'   => 'robot@mailinator.com',
            'message' => 'Guest post et backlink disponibles, visitez https://a.example et https://b.example.',
        ]);

        $this->artisan('messages:reclasser-spam', ['--dry-run' => true])
            ->expectsOutputToContain('Simulation')
            ->assertSuccessful();

        $spam->refresh();
        $this->assertFalse($spam->est_spam);
        $this->assertFalse($spam->lu);
    }

    public function test_les_messages_deja_classes_ne_sont_pas_reevalues_sans_force(): void
    {
        $dejaSpam = $this->message([
            'nom'     => 'Ancien robot',
            'message' => 'Bonjour, je souhaite connaître vos tarifs.',
            'est_spam' => true,
            'lu'       => true,
        ]);

        // Sans --force, le message reste tel quel même s'il paraît légitime
        $this->artisan('messages:reclasser-spam')->assertSuccessful();

        $this->assertTrue($dejaSpam->fresh()->est_spam);
    }

    public function test_l_option_force_rehabilite_un_faux_positif(): void
    {
        $fauxPositif = $this->message([
            'nom'     => 'Vrai client',
            'est_spam' => true,
            'lu'       => true,
        ]);

        $this->artisan('messages:reclasser-spam', ['--force' => true])->assertSuccessful();

        $fauxPositif->refresh();
        $this->assertFalse($fauxPositif->est_spam);
        $this->assertNull($fauxPositif->spam_raison);
    }

    public function test_l_option_depuis_ignore_les_messages_anciens(): void
    {
        $spam = $this->message([
            'email'   => 'robot@mailinator.com',
            'message' => 'Guest post et backlink, voir https://a.example et https://b.example.',
        ], now()->subMonths(3)->toDateTimeString());

        $this->artisan('messages:reclasser-spam', ['--depuis' => now()->subDays(7)->toDateString()])
            ->assertSuccessful();

        $this->assertFalse($spam->fresh()->est_spam);
    }

    public function test_un_message_reclasse_sort_du_compteur_de_non_lus(): void
    {
        $this->message([
            'email'   => 'robot@mailinator.com',
            'message' => 'Guest post et backlink, voir https://a.example et https://b.example.',
        ]);

        $this->assertSame(1, MessageContact::nonLus()->count());

        $this->artisan('messages:reclasser-spam')->assertSuccessful();

        $this->assertSame(0, MessageContact::nonLus()->count());
        $this->assertSame(1, MessageContact::spam()->count());
    }

    public function test_une_date_invalide_est_refusee(): void
    {
        $this->artisan('messages:reclasser-spam', ['--depuis' => 'pas-une-date'])
            ->assertFailed();
    }
}
