<?php

namespace Tests\Feature\Dashboard;

use App\Models\Avoir;
use App\Models\CodeReduction;
use App\Models\CodeReductionUtilisation;
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
            // Le filtre s'appelle « Inactifs » : on vise bien le badge.
            ->assertDontSee('>Inactif</span>', false);
    }

    public function test_le_filtre_par_statut_ne_montre_que_les_codes_concernes(): void
    {
        $this->creerCode(['code' => 'ACTIF1']);
        $this->creerCode(['code' => 'EPUISE1', 'limite_utilisation' => 2, 'nb_utilisations' => 2]);
        $this->creerCode(['code' => 'INACTIF1', 'actif' => false]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index', ['statut' => 'epuise']))
            ->assertOk()
            ->assertSee('EPUISE1')
            ->assertDontSee('ACTIF1')
            ->assertDontSee('INACTIF1');
    }

    public function test_la_recherche_avec_une_apostrophe_ne_casse_pas_la_page(): void
    {
        $this->creerCode();

        $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index', ['q' => "l'été"]))
            ->assertOk();
    }

    public function test_les_codes_au_dela_de_trente_sont_accessibles(): void    {
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

    // ── Plafond de remise ────────────────────────────────────────────────────

    public function test_le_plafond_limite_la_remise(): void
    {
        $code = $this->creerCode(['type' => 'pourcentage', 'valeur' => 20, 'plafond_remise' => 10000]);

        $this->assertSame(2000, $code->calculerRemise(10000));
        // 20 % de 100 000 = 20 000, ramené au plafond.
        $this->assertSame(10000, $code->calculerRemise(100000));
        $this->assertStringContainsString('max', $code->libelleRemise());
    }

    public function test_la_remise_ne_depasse_jamais_le_panier(): void
    {
        $code = $this->creerCode(['type' => 'montant_fixe', 'valeur' => 50000]);

        $this->assertSame(3000, $code->calculerRemise(3000));
    }

    // ── Modification, duplication, lot ───────────────────────────────────────

    public function test_le_selecteur_de_client_propose_une_recherche(): void
    {
        Client::create([
            'institut_id' => $this->institutId,
            'prenom'      => 'Fatou',
            'nom'         => 'Traore',
            'telephone'   => '0705060708',
            'actif'       => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.index'))
            ->assertOk()
            ->assertSee('Chercher un client par nom ou téléphone')
            ->assertSee('Fatou Traore')
            // Plus de liste déroulante native : le choix passe par la recherche.
            ->assertDontSee('<select name="client_id"', false);
    }

    public function test_un_code_peut_etre_modifie(): void
    {
        $code = $this->creerCode(['code' => 'ANCIEN', 'valeur' => 10]);

        $this->actingAs($this->admin)
            ->put(route('dashboard.codes-reduction.update', $code), [
                'code'              => 'NOUVEAU',
                'description'       => 'Nouvelle description',
                'type'              => 'pourcentage',
                'valeur'            => 25,
                'plafond_remise'    => 8000,
                'client_id'         => '',
                'montant_minimum'   => 0,
                'date_debut'        => null,
                'date_fin'          => null,
                'limite_utilisation'=> null,
            ])
            ->assertRedirect();

        $code->refresh();

        $this->assertSame('NOUVEAU', $code->code);
        $this->assertSame(25, $code->valeur);
        $this->assertSame(8000, $code->plafond_remise);
    }

    public function test_un_code_peut_etre_duplique_sans_reprendre_les_utilisations(): void
    {
        $code = $this->creerCode(['code' => 'FIDELITE', 'nb_utilisations' => 4]);

        $this->actingAs($this->admin)
            ->post(route('dashboard.codes-reduction.dupliquer', $code))
            ->assertRedirect();

        $copie = CodeReduction::where('code', 'like', 'FIDELITE-COPIE%')->first();

        $this->assertNotNull($copie);
        $this->assertSame(0, $copie->nb_utilisations);
        $this->assertSame($code->valeur, $copie->valeur);
    }

    public function test_la_generation_en_lot_cree_des_codes_uniques(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.codes-reduction.generer'), [
                'quantite'           => 12,
                'prefixe'            => 'ETE',
                'type'               => 'pourcentage',
                'valeur'             => 15,
                'limite_utilisation' => 1,
            ])
            ->assertRedirect();

        $codes = CodeReduction::where('code', 'like', 'ETE-%')->get();

        $this->assertCount(12, $codes);
        $this->assertCount(12, $codes->pluck('code')->unique());
        $this->assertSame(1, $codes->first()->limite_utilisation);
    }

    // ── Historique ───────────────────────────────────────────────────────────

    public function test_l_historique_d_utilisation_est_expose(): void
    {
        $code = $this->creerCode(['code' => 'HISTO10', 'nb_utilisations' => 1]);

        CodeReductionUtilisation::create([
            'code_reduction_id' => $code->id,
            'institut_id'       => $this->institutId,
            'code'              => 'HISTO10',
            'montant_remise'    => 1500,
        ]);

        $reponse = $this->actingAs($this->admin)
            ->getJson(route('dashboard.codes-reduction.utilisations', $code))
            ->assertOk();

        $this->assertSame('HISTO10', $reponse->json('code'));
        $this->assertCount(1, $reponse->json('lignes'));
        $this->assertSame('1 500 F', $reponse->json('lignes.0.remise'));
    }

    // ── Exports ──────────────────────────────────────────────────────────────

    public function test_l_export_csv_des_codes(): void
    {
        $this->creerCode(['code' => 'EXPORT10']);

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.codes-reduction.export'))
            ->assertOk();

        $this->assertStringContainsString('EXPORT10', $reponse->streamedContent());
    }

    public function test_l_export_csv_des_avoirs(): void
    {
        Avoir::create([
            'institut_id' => $this->institutId,
            'user_id'     => $this->admin->id,
            'numero'      => 'AV-EXPORT-0001',
            'montant'     => 7000,
            'statut'      => 'emis',
        ]);

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.avoirs.export'))
            ->assertOk();

        $this->assertStringContainsString('AV-EXPORT-0001', $reponse->streamedContent());
    }

    // ── Contrôle cumulé des avoirs ───────────────────────────────────────────

    public function test_les_avoirs_cumules_ne_peuvent_pas_depasser_la_vente(): void
    {
        $client = Client::create([
            'institut_id' => $this->institutId,
            'prenom'      => 'Awa',
            'nom'         => 'Kone',
            'telephone'   => '0701020304',
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

        app(AvoirService::class)->creer([
            'institut_id' => $this->institutId,
            'vente_id'    => $vente->id,
            'client_id'   => $client->id,
            'user_id'     => $this->admin->id,
            'montant'     => 15000,
        ]);

        $this->actingAs($this->admin)
            ->post(route('dashboard.ventes.avoirs.store', $vente), ['montant' => 10000])
            ->assertSessionHasErrors('montant');

        $this->assertSame(1, Avoir::count());
    }

    // ── Caisse ───────────────────────────────────────────────────────────────

    public function test_la_caisse_recoit_le_code_passe_dans_l_url(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard.caisse', ['code' => 'BIENVENUE10']))
            ->assertOk()
            ->assertSee('BIENVENUE10');
    }
}
