<?php

namespace Tests\Feature\Abonnement;

use App\Models\Abonnement;
use App\Models\PlanAbonnement;
use App\Models\User;
use App\Services\AbonnementPeriodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Périodes d'abonnement au moment du paiement.
 *
 * Payer avant l'échéance ne doit pas effacer les jours déjà payés : la
 * nouvelle période enchaîne sur l'ancienne. Une mise à niveau, elle, démarre
 * immédiatement.
 */
class RenouvellementAbonnementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $superAdmin;
    private PlanAbonnement $premium;
    private PlanAbonnement $premiumPlus;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->owner      = $this->creerAdmin();
        $this->superAdmin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        // `creerAdmin()` pose un abonnement d'essai actif (30 jours) pour
        // passer le middleware. On repart d'un compte vierge : ce sont les
        // périodes du test qui doivent décider, pas cet artefact.
        Abonnement::withoutGlobalScopes()->where('user_id', $this->owner->id)->delete();

        $this->premium = $this->creerPlan([
            'nom'    => 'Premium',
            'slug'   => 'premium',
            'duree_type' => 'mensuel',
            'ordre'  => 1,
            'prix'   => 4900,
        ]);

        $this->premiumPlus = $this->creerPlan([
            'nom'    => 'Premium+',
            'slug'   => 'premium-plus',
            'duree_type' => 'mensuel',
            'ordre'  => 2,
            'prix'   => 9900,
        ]);
    }

    /** Abonnement en cours, échéance dans `$joursRestants` jours. */
    private function abonnementEnCours(PlanAbonnement $plan, int $joursRestants): Abonnement
    {
        return Abonnement::create([
            'user_id'   => $this->owner->id,
            'plan_id'   => $plan->id,
            'montant'   => $plan->prix,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->subDays(30 - $joursRestants)->toDateString(),
            'expire_le' => now()->addDays($joursRestants)->toDateString(),
        ]);
    }

    private function demandeEnAttente(PlanAbonnement $plan): Abonnement
    {
        return Abonnement::create([
            'user_id' => $this->owner->id,
            'plan_id' => $plan->id,
            'montant' => $plan->prix,
            'periode' => 'mensuel',
            'statut'  => 'en_attente',
        ]);
    }

    private function valider(Abonnement $abonnement)
    {
        return $this->actingAs($this->superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement))
            ->assertRedirect();
    }

    public function test_un_renouvellement_anticipe_enchaines_sur_l_echeance(): void
    {
        $ancien = $this->abonnementEnCours($this->premiumPlus, 3); // échéance J+3
        $nouveau = $this->demandeEnAttente($this->premiumPlus);

        $this->valider($nouveau);

        $nouveau->refresh();

        $this->assertSame('actif', $nouveau->statut);
        $this->assertSame(
            now()->addDays(4)->toDateString(),
            $nouveau->debut_le->toDateString(),
            'La nouvelle période commence le lendemain de l\'échéance.'
        );
        $this->assertSame(
            now()->addDays(4)->addDays(30)->toDateString(),
            $nouveau->expire_le->toDateString(),
            'Trente jours pleins à partir du nouveau départ.'
        );
        $this->assertSame(3, $nouveau->metadata['jours_reportes']);
        $this->assertFalse($nouveau->metadata['mise_a_niveau']);
    }

    public function test_l_ancien_abonnement_reste_actif_jusqu_a_sa_vraie_fin(): void
    {
        $ancien  = $this->abonnementEnCours($this->premiumPlus, 3);
        $nouveau = $this->demandeEnAttente($this->premiumPlus);

        $this->valider($nouveau);

        $this->assertSame('actif', $ancien->fresh()->statut, 'Aucun trou d\'accès pendant les 3 jours restants.');
    }

    public function test_une_retrogradation_anticipee_est_aussi_reportee(): void
    {
        $this->abonnementEnCours($this->premiumPlus, 5);
        $nouveau = $this->demandeEnAttente($this->premium);

        $this->valider($nouveau);

        $nouveau->refresh();

        $this->assertSame(now()->addDays(6)->toDateString(), $nouveau->debut_le->toDateString());
        $this->assertSame(5, $nouveau->metadata['jours_reportes']);
    }

    public function test_une_mise_a_niveau_demarre_immediatement(): void
    {
        $ancien  = $this->abonnementEnCours($this->premium, 10);
        $nouveau = $this->demandeEnAttente($this->premiumPlus);

        $this->valider($nouveau);

        $nouveau->refresh();

        $this->assertSame(now()->toDateString(), $nouveau->debut_le->toDateString());
        $this->assertSame(now()->addDays(30)->toDateString(), $nouveau->expire_le->toDateString());
        $this->assertTrue($nouveau->metadata['mise_a_niveau']);
        $this->assertSame(0, $nouveau->metadata['jours_reportes']);
        $this->assertSame('expire', $ancien->fresh()->statut, 'L\'ancien plan est remplacé tout de suite.');
    }

    public function test_un_renouvellement_apres_echeance_demarre_le_jour_meme(): void
    {
        Abonnement::create([
            'user_id'   => $this->owner->id,
            'plan_id'   => $this->premiumPlus->id,
            'montant'   => 9900,
            'periode'   => 'mensuel',
            'statut'    => 'expire',
            'debut_le'  => now()->subDays(40)->toDateString(),
            'expire_le' => now()->subDays(10)->toDateString(),
        ]);

        $nouveau = $this->demandeEnAttente($this->premiumPlus);

        $this->valider($nouveau);

        $nouveau->refresh();

        $this->assertSame(now()->toDateString(), $nouveau->debut_le->toDateString());
        $this->assertSame(0, $nouveau->metadata['jours_reportes']);
    }

    public function test_l_abonnement_actif_ignore_une_periode_payee_d_avance(): void
    {
        $ancien = $this->abonnementEnCours($this->premiumPlus, 3);

        $futur = Abonnement::create([
            'user_id'   => $this->owner->id,
            'plan_id'   => $this->premiumPlus->id,
            'montant'   => 9900,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->addDays(4)->toDateString(),
            'expire_le' => now()->addDays(34)->toDateString(),
        ]);

        $this->assertSame($ancien->id, $this->owner->fresh()->abonnementActif?->id);

        // Le jour du démarrage, la nouvelle période prend la main.
        $this->travel(4)->days();
        $this->assertSame($futur->id, $this->owner->fresh()->abonnementActif?->id);
    }

    public function test_le_service_calcule_sans_ecrire_en_base(): void
    {
        $this->abonnementEnCours($this->premiumPlus, 7);
        $nouveau = $this->demandeEnAttente($this->premiumPlus);

        $periode = app(AbonnementPeriodeService::class)->calculer($nouveau, $this->premiumPlus, 30);

        $this->assertSame(now()->addDays(8)->toDateString(), $periode['debut']->toDateString());
        $this->assertSame(now()->addDays(38)->toDateString(), $periode['expire']->toDateString());
        $this->assertSame(7, $periode['jours_reportes']);
        $this->assertSame('en_attente', $nouveau->fresh()->statut, 'Le calcul ne modifie rien.');
    }
}
