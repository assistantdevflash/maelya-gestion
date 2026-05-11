<?php

namespace Tests\Feature\Dashboard;

use App\Models\Abonnement;
use App\Models\Client;
use App\Models\Prestation;
use App\Models\RendezVous;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RdvControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Crée un admin disposant de la fonctionnalité 'rdv' (plan premium).
     */
    private function creerAdminAvecRdv(): \App\Models\User
    {
        $user = $this->creerAdmin();

        // Expire le plan actuel (slug aléatoire sans rdv)
        $user->abonnements()->update(['statut' => 'expire']);

        // Crée un plan "premium" reconnu par plans-features.php
        $plan = $this->creerPlan(['slug' => 'premium', 'nom' => 'Premium']);

        Abonnement::create([
            'user_id'   => $user->id,
            'plan_id'   => $plan->id,
            'montant'   => 10000,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now(),
            'expire_le' => now()->addDays(30),
        ]);

        return $user->fresh();
    }

    /**
     * Crée un RendezVous pour l'institut de l'utilisateur connecté.
     */
    private function creerRdv(\App\Models\User $user, array $attrs = []): RendezVous
    {
        $this->actingAs($user);
        return RendezVous::create(array_merge([
            'client_nom'    => 'Awa Test',
            'debut_le'      => now()->addDay()->setHour(10)->setMinute(0)->setSecond(0),
            'duree_minutes' => 30,
            'statut'        => 'en_attente',
        ], $attrs));
    }

    // ── Auth & Accès ──────────────────────────────────────────────────────────

    public function test_index_redirige_si_non_authentifie(): void
    {
        $this->get(route('dashboard.rdv.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_accessible_pour_admin_avec_feature_rdv(): void
    {
        $user = $this->creerAdminAvecRdv();

        $this->actingAs($user)
            ->get(route('dashboard.rdv.index'))
            ->assertOk()
            ->assertViewIs('dashboard.rdv.index');
    }

    public function test_index_bloque_sans_feature_rdv(): void
    {
        // creerAdmin() crée un plan slug aléatoire sans 'rdv'
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->get(route('dashboard.rdv.index'))
            ->assertRedirect(); // redirigé vers upgrade
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_create_retourne_formulaire(): void
    {
        $user = $this->creerAdminAvecRdv();

        $this->actingAs($user)
            ->get(route('dashboard.rdv.create'))
            ->assertOk()
            ->assertViewIs('dashboard.rdv.create');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_cree_rdv_sans_prestation(): void
    {
        Mail::fake();
        $user = $this->creerAdminAvecRdv();

        $this->actingAs($user)
            ->post(route('dashboard.rdv.store'), [
                'client_nom'    => 'Fatou Koné',
                'debut_date'    => now()->addDay()->format('Y-m-d'),
                'debut_heure'   => '10:00',
                'duree_minutes' => 45,
                'statut'        => 'en_attente',
            ])
            ->assertRedirect(route('dashboard.rdv.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rendez_vous', [
            'client_nom'    => 'Fatou Koné',
            'duree_minutes' => 45,
            'statut'        => 'en_attente',
            'institut_id'   => $user->institut_id,
        ]);
    }

    public function test_store_cree_rdv_avec_prestations(): void
    {
        Mail::fake();
        $user = $this->creerAdminAvecRdv();
        $this->actingAs($user);

        $categorie = \App\Models\CategoriePrestation::create(['nom' => 'Soins']);

        $prestation = Prestation::create([
            'nom'         => 'Coupe femme',
            'prix'        => 5000,
            'actif'       => true,
            'categorie_id'=> $categorie->id,
        ]);

        $this->actingAs($user)
            ->post(route('dashboard.rdv.store'), [
                'client_nom'    => 'Marie Diallo',
                'debut_date'    => now()->addDay()->format('Y-m-d'),
                'debut_heure'   => '14:00',
                'duree_minutes' => 60,
                'statut'        => 'confirme',
                'prestations'   => [$prestation->id],
            ])
            ->assertRedirect(route('dashboard.rdv.index'));

        $rdv = RendezVous::where('client_nom', 'Marie Diallo')->firstOrFail();
        $this->assertDatabaseHas('rendez_vous_prestations', [
            'rendez_vous_id' => $rdv->id,
            'prestation_id'  => $prestation->id,
        ]);
    }

    public function test_store_rejette_date_passee(): void
    {
        Mail::fake();
        $user = $this->creerAdminAvecRdv();

        $this->actingAs($user)
            ->post(route('dashboard.rdv.store'), [
                'client_nom'    => 'Test',
                'debut_date'    => now()->subDay()->format('Y-m-d'),
                'debut_heure'   => '10:00',
                'duree_minutes' => 30,
                'statut'        => 'en_attente',
            ])
            ->assertSessionHasErrors(['debut_le']);
    }

    public function test_store_rejette_champs_requis_manquants(): void
    {
        Mail::fake();
        $user = $this->creerAdminAvecRdv();

        $this->actingAs($user)
            ->post(route('dashboard.rdv.store'), [])
            ->assertSessionHasErrors(['client_nom']);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_show_affiche_le_rdv(): void
    {
        $user = $this->creerAdminAvecRdv();
        $rdv  = $this->creerRdv($user);

        $this->actingAs($user)
            ->get(route('dashboard.rdv.show', $rdv))
            ->assertOk()
            ->assertViewIs('dashboard.rdv.show')
            ->assertSee('Awa Test');
    }

    public function test_show_interdit_pour_autre_institut(): void
    {
        $user1 = $this->creerAdminAvecRdv();
        $rdv   = $this->creerRdv($user1);

        $user2 = $this->creerAdminAvecRdv();

        // InstitutScope filtre par institut → 404 pour un RDV d'un autre institut
        $this->actingAs($user2)
            ->get(route('dashboard.rdv.show', $rdv))
            ->assertNotFound();
    }

    // ── Edit / Update ─────────────────────────────────────────────────────────

    public function test_edit_retourne_formulaire_prefill(): void
    {
        $user = $this->creerAdminAvecRdv();
        $rdv  = $this->creerRdv($user);

        $this->actingAs($user)
            ->get(route('dashboard.rdv.edit', $rdv))
            ->assertOk()
            ->assertViewIs('dashboard.rdv.edit')
            ->assertSee('Awa Test');
    }

    public function test_update_modifie_le_rdv(): void
    {
        Mail::fake();
        $user = $this->creerAdminAvecRdv();
        $rdv  = $this->creerRdv($user);

        $this->actingAs($user)
            ->patch(route('dashboard.rdv.update', $rdv), [
                'client_nom'    => 'Awa Test Modifié',
                'debut_date'    => now()->addDays(2)->format('Y-m-d'),
                'debut_heure'   => '11:00',
                'duree_minutes' => 60,
                'statut'        => 'confirme',
            ])
            ->assertRedirect(route('dashboard.rdv.show', $rdv))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rendez_vous', [
            'id'            => $rdv->id,
            'client_nom'    => 'Awa Test Modifié',
            'statut'        => 'confirme',
            'duree_minutes' => 60,
        ]);
    }

    // ── Annuler ───────────────────────────────────────────────────────────────

    public function test_annuler_change_statut_en_annule(): void
    {
        Mail::fake();
        $user = $this->creerAdminAvecRdv();
        $rdv  = $this->creerRdv($user, ['client_email' => null]);

        $this->actingAs($user)
            ->post(route('dashboard.rdv.annuler', $rdv))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rendez_vous', [
            'id'     => $rdv->id,
            'statut' => 'annule',
        ]);
    }

    public function test_annuler_envoie_mail_si_client_email_present(): void
    {
        Mail::fake();
        $user = $this->creerAdminAvecRdv();
        $rdv  = $this->creerRdv($user, ['client_email' => 'awa@test.com']);

        $this->actingAs($user)
            ->post(route('dashboard.rdv.annuler', $rdv));

        Mail::assertSent(\App\Mail\RdvAnnuleClient::class, fn ($mail) => $mail->hasTo('awa@test.com'));
    }

    // ── Terminer ──────────────────────────────────────────────────────────────

    public function test_terminer_change_statut_en_termine(): void
    {
        $user = $this->creerAdminAvecRdv();
        $rdv  = $this->creerRdv($user);

        $this->actingAs($user)
            ->post(route('dashboard.rdv.terminer', $rdv))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rendez_vous', [
            'id'     => $rdv->id,
            'statut' => 'termine',
        ]);
    }

    // ── Fiche client ──────────────────────────────────────────────────────────

    public function test_client_show_inclut_rdv_a_venir_et_passes(): void
    {
        $user = $this->creerAdminAvecRdv();
        $this->actingAs($user);

        $client = Client::create([
            'prenom'    => 'Chloé',
            'nom'       => 'Martin',
            'telephone' => '0600000000',
            'actif'     => true,
        ]);

        // RDV à venir
        RendezVous::create([
            'client_id'     => $client->id,
            'client_nom'    => 'Chloé Martin',
            'debut_le'      => now()->addDay()->setHour(14)->setMinute(0)->setSecond(0),
            'duree_minutes' => 30,
            'statut'        => 'confirme',
        ]);

        // RDV passé
        RendezVous::create([
            'client_id'     => $client->id,
            'client_nom'    => 'Chloé Martin',
            'debut_le'      => now()->subDay()->setHour(10)->setMinute(0)->setSecond(0),
            'duree_minutes' => 45,
            'statut'        => 'termine',
        ]);

        $response = $this->actingAs($user)
            ->get(route('dashboard.clients.show', $client));

        $response->assertOk();
        $response->assertViewHas('rdvAVenir', fn ($c) => $c->count() === 1);
        $response->assertViewHas('rdvPasses',  fn ($c) => $c->count() === 1);
    }

    // ── Commande rappels ──────────────────────────────────────────────────────

    public function test_rdv_rappels_command_marque_rappel_envoye(): void
    {
        Mail::fake();

        $user = $this->creerAdminAvecRdv();
        $this->actingAs($user);

        // RDV demain
        $rdv = RendezVous::create([
            'client_nom'    => 'Béatrice Test',
            'client_email'  => 'beatrice@test.com',
            'debut_le'      => now()->addDay()->setHour(9)->setMinute(0)->setSecond(0),
            'duree_minutes' => 30,
            'statut'        => 'confirme',
            'rappel_envoye' => false,
        ]);

        $this->artisan('rdv:rappels')->assertSuccessful();

        $this->assertDatabaseHas('rendez_vous', [
            'id'            => $rdv->id,
            'rappel_envoye' => true,
        ]);
    }

    public function test_rdv_rappels_command_ne_renvoie_pas_rappel_deja_envoye(): void
    {
        Mail::fake();

        $user = $this->creerAdminAvecRdv();
        $this->actingAs($user);

        RendezVous::create([
            'client_nom'    => 'Test déjà rappelé',
            'client_email'  => 'deja@test.com',
            'debut_le'      => now()->addDay()->setHour(9)->setMinute(0)->setSecond(0),
            'duree_minutes' => 30,
            'statut'        => 'confirme',
            'rappel_envoye' => true,
        ]);

        $this->artisan('rdv:rappels')->assertSuccessful();

        Mail::assertNothingSent();
    }
}
