<?php

namespace Tests\Feature\Dashboard;

use App\Models\Abonnement;
use App\Models\Client;
use App\Models\Institut;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Disponibilité des rendez-vous (chevauchements, horaires) et workflow
 * de confirmation.
 */
class RdvDisponibiliteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Institut $institut;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->creerAdmin();
        $user->abonnements()->update(['statut' => 'expire']);

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

        $this->user = $user->fresh();
        $this->institut = Institut::findOrFail($this->user->institut_id);

        $this->actingAs($this->user);
    }

    private function rdv(array $attrs = []): RendezVous
    {
        return RendezVous::create(array_merge([
            'client_nom'    => 'Client Test',
            'debut_le'      => now()->addDay()->setTime(10, 0),
            'duree_minutes' => 60,
            'statut'        => 'confirme',
        ], $attrs));
    }

    private function creer(string $debut, int $duree = 60, ?string $employe = null, array $extra = [])
    {
        return $this->post(route('dashboard.rdv.store'), array_merge([
            'client_nom'    => 'Nouveau Client',
            'debut_date'    => Carbon::parse($debut)->format('Y-m-d'),
            'debut_heure'   => Carbon::parse($debut)->format('H:i'),
            'duree_minutes' => $duree,
            'statut'        => 'confirme',
            'employe_id'    => $employe,
        ], $extra));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Chevauchements
    // ─────────────────────────────────────────────────────────────────────

    public function test_un_creneau_deja_pris_est_refuse(): void
    {
        $this->rdv(['debut_le' => now()->addDay()->setTime(10, 0)]);

        $this->creer(now()->addDay()->setTime(10, 30)->toDateTimeString())
            ->assertSessionHasErrors('debut_le');

        $this->assertSame(1, RendezVous::count());
    }

    public function test_un_creneau_libre_est_accepte(): void
    {
        $this->rdv(['debut_le' => now()->addDay()->setTime(10, 0)]);

        $this->creer(now()->addDay()->setTime(12, 0)->toDateTimeString())
            ->assertSessionHasNoErrors();

        $this->assertSame(2, RendezVous::count());
    }

    public function test_un_autre_employe_peut_prendre_le_meme_creneau(): void
    {
        $employe = User::factory()->create([
            'institut_id' => $this->institut->id,
            'role'        => 'employe',
            'actif'       => true,
        ]);

        $this->rdv([
            'debut_le'   => now()->addDay()->setTime(10, 0),
            'employe_id' => $this->user->id,
        ]);

        $this->creer(now()->addDay()->setTime(10, 0)->toDateTimeString(), 60, $employe->id)
            ->assertSessionHasNoErrors();

        $this->assertSame(2, RendezVous::count());
    }

    public function test_un_rdv_annule_ne_bloque_pas_le_creneau(): void
    {
        $this->rdv([
            'debut_le' => now()->addDay()->setTime(10, 0),
            'statut'   => 'annule',
        ]);

        $this->creer(now()->addDay()->setTime(10, 0)->toDateTimeString())
            ->assertSessionHasNoErrors();

        $this->assertSame(2, RendezVous::count());
    }

    public function test_le_glisser_deposer_refuse_un_chevauchement(): void
    {
        $existant = $this->rdv(['debut_le' => now()->addDay()->setTime(10, 0)]);
        $mobile   = $this->rdv([
            'client_nom' => 'Mobile',
            'debut_le'   => now()->addDay()->setTime(14, 0),
        ]);

        $this->postJson(route('dashboard.rdv.move', $mobile), [
            'debut_le' => now()->addDay()->setTime(10, 30)->toDateTimeString(),
        ])->assertStatus(409);

        $this->assertSame(
            now()->addDay()->setTime(14, 0)->toDateTimeString(),
            $mobile->fresh()->debut_le->toDateTimeString()
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // Horaires d'ouverture
    // ─────────────────────────────────────────────────────────────────────

    public function test_un_rdv_hors_horaires_est_refuse(): void
    {
        $this->institut->update([
            'heure_ouverture' => '08:00',
            'heure_fermeture' => '18:00',
            'jours_ouverture' => [1, 2, 3, 4, 5, 6, 7],
        ]);

        $this->creer(now()->addDay()->setTime(21, 0)->toDateTimeString())
            ->assertSessionHasErrors('debut_le');

        $this->creer(now()->addDay()->setTime(9, 0)->toDateTimeString())
            ->assertSessionHasNoErrors();
    }

    public function test_un_jour_de_fermeture_est_refuse(): void
    {
        // Uniquement le lundi
        $this->institut->update([
            'heure_ouverture' => '08:00',
            'heure_fermeture' => '18:00',
            'jours_ouverture' => [1],
        ]);

        $mardi = now()->startOfWeek()->addDay()->setTime(10, 0);

        $this->creer($mardi->toDateTimeString())->assertSessionHasErrors('debut_le');
    }

    public function test_sans_horaires_aucune_restriction(): void
    {
        $this->institut->update([
            'heure_ouverture' => null,
            'heure_fermeture' => null,
            'jours_ouverture' => null,
        ]);

        $this->creer(now()->addDay()->setTime(23, 30)->toDateTimeString())
            ->assertSessionHasNoErrors();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Confirmation + fiche client
    // ─────────────────────────────────────────────────────────────────────

    public function test_confirmer_change_le_statut_et_previent_le_client(): void
    {
        Mail::fake();

        $rdv = $this->rdv([
            'statut'         => 'en_attente',
            'client_email'   => 'client@example.ci',
            'client_telephone' => '0701020304',
        ]);

        $this->post(route('dashboard.rdv.confirmer', $rdv))
            ->assertSessionHasNoErrors();

        $this->assertSame('confirme', $rdv->fresh()->statut);
        Mail::assertSent(\App\Mail\RdvConfirmeClient::class);
    }

    public function test_confirmer_cree_la_fiche_client_d_un_rdv_vitrine(): void
    {
        Mail::fake();

        $rdv = $this->rdv([
            'statut'           => 'en_attente',
            'source'           => 'vitrine',
            'client_nom'       => 'Awa Kone',
            'client_telephone' => '0701020304',
            'client_email'     => 'awa@example.ci',
        ]);

        $this->assertNull($rdv->client_id);

        $this->post(route('dashboard.rdv.confirmer', $rdv))->assertSessionHasNoErrors();

        $rdv->refresh();
        $this->assertNotNull($rdv->client_id);

        $client = Client::find($rdv->client_id);
        $this->assertSame('Awa', $client->prenom);
        $this->assertSame('Kone', $client->nom);
        $this->assertSame('0701020304', $client->telephone);
    }

    public function test_confirmer_ne_duplique_pas_la_fiche_par_telephone(): void
    {
        Mail::fake();

        // Téléphone saisi avec des espaces : doit quand même être reconnu
        $existant = Client::create([
            'institut_id' => $this->institut->id,
            'type_client' => 'personne_physique',
            'prenom'      => 'Awa',
            'nom'         => 'Kone',
            'telephone'   => '0701020304',
        ]);

        $rdv = $this->rdv([
            'statut'           => 'en_attente',
            'source'           => 'vitrine',
            'client_nom'       => 'A. Kone',
            'client_telephone' => '07 01 02 03 04',
        ]);

        $this->post(route('dashboard.rdv.confirmer', $rdv))->assertSessionHasNoErrors();

        $this->assertSame(1, Client::count(), 'Aucune fiche en double ne doit être créée.');
        $this->assertSame($existant->id, $rdv->fresh()->client_id);
    }

    public function test_confirmer_rattache_la_fiche_existante_par_email(): void
    {
        Mail::fake();

        $existant = Client::create([
            'institut_id' => $this->institut->id,
            'type_client' => 'personne_physique',
            'prenom'      => 'Awa',
            'nom'         => 'Kone',
            'telephone'   => '0100000000',
            'email'       => 'awa@example.ci',
        ]);

        $rdv = $this->rdv([
            'statut'           => 'en_attente',
            'source'           => 'vitrine',
            'client_telephone' => '0799999999',
            'client_email'     => 'AWA@example.ci',
        ]);

        $this->post(route('dashboard.rdv.confirmer', $rdv))->assertSessionHasNoErrors();

        $this->assertSame(1, Client::count());
        $this->assertSame($existant->id, $rdv->fresh()->client_id);
    }

    public function test_un_rdv_manuel_ne_cree_pas_de_fiche_client(): void
    {
        Mail::fake();

        $rdv = $this->rdv([
            'statut'           => 'en_attente',
            'source'           => 'manuel',
            'client_telephone' => '0701020304',
        ]);

        $this->post(route('dashboard.rdv.confirmer', $rdv))->assertSessionHasNoErrors();

        $this->assertSame(0, Client::count());
    }

    public function test_un_employe_ne_peut_pas_confirmer_le_rdv_d_un_autre(): void
    {
        $employe = User::factory()->create([
            'institut_id' => $this->institut->id,
            'role'        => 'employe',
            'actif'       => true,
        ]);

        $rdv = $this->rdv(['statut' => 'en_attente', 'employe_id' => $this->user->id]);

        $this->actingAs($employe)
            ->post(route('dashboard.rdv.confirmer', $rdv))
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Recherche et filtre employé
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_recherche_filtre_par_nom_ou_telephone(): void
    {
        $this->rdv(['client_nom' => 'Fatou Diallo', 'client_telephone' => '0700000001']);
        $this->rdv(['client_nom' => 'Koffi Yao', 'client_telephone' => '0500000002']);

        $noms = collect(
            $this->get(route('dashboard.rdv.index', ['filtre' => 'tous', 'q' => 'Fatou']))
                ->assertOk()->viewData('rdvs')
        )->flatten()->pluck('client_nom');

        $this->assertContains('Fatou Diallo', $noms);
        $this->assertNotContains('Koffi Yao', $noms);

        $nomsTel = collect(
            $this->get(route('dashboard.rdv.index', ['filtre' => 'tous', 'q' => '0500000002']))
                ->assertOk()->viewData('rdvs')
        )->flatten()->pluck('client_nom');

        $this->assertContains('Koffi Yao', $nomsTel);
    }

    public function test_le_filtre_employe_fonctionne(): void
    {
        $employe = User::factory()->create([
            'institut_id' => $this->institut->id,
            'role'        => 'employe',
            'actif'       => true,
        ]);

        $this->rdv(['client_nom' => 'Chez Awa', 'employe_id' => $this->user->id]);
        $this->rdv(['client_nom' => 'Chez Koffi', 'employe_id' => $employe->id]);

        $noms = collect(
            $this->get(route('dashboard.rdv.index', ['filtre' => 'tous', 'employe' => $employe->id]))
                ->assertOk()->viewData('rdvs')
        )->flatten()->pluck('client_nom');

        $this->assertContains('Chez Koffi', $noms);
        $this->assertNotContains('Chez Awa', $noms);

        // Garde-fou : le sélecteur doit afficher « Prénom Nom_famille ».
        // ⚠️ SQLite tolère un nom de colonne inexistant (traité comme une
        // chaîne), donc la requête ne casse pas en test alors qu'elle explose
        // en MySQL. C'est le rendu qui trahit l'erreur.
        $this->get(route('dashboard.rdv.index', ['filtre' => 'tous']))
            ->assertOk()
            ->assertSee($employe->prenom . ' ' . $employe->nom_famille, false);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Présentation des actions (action principale contextuelle)
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_fiche_rdv_met_en_avant_l_action_principale(): void
    {
        // RDV en attente → l'action principale est la confirmation
        $enAttente = $this->rdv(['statut' => 'en_attente']);

        $this->get(route('dashboard.rdv.show', $enAttente))
            ->assertOk()
            ->assertSee('Confirmer le rendez-vous')
            ->assertDontSee('Encaisser ce rendez-vous');

        // RDV confirmé avec prestations → l'action principale est l'encaissement
        $categorie = \App\Models\CategoriePrestation::create([
            'institut_id' => $this->institut->id,
            'nom'         => 'Catégorie test',
        ]);

        $prestation = \App\Models\Prestation::create([
            'institut_id'  => $this->institut->id,
            'categorie_id' => $categorie->id,
            'nom'          => 'Soin test',
            'prix'        => 5000,
            'duree'       => 30,
            'actif'       => true,
        ]);

        $confirme = $this->rdv(['statut' => 'confirme', 'client_nom' => 'Confirme Test']);
        $confirme->prestations()->attach($prestation->id);

        $this->get(route('dashboard.rdv.show', $confirme->fresh()))
            ->assertOk()
            ->assertSee('Encaisser ce rendez-vous')
            ->assertDontSee('Confirmer le rendez-vous');
    }
}
