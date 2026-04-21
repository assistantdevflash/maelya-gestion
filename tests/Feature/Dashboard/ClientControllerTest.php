<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_redirige_si_non_authentifie(): void
    {
        $this->get(route('dashboard.clients.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_accessible_pour_admin(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->get(route('dashboard.clients.index'))
            ->assertOk()
            ->assertViewIs('dashboard.clients.index');
    }

    public function test_index_retourne_clients_de_l_institut(): void
    {
        $user  = $this->creerAdmin();
        $user2 = $this->creerAdmin(); // autre institut

        $this->actingAs($user);
        Client::create(['prenom' => 'Awa', 'nom' => 'Koné', 'telephone' => '111', 'actif' => true]);

        $this->actingAs($user2);
        Client::create(['prenom' => 'Autre', 'nom' => 'Client', 'telephone' => '222', 'actif' => true]);

        // Seul le client de user doit apparaître
        $this->actingAs($user)
            ->get(route('dashboard.clients.index'))
            ->assertSee('Awa')
            ->assertDontSee('Autre');
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_create_retourne_le_formulaire(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->get(route('dashboard.clients.create'))
            ->assertOk()
            ->assertViewIs('dashboard.clients.create');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_cree_un_client_valide(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->post(route('dashboard.clients.store'), [
                'prenom'    => 'Marie',
                'nom'       => 'Dupont',
                'telephone' => '0707070707',
            ])
            ->assertRedirect(route('dashboard.clients.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('clients', [
            'prenom'     => 'Marie',
            'nom'        => 'Dupont',
            'telephone'  => '0707070707',
            'institut_id'=> $user->institut_id,
        ]);
    }

    public function test_store_echoue_si_champs_requis_manquants(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->post(route('dashboard.clients.store'), [])
            ->assertSessionHasErrors(['prenom', 'nom', 'telephone']);
    }

    public function test_store_echoue_si_email_invalide(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->post(route('dashboard.clients.store'), [
                'prenom'    => 'Test',
                'nom'       => 'User',
                'telephone' => '0000',
                'email'     => 'pas-un-email',
            ])
            ->assertSessionHasErrors(['email']);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_show_affiche_le_detail_du_client(): void
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        $client = Client::create(['prenom' => 'Léa', 'nom' => 'Bah', 'telephone' => '0000', 'actif' => true]);

        $this->actingAs($user)
            ->get(route('dashboard.clients.show', $client))
            ->assertOk()
            ->assertViewIs('dashboard.clients.show')
            ->assertSee('Léa');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_update_modifie_le_client(): void
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        $client = Client::create(['prenom' => 'Awa', 'nom' => 'Koné', 'telephone' => '111', 'actif' => true]);

        $this->actingAs($user)
            ->put(route('dashboard.clients.update', $client), [
                'prenom'    => 'Awa Modifiée',
                'nom'       => 'Koné',
                'telephone' => '222',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('clients', ['prenom' => 'Awa Modifiée', 'telephone' => '222']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_supprime_le_client_soft_delete(): void
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        $client = Client::create(['prenom' => 'À Supprimer', 'nom' => 'Test', 'telephone' => '999', 'actif' => true]);

        $this->actingAs($user)
            ->delete(route('dashboard.clients.destroy', $client))
            ->assertRedirect();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }
}
