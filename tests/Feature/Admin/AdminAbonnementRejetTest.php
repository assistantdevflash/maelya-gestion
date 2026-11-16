<?php

namespace Tests\Feature\Admin;

use App\Mail\AbonnementRejete;
use App\Models\Abonnement;
use App\Models\Notif;
use App\Models\PlanAbonnement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Rejet d'une demande d'abonnement : avec notification, ou en silence.
 *
 * Une demande abandonnée en cours de paiement reste « en attente ». Le rejet
 * silencieux la clôture sans motif et sans rien envoyer au client.
 */
class AdminAbonnementRejetTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $client;
    private PlanAbonnement $plan;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->superAdmin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        $this->client = $this->creerAdmin(['role' => 'admin']);

        $this->plan = PlanAbonnement::create([
            'nom'           => 'Premium+',
            'slug'          => 'premium-plus',
            'duree_type'    => 'mensuel',
            'duree_jours'   => 30,
            'prix'          => 9900,
            'max_instituts' => 3,
            'max_employes'  => 10,
            'actif'         => true,
            'ordre'         => 2,
        ]);
    }

    private function demandeEnAttente(): Abonnement
    {
        return Abonnement::create([
            'user_id'   => $this->client->id,
            'plan_id'   => $this->plan->id,
            'montant'   => 9900,
            'periode'   => 'mensuel',
            'statut'    => 'en_attente',
        ]);
    }

    public function test_un_rejet_normal_notifie_le_client(): void
    {
        $abonnement = $this->demandeEnAttente();

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.abonnements.rejeter', $abonnement), [
                'notes_admin' => 'Paiement introuvable',
            ])
            ->assertRedirect();

        $abonnement->refresh();

        $this->assertSame('rejete', $abonnement->statut);
        $this->assertSame('Paiement introuvable', $abonnement->notes_admin);
        $this->assertSame($this->superAdmin->id, $abonnement->valide_par);

        $this->assertSame(1, Notif::where('user_id', $this->client->id)->count());
        Mail::assertSent(AbonnementRejete::class);
    }

    public function test_le_rejet_silencieux_ne_notifie_pas_le_client(): void
    {
        $abonnement = $this->demandeEnAttente();

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.abonnements.rejeter', $abonnement), [
                'silencieux' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $abonnement->refresh();

        $this->assertSame('rejete', $abonnement->statut, 'La demande est bien clôturée.');
        $this->assertSame($this->superAdmin->id, $abonnement->valide_par, 'L\'auteur du rejet reste traçable.');

        $this->assertSame(0, Notif::where('user_id', $this->client->id)->count(), 'Aucune notification.');
        Mail::assertNothingSent();
    }

    public function test_le_rejet_silencieux_ignore_le_motif_saisi(): void
    {
        $abonnement = $this->demandeEnAttente();

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.abonnements.rejeter', $abonnement), [
                'silencieux'  => 1,
                'notes_admin' => 'Motif qui ne doit pas apparaître',
            ])
            ->assertRedirect();

        $abonnement->refresh();

        // `notes_admin` est affiché au client dans son historique.
        $this->assertNull($abonnement->notes_admin);
    }

    public function test_le_rejet_silencieux_ne_touche_pas_au_plan_ni_a_la_periode(): void
    {
        $abonnement = $this->demandeEnAttente();
        $avant = $abonnement->only(['plan_id', 'montant', 'periode', 'debut_le', 'expire_le']);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.abonnements.rejeter', $abonnement), ['silencieux' => 1])
            ->assertRedirect();

        $this->assertSame($avant, $abonnement->fresh()->only(array_keys($avant)));
    }

    public function test_une_demande_deja_traitee_ne_peut_pas_etre_cloturee(): void
    {
        $abonnement = $this->demandeEnAttente();
        $abonnement->update(['statut' => 'actif']);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.abonnements.rejeter', $abonnement), ['silencieux' => 1])
            ->assertSessionHas('error');

        $this->assertSame('actif', $abonnement->fresh()->statut);
    }

    public function test_la_page_de_detail_propose_les_deux_rejets(): void
    {
        $abonnement = $this->demandeEnAttente();

        $this->actingAs($this->superAdmin)
            ->get(route('admin.abonnements.show', $abonnement))
            ->assertOk()
            ->assertSee('Rejeter')
            ->assertSee('Clôturer sans notifier')
            ->assertSee('name="silencieux"', false)
            ->assertSee('aucun e-mail');
    }
}
