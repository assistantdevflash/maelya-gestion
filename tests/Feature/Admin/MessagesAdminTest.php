<?php

namespace Tests\Feature\Admin;

use App\Models\MessageContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exploitation des messages de contact côté super-admin :
 * filtres, recherche, lecture, actions groupées, gestion du spam.
 */
class MessagesAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'actif' => true]);
    }

    private function message(array $attrs = []): MessageContact
    {
        return MessageContact::create(array_merge([
            'nom'     => 'Aicha Kone',
            'email'   => 'aicha@example.com',
            'message' => 'Bonjour, je voudrais connaître vos tarifs.',
            'lu'      => false,
            'est_spam' => false,
        ], $attrs));
    }

    public function test_la_liste_affiche_les_compteurs(): void
    {
        $this->message(['nom' => 'Client Un']);
        $this->message(['nom' => 'Client Deux', 'lu' => true]);
        $this->message(['nom' => 'Robot', 'est_spam' => true, 'lu' => true]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('3 messages au total')
            ->assertSee('1 non lu')
            ->assertSee('1 indésirable');
    }

    public function test_le_filtre_non_lus_exclut_les_lus_et_les_spams(): void
    {
        $this->message(['nom' => 'Nouveau Message']);
        $this->message(['nom' => 'Ancien Message', 'lu' => true]);
        $this->message(['nom' => 'Message Robot', 'est_spam' => true, 'lu' => true]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.messages.index', ['statut' => 'non_lus']))
            ->assertOk()
            ->assertSee('Nouveau Message')
            ->assertDontSee('Ancien Message')
            ->assertDontSee('Message Robot');
    }

    public function test_le_filtre_spam_isole_les_indesirables(): void
    {
        $this->message(['nom' => 'Vrai Client']);
        $this->message(['nom' => 'Robot Publicitaire', 'est_spam' => true, 'lu' => true]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.messages.index', ['statut' => 'spam']))
            ->assertOk()
            ->assertSee('Robot Publicitaire')
            ->assertDontSee('Vrai Client');
    }

    public function test_la_recherche_filtre_sur_nom_email_et_contenu(): void
    {
        $this->message(['nom' => 'Aicha Kone', 'email' => 'aicha@example.com']);
        $this->message(['nom' => 'Moussa Diallo', 'email' => 'moussa@example.com']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.messages.index', ['q' => 'moussa']))
            ->assertOk()
            ->assertSee('Moussa Diallo')
            ->assertDontSee('Aicha Kone');
    }

    public function test_ouvrir_un_message_le_marque_comme_lu(): void
    {
        $message = $this->message(['lu' => false]);

        $this->assertFalse($message->fresh()->lu);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.messages.show', $message))
            ->assertOk()
            ->assertSee($message->nom);

        $this->assertTrue($message->fresh()->lu);
    }

    public function test_ouvrir_un_spam_ne_le_marque_pas_comme_lu(): void
    {
        $message = $this->message(['est_spam' => true, 'lu' => true]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.messages.show', $message))
            ->assertOk()
            ->assertSee('indésirable');

        $this->assertTrue($message->fresh()->est_spam);
    }

    public function test_tout_marquer_comme_lu(): void
    {
        $this->message(['nom' => 'A']);
        $this->message(['nom' => 'B']);

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.messages.tout-lire'))
            ->assertSessionHas('success');

        $this->assertSame(0, MessageContact::nonLus()->count());
    }

    public function test_vider_les_indesirables_ne_touche_pas_aux_vrais_messages(): void
    {
        $this->message(['nom' => 'Vrai Client']);
        $this->message(['nom' => 'Robot 1', 'est_spam' => true]);
        $this->message(['nom' => 'Robot 2', 'est_spam' => true]);

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.messages.vider-spam'))
            ->assertSessionHas('success');

        $this->assertSame(1, MessageContact::count());
        $this->assertSame('Vrai Client', MessageContact::first()->nom);
    }

    public function test_supprimer_un_message(): void
    {
        $message = $this->message();

        $this->actingAs($this->superAdmin())
            ->delete(route('admin.messages.destroy', $message))
            ->assertRedirect(route('admin.messages.index'));

        $this->assertSame(0, MessageContact::count());
    }

    public function test_le_badge_de_la_sidebar_ignore_les_spams(): void
    {
        $this->message(['nom' => 'Vrai Client']);
        $this->message(['nom' => 'Robot', 'est_spam' => true, 'lu' => false]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.instituts.index'))
            ->assertOk();

        $this->assertSame(1, MessageContact::nonLus()->count());
    }
}
