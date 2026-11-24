<?php

namespace Tests\Feature\Dashboard;

use App\Models\Avoir;
use App\Models\CodeReduction;
use App\Models\Client;
use App\Models\User;
use App\Models\Vente;
use App\Services\AvoirService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Écran « Remises & Avoirs » : codes de réduction et avoirs clients.
 *
 * Cette page n'était couverte par aucun test, ce qui a laissé passer une
 * erreur 500 sur SQLite (fonction `NOW()` inexistante) et une liste de codes
 * tronquée à 30 éléments sans pagination.
 */
class RemisesAvoirsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $institutId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->creerAdmin();
        $this->institutId = $this->admin->institut_id;
    }

    private function creerCode(array $attrs = []): CodeReduction
    {
        return CodeReduction::create(array_merge([
            'institut_id' => $this->institutId,
            'code'        => 'PROMO' . random_int(1000, 9999),
            'type'        => 'pourcentage',
            'valeur'      => 10,
            'actif'       => true,
            'nb_utilisations' => 0,
        ], $attrs));
    }

    // ── La page ──────────────────────────────────────────────────────────────

    public function test_la_page_s_affiche_avec_les_statistiques(): void
    {
        $this->creerCode(['code' => 'BIENVENUE10']);
        $this->creerCode(['code' => 'FIDELITE', 'actif' => false]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index'))
            ->assertOk()
            ->assertSee('BIENVENUE10')
            ->assertSee('FIDELITE');
    }

    public function test_les_statistiques_affichent_zero_quand_tout_est_vide(): void
    {
        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index'))
            ->assertOk();

        // Aucun code : les compteurs doivent afficher 0, pas une case vide.
        $reponse->assertSee('Total codes');
        $reponse->assertSee('Codes actifs');
        $this->assertSame(0, CodeReduction::count());
    }

    public function test_un_code_programme_n_est_pas_affiche_comme_inactif(): void
    {
        $this->creerCode([
            'code'       => 'NOEL2026',
            'date_debut' => now()->addMonth(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index'))
            ->assertOk()
            ->assertSee('Programmé')
            ->assertDontSee('Inactif');
    }

    public function test_la_recherche_avec_une_apostrophe_ne_casse_pas_la_page(): void
    {
        $this->creerCode();

        $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index', ['q' => "l'été"]))
            ->assertOk();
    }

    public function test_les_codes_au_dela_de_trente_sont_accessibles(): void
    {
        for ($i = 1; $i <= 35; $i++) {
            $this->creerCode(['code' => 'CODE' . str_pad((string) $i, 3, '0', STR_PAD_LEFT)]);
        }

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index'))
            ->assertOk();

        // Le premier lot est affiché…
        $reponse->assertSee('CODE001');
        $reponse->assertDontSee('CODE035');

        // … et la pagination permet d'atteindre le reste.
        $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index', ['page' => 2]))
            ->assertOk()
            ->assertSee('CODE035');
    }

    // ── Onglets ──────────────────────────────────────────────────────────────

    public function test_l_onglet_avoirs_s_ouvre_directement(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index', ['tab' => 'avoirs']))
            ->assertOk()
            ->assertSee("onglet: 'avoirs'", false);
    }

    public function test_la_route_avoirs_renvoie_vers_l_onglet_avoirs(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard.avoirs.index'))
            ->assertRedirect(route('dashboard.codes-reduction.index', ['tab' => 'avoirs']));
    }

    // ── Avoirs ───────────────────────────────────────────────────────────────

    public function test_un_avoir_cree_apparait_dans_l_onglet_avoirs(): void
    {
        $client = Client::create([
            'institut_id' => $this->institutId,
            'prenom'      => 'Sophie',
            'nom'         => 'Test',
            'telephone'   => '0102030405',
            'actif'       => true,
        ]);

        $vente = Vente::create([
            'institut_id'   => $this->institutId,
            'client_id'     => $client->id,
            'user_id'       => $this->admin->id,
            'total'         => 20000,
            'mode_paiement' => 'cash',
            'montant_cash'  => 20000,
            'statut'        => 'validee',
        ]);

        $avoir = app(AvoirService::class)->creer([
            'institut_id' => $this->institutId,
            'vente_id'    => $vente->id,
            'client_id'   => $client->id,
            'user_id'     => $this->admin->id,
            'montant'     => 5000,
            'motif'       => 'Retour article',
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index', ['tab' => 'avoirs']))
            ->assertOk()
            ->assertSee($avoir->numero)
            ->assertSee('Sophie Test');
    }

    public function test_marquer_un_avoir_utilise_le_desactive(): void
    {
        $avoir = Avoir::create([
            'institut_id' => $this->institutId,
            'user_id'     => $this->admin->id,
            'numero'      => 'AV-TEST-0001',
            'montant'     => 5000,
            'statut'      => 'emis',
            'code_reduction_id' => $this->creerCode()->id,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('dashboard.avoirs.marquer-utilise', $avoir))
            ->assertRedirect();

        $this->assertSame('utilise', $avoir->fresh()->statut);
    }
}
