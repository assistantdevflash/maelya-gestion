<?php

namespace Tests\Feature\AntiSpam;

use App\Models\MessageContact;
use App\Models\Notif;
use App\Models\User;
use App\Services\AntiSpamService;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Notification des super-admins à la réception d'un message de contact.
 */
class NotificationMessageContactTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'actif' => true]);
    }

    /** Envoie un message légitime (jeton assez ancien pour ne pas être pénalisé). */
    private function envoyerMessageLegitime(array $overrides = [])
    {
        $jeton = AntiSpamService::jetonHorodatage();
        $this->travel(6)->seconds();

        return $this->post(route('contact.send'), array_merge([
            'nom'     => 'Aicha Kone',
            'email'   => 'aicha@example.com',
            'message' => 'Bonjour, je souhaite des informations sur vos tarifs.',
            '_t'      => $jeton,
        ], $overrides));
    }

    // ─────────────────────────────────────────────────────────────────────

    public function test_un_message_legitime_notifie_les_super_admins(): void
    {
        $admin = $this->superAdmin();

        $this->envoyerMessageLegitime()->assertRedirect(route('contact'));

        $message = MessageContact::first();

        $this->assertDatabaseHas('notifs', [
            'user_id' => $admin->id,
            'type'    => 'nouveau_message',
        ]);

        $notif = Notif::where('user_id', $admin->id)->first();
        $this->assertSame(route('admin.messages.show', $message), $notif->url);
        $this->assertStringContainsString('Aicha Kone', $notif->corps);
    }

    public function test_le_push_est_envoye_aux_super_admins(): void
    {
        $this->superAdmin();

        $this->mock(PushNotificationService::class, function ($mock) {
            $mock->shouldReceive('sendToAdmins')
                ->once()
                ->withArgs(function ($titre, $corps, $url) {
                    return str_contains($titre, 'Nouveau message')
                        && str_contains($corps, 'Aicha Kone')
                        && str_contains($url, '/admin/messages/');
                });
        });

        $this->envoyerMessageLegitime()->assertRedirect(route('contact'));
    }

    public function test_un_spam_ne_notifie_personne(): void
    {
        $admin = $this->superAdmin();

        $this->mock(PushNotificationService::class, function ($mock) {
            $mock->shouldNotReceive('sendToAdmins');
        });

        // Soumission instantanée → spam
        $this->post(route('contact.send'), [
            'nom'     => 'Robot',
            'email'   => 'robot@example.com',
            'message' => 'Guest post et backlink, voir https://a.example et https://b.example.',
            '_t'      => AntiSpamService::jetonHorodatage(),
        ])->assertRedirect(route('contact'));

        $this->assertTrue(MessageContact::first()->est_spam);
        $this->assertSame(0, Notif::where('user_id', $admin->id)->count());
    }

    public function test_l_absence_de_super_admin_ne_bloque_pas_l_envoi(): void
    {
        // Aucun super-admin en base
        $this->envoyerMessageLegitime()
            ->assertRedirect(route('contact'))
            ->assertSessionHas('success');

        $this->assertSame(1, MessageContact::count());
        $this->assertSame(0, Notif::count());
    }

    public function test_une_erreur_de_notification_ne_bloque_pas_l_enregistrement(): void
    {
        $this->superAdmin();

        $this->mock(PushNotificationService::class, function ($mock) {
            $mock->shouldReceive('sendToAdmins')->andThrow(new \RuntimeException('Service indisponible'));
        });

        $this->envoyerMessageLegitime()
            ->assertRedirect(route('contact'))
            ->assertSessionHas('success');

        // Le message est bien enregistré malgré l'échec du push
        $this->assertSame(1, MessageContact::count());
    }

    public function test_la_notification_est_egalement_envoyee_a_chaque_super_admin(): void
    {
        $admin1 = $this->superAdmin();
        $admin2 = $this->superAdmin();

        $this->envoyerMessageLegitime();

        $this->assertSame(1, Notif::where('user_id', $admin1->id)->count());
        $this->assertSame(1, Notif::where('user_id', $admin2->id)->count());
    }
}
