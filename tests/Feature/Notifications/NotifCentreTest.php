<?php

namespace Tests\Feature\Notifications;

use App\Models\Notif;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du centre de notifications in-app :
 * - NotificationService (notifyUser / notifyAdmins)
 * - Modèle Notif
 * - Endpoint POST /notifications/tout-lire
 */
class NotifCentreTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // NotificationService::notifyUser()
    // =========================================================================

    public function test_notify_user_cree_un_enregistrement_notif(): void
    {
        $user = $this->creerAdmin();

        NotificationService::notifyUser($user, 'abonnement_valide', 'Abonnement validé', 'Détail.', '/abonnement');

        $this->assertDatabaseHas('notifs', [
            'user_id' => $user->id,
            'type'    => 'abonnement_valide',
            'titre'   => 'Abonnement validé',
            'corps'   => 'Détail.',
            'url'     => '/abonnement',
            'lu'      => false,
        ]);
    }

    public function test_notify_user_url_par_defaut_est_slash(): void
    {
        $user = $this->creerAdmin();

        NotificationService::notifyUser($user, 'bienvenue', 'Bienvenue', 'Corps.');

        $this->assertDatabaseHas('notifs', [
            'user_id' => $user->id,
            'url'     => '/',
        ]);
    }

    public function test_notify_user_cree_plusieurs_notifs_independantes(): void
    {
        $user = $this->creerAdmin();

        NotificationService::notifyUser($user, 'rdv_confirme', 'RDV 1', 'Corps 1.');
        NotificationService::notifyUser($user, 'rdv_rappel',   'RDV 2', 'Corps 2.');

        $this->assertSame(2, Notif::where('user_id', $user->id)->count());
    }

    // =========================================================================
    // NotificationService::notifyAdmins()
    // =========================================================================

    public function test_notify_admins_cree_une_notif_par_super_admin(): void
    {
        $a1 = User::factory()->create(['role' => 'super_admin', 'actif' => true]);
        $a2 = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        NotificationService::notifyAdmins('nouvelle_demande', 'Titre', 'Corps.', '/admin');

        $this->assertDatabaseHas('notifs', ['user_id' => $a1->id, 'type' => 'nouvelle_demande']);
        $this->assertDatabaseHas('notifs', ['user_id' => $a2->id, 'type' => 'nouvelle_demande']);
        $this->assertSame(2, Notif::where('type', 'nouvelle_demande')->count());
    }

    public function test_notify_admins_ne_cree_rien_sans_super_admin(): void
    {
        // Aucun super_admin en base
        NotificationService::notifyAdmins('nouvelle_demande', 'Titre', 'Corps.');

        $this->assertDatabaseCount('notifs', 0);
    }

    public function test_notify_admins_n_affecte_pas_les_non_admins(): void
    {
        $admin  = User::factory()->create(['role' => 'super_admin', 'actif' => true]);
        $etabl  = $this->creerAdmin(); // role = admin (établissement)

        NotificationService::notifyAdmins('nouvelle_demande', 'Titre', 'Corps.');

        $this->assertSame(1, Notif::where('type', 'nouvelle_demande')->count());
        $this->assertDatabaseMissing('notifs', ['user_id' => $etabl->id]);
    }

    // =========================================================================
    // Modèle Notif — valeurs par défaut
    // =========================================================================

    public function test_notif_est_non_lue_par_defaut(): void
    {
        $user = $this->creerAdmin();

        $notif = Notif::create([
            'user_id' => $user->id,
            'type'    => 'test',
            'titre'   => 'Titre test',
        ]);
        $notif->refresh();

        $this->assertFalse($notif->lu);
    }

    public function test_notif_corps_et_url_ont_des_valeurs_par_defaut(): void
    {
        $user = $this->creerAdmin();

        $notif = Notif::create([
            'user_id' => $user->id,
            'type'    => 'test',
            'titre'   => 'Titre',
        ]);
        $notif->refresh();

        $this->assertSame('', $notif->corps);
        $this->assertSame('/', $notif->url);
    }

    public function test_notif_created_at_est_caste_en_datetime(): void
    {
        $user  = $this->creerAdmin();
        $notif = Notif::create(['user_id' => $user->id, 'type' => 'test', 'titre' => 'T']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $notif->created_at);
    }

    // =========================================================================
    // POST /notifications/tout-lire
    // =========================================================================

    public function test_tout_lire_redirige_si_non_authentifie(): void
    {
        $this->postJson(route('notifications.tout-lire'))
            ->assertUnauthorized();
    }

    public function test_tout_lire_retourne_ok_true(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->postJson(route('notifications.tout-lire'))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_tout_lire_marque_toutes_les_notifs_de_lutilisateur_comme_lues(): void
    {
        $user = $this->creerAdmin();

        Notif::create(['user_id' => $user->id, 'type' => 'a', 'titre' => 'A', 'lu' => false]);
        Notif::create(['user_id' => $user->id, 'type' => 'b', 'titre' => 'B', 'lu' => false]);

        $this->actingAs($user)
            ->postJson(route('notifications.tout-lire'))
            ->assertOk();

        $this->assertSame(0, Notif::where('user_id', $user->id)->where('lu', false)->count());
        $this->assertSame(2, Notif::where('user_id', $user->id)->where('lu', true)->count());
    }

    public function test_tout_lire_ne_touche_pas_aux_notifs_des_autres_utilisateurs(): void
    {
        $user1 = $this->creerAdmin(['email' => 'user1@test.com']);
        $user2 = $this->creerAdmin(['email' => 'user2@test.com']);

        Notif::create(['user_id' => $user1->id, 'type' => 'a', 'titre' => 'A', 'lu' => false]);
        Notif::create(['user_id' => $user2->id, 'type' => 'b', 'titre' => 'B', 'lu' => false]);

        $this->actingAs($user1)
            ->postJson(route('notifications.tout-lire'));

        // user2's notif must remain unread
        $this->assertDatabaseHas('notifs', ['user_id' => $user2->id, 'lu' => false]);
    }

    public function test_tout_lire_fonctionne_sans_notifs_existantes(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->postJson(route('notifications.tout-lire'))
            ->assertOk()
            ->assertJson(['ok' => true]);
    }
}
