<?php

namespace Tests\Feature\Dashboard;

use App\Models\Abonnement;
use App\Models\RendezVous;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filtres et compteurs de la liste des rendez-vous.
 *
 * Ces cas n'étaient pas couverts : une régression sur les filtres se traduisait
 * par une liste vide alors que les compteurs annonçaient des RDV.
 */
class RdvFiltresTest extends TestCase
{
    use RefreshDatabase;

    protected \App\Models\User $user;

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
        $this->actingAs($this->user);
    }

    private function rdv(string $nom, \Carbon\Carbon $debut, string $statut = 'en_attente'): RendezVous
    {
        return RendezVous::create([
            'client_nom'    => $nom,
            'debut_le'      => $debut,
            'duree_minutes' => 30,
            'statut'        => $statut,
        ]);
    }

    public function test_le_filtre_tous_affiche_les_rdv_de_toutes_les_periodes(): void
    {
        $this->rdv('Aujourdhui Test', now()->setTime(10, 0));
        $this->rdv('Futur Test', now()->addMonths(3));
        $this->rdv('Passe Test', now()->subMonths(6));

        $this->get(route('dashboard.rdv.index', ['filtre' => 'tous']))
            ->assertOk()
            ->assertSee('Aujourdhui Test')
            ->assertSee('Futur Test')
            ->assertSee('Passe Test');
    }

    public function test_le_filtre_aujourdhui_ne_montre_que_le_jour_meme(): void
    {
        $this->rdv('Aujourdhui Test', now()->setTime(10, 0));
        $this->rdv('Futur Test', now()->addMonths(3));

        $reponse = $this->get(route('dashboard.rdv.index', ['filtre' => 'today']))->assertOk();

        // On inspecte les données de la vue : le bandeau « prochain RDV » cite
        // légitimement un RDV hors période.
        $noms = collect($reponse->viewData('rdvs'))->flatten()->pluck('client_nom');

        $this->assertContains('Aujourdhui Test', $noms);
        $this->assertNotContains('Futur Test', $noms);
    }

    public function test_le_filtre_mois_ne_montre_que_le_mois_courant(): void
    {
        $this->rdv('Ce mois Test', now()->setTime(10, 0));
        $this->rdv('Mois suivant Test', now()->addMonths(2));

        $reponse = $this->get(route('dashboard.rdv.index', ['filtre' => 'mois']))->assertOk();

        // On inspecte les données de la vue : le bandeau « prochain RDV » peut
        // légitimement citer un RDV hors période.
        $noms = collect($reponse->viewData('rdvs'))->flatten()->pluck('client_nom');

        $this->assertContains('Ce mois Test', $noms);
        $this->assertNotContains('Mois suivant Test', $noms);
    }

    public function test_les_compteurs_refletent_les_rdv_reels(): void
    {
        $this->rdv('Un', now()->setTime(10, 0));
        $this->rdv('Deux', now()->addMonths(3));
        $this->rdv('Trois', now()->subMonths(6));

        $reponse = $this->get(route('dashboard.rdv.index', ['filtre' => 'tous']))->assertOk();

        $counts = $reponse->viewData('counts');

        $this->assertSame(3, $counts['tous']);
        $this->assertSame(1, $counts['today']);
        $this->assertLessThanOrEqual($counts['tous'], $counts['mois']);
    }

    public function test_le_filtre_statut_s_applique(): void
    {
        $this->rdv('Confirme Test', now()->setTime(10, 0), 'confirme');
        $this->rdv('Annule Test', now()->setTime(11, 0), 'annule');

        $this->get(route('dashboard.rdv.index', ['filtre' => 'tous', 'statut' => 'confirme']))
            ->assertOk()
            ->assertSee('Confirme Test')
            ->assertDontSee('Annule Test');
    }

    /**
     * Non-régression : `$request->get()` peut renvoyer null pour un paramètre
     * présent mais vide (?statut=&employe=). Le typage strict de
     * appliquerFiltres() provoquait alors un TypeError → 500 en production.
     */
    public function test_des_parametres_vides_ne_cassent_pas_la_liste(): void
    {
        $this->rdv('Robuste Test', now()->setTime(10, 0));

        $this->get(route('dashboard.rdv.index') . '?filtre=&statut=&employe=&q=')
            ->assertOk()
            ->assertSee('Robuste Test');

        $this->get(route('dashboard.rdv.index') . '?filtre=tous&statut')
            ->assertOk();

        // Paramètre envoyé sous forme de tableau (?q[]=…)
        $this->get(route('dashboard.rdv.index') . '?filtre=tous&q[]=x')
            ->assertOk();
    }

    /**
     * Non-régression : le `<select statut>` est dans un formulaire qui ne
     * transporte pas le filtre de période → changer le statut ramenait sur
     * « semaine » et faisait disparaître les RDV affichés.
     */
    public function test_le_formulaire_de_statut_conserve_le_filtre_de_periode(): void
    {
        $html = $this->get(route('dashboard.rdv.index', ['filtre' => 'tous']))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<form[^>]*>.*?name="filtre"[^>]*value="tous".*?<\/form>/s',
            $html,
            'Le filtre de période doit être conservé par le formulaire de statut.'
        );
    }

    public function test_les_filtres_ne_debordent_pas_en_mobile(): void
    {
        $html = $this->get(route('dashboard.rdv.index'))
            ->assertOk()
            ->getContent();

        // Plus de défilement horizontal : la barre de filtres s'empile sur mobile
        $this->assertStringNotContainsString('overflow-x-auto scrollbar-hide', $html);
        $this->assertStringContainsString('lg:flex-row', $html);

        // Les 4 périodes restent toutes visibles, avec libellé court sur mobile
        $this->assertStringContainsString('grid-cols-4', $html);
        foreach (['Jour', 'Sem.', 'Mois', 'Tous'] as $court) {
            $this->assertStringContainsString('>' . $court . '<', $html);
        }
    }
}
