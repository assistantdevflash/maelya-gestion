<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\Credit;
use App\Models\HistoriquePoints;
use App\Models\Institut;
use App\Models\ProgrammeFidelite;
use App\Models\User;
use App\Models\Vente;
use App\Services\FideliteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Programme de fidélité : attribution des points, conversion, ajustement.
 *
 * Aucun test ne couvrait cet écran. Deux défauts majeurs en découlaient :
 * les ventes à crédit ne rapportaient aucun point, et un ajustement négatif
 * pouvait désynchroniser définitivement le solde de l'historique.
 */
class FideliteProgrammeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Institut $institut;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->creerAdmin();
        $this->institut = Institut::findOrFail($this->admin->institut_id);

        $this->client = Client::create([
            'institut_id' => $this->institut->id,
            'prenom'      => 'Awa',
            'nom'         => 'Kone',
            'telephone'   => '0701020304',
            'actif'       => true,
        ]);
    }

    private function programme(array $attrs = []): ProgrammeFidelite
    {
        return ProgrammeFidelite::updateOrCreate(
            ['institut_id' => $this->institut->id],
            array_merge([
                'actif'              => true,
                'tranche_fcfa'       => 10000,
                'points_par_tranche' => 1,
                'seuil_recompense'   => 10,
                'type_recompense'    => 'pourcentage',
                'valeur_recompense'  => 10,
            ], $attrs)
        );
    }

    private function vente(int $total, string $mode = 'cash'): Vente
    {
        return Vente::create([
            'institut_id'   => $this->institut->id,
            'client_id'     => $this->client->id,
            'user_id'       => $this->admin->id,
            'numero'        => 'V-' . uniqid(),
            'total'         => $total,
            'mode_paiement' => $mode,
            'montant_cash'  => $mode === 'cash' ? $total : 0,
            'statut'        => 'validee',
        ]);
    }

    // ── Attribution ──────────────────────────────────────────────────────────

    public function test_une_vente_comptant_rapporte_des_points(): void
    {
        $this->programme();

        $points = app(FideliteService::class)->attribuerPointsPourVente($this->vente(45000), $this->admin->id);

        $this->assertSame(4, $points);
        $this->assertSame(4, $this->client->fresh()->points_fidelite);
        $this->assertSame(1, HistoriquePoints::where('type', 'gain')->count());
    }

    public function test_une_vente_a_credit_rapporte_les_memes_points(): void
    {
        $this->programme();

        // Vente à crédit créée depuis le formulaire de crédit.
        $this->actingAs($this->admin)
            ->post(route('dashboard.credits.store'), [
                'client_id'      => $this->client->id,
                'lignes'         => json_encode([
                    ['designation' => 'Prestation', 'quantite' => 1, 'prix_unitaire' => 75000],
                ]),
                'apport_initial' => 0,
                'nb_echeances'   => 3,
                'frequence'      => 'mensuelle',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Credit::count());
        $this->assertSame(7, $this->client->fresh()->points_fidelite);
        $this->assertSame(7, (int) HistoriquePoints::where('type', 'gain')->sum('points'));
    }

    public function test_aucun_point_sans_programme_actif(): void
    {
        $this->programme(['actif' => false]);

        $points = app(FideliteService::class)->attribuerPointsPourVente($this->vente(50000), $this->admin->id);

        $this->assertSame(0, $points);
        $this->assertSame(0, $this->client->fresh()->points_fidelite);
    }

    // ── Conversion ───────────────────────────────────────────────────────────

    public function test_convertir_cree_un_code_et_le_lie_a_l_historique(): void
    {
        $programme = $this->programme();
        $this->client->update(['points_fidelite' => 25]);

        $code = app(FideliteService::class)->convertir($this->client, $programme, $this->admin->id);

        $this->assertStringStartsWith(FideliteService::PREFIXE_CODE, $code->code);
        $this->assertSame($this->client->id, $code->client_id);
        $this->assertSame(15, $this->client->fresh()->points_fidelite);

        $mouvement = HistoriquePoints::where('type', 'recompense')->firstOrFail();

        $this->assertSame(-10, (int) $mouvement->points);
        $this->assertSame($code->id, $mouvement->code_reduction_id, 'La conversion doit pointer vers le code généré.');
        $this->assertSame($this->admin->id, $mouvement->user_id);
    }

    public function test_convertir_refuse_si_les_points_sont_insuffisants(): void
    {
        $programme = $this->programme();
        $this->client->update(['points_fidelite' => 4]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(FideliteService::class)->convertir($this->client, $programme, $this->admin->id);
    }

    // ── Ajustement : solde = somme des mouvements ────────────────────────────

    public function test_un_ajustement_negatif_enregistre_le_delta_reel(): void
    {
        $this->client->update(['points_fidelite' => 3]);

        $applique = app(FideliteService::class)->ajuster($this->client, -10, 'Correction erreur', $this->admin->id);

        $this->assertSame(-3, $applique, 'On ne peut retirer que les points disponibles.');
        $this->assertSame(0, $this->client->fresh()->points_fidelite);
        $this->assertSame(-3, (int) HistoriquePoints::sum('points'), 'L\'historique doit refléter le solde.');
    }

    public function test_la_somme_de_l_historique_egale_toujours_le_solde(): void
    {
        $this->programme();

        $service = app(FideliteService::class);
        $service->attribuerPointsPourVente($this->vente(50000), $this->admin->id);   // +5
        $service->ajuster($this->client, -3, 'Cadeau offert', $this->admin->id);      // -3
        $service->ajuster($this->client, -100, 'Trop', $this->admin->id === null ? null : $this->admin->id); // -2

        $this->assertSame(
            (int) $this->client->fresh()->points_fidelite,
            (int) HistoriquePoints::sum('points')
        );
    }

    public function test_un_ajustement_sans_effet_est_refuse(): void
    {
        $this->client->update(['points_fidelite' => 0]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(FideliteService::class)->ajuster($this->client, -5, 'Rien à retirer', $this->admin->id);
    }

    // ── Écran ────────────────────────────────────────────────────────────────

    public function test_l_ecran_affiche_les_compteurs_et_la_progression(): void
    {
        $programme = $this->programme();
        $this->client->update(['points_fidelite' => 25]);

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.fidelite.index'))
            ->assertOk();

        $reponse->assertSee('25');
        // 25 points / seuil 10 → 2 récompenses, 5 restants avant la suivante.
        $reponse->assertSee('🎁 ×2');
        $reponse->assertSee('2 récompenses disponibles'); // title de la pastille
        $reponse->assertSee('5/10');
        $this->assertSame(10, $programme->seuil_recompense);
    }

    public function test_un_client_a_zero_point_apparait_avec_le_filtre_tous(): void
    {
        $this->programme();
        $this->client->update(['points_fidelite' => 0]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.fidelite.index'))
            ->assertOk()
            ->assertDontSee('Awa Kone');

        $this->actingAs($this->admin)
            ->get(route('dashboard.fidelite.index', ['clients' => 'tous']))
            ->assertOk()
            ->assertSee('Awa Kone');
    }

    public function test_la_recherche_retrouve_un_client(): void
    {
        $this->programme();
        $this->client->update(['points_fidelite' => 12]);

        Client::create([
            'institut_id' => $this->institut->id,
            'prenom'      => 'Fatou',
            'nom'         => 'Traore',
            'telephone'   => '0705060708',
            'actif'       => true,
            'points_fidelite' => 30,
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.fidelite.index', ['q' => 'Fatou']))
            ->assertOk()
            ->assertSee('Fatou Traore')
            ->assertDontSee('Awa Kone');
    }

    public function test_l_historique_d_un_client_est_expose(): void
    {
        $this->client->update(['points_fidelite' => 8]);

        HistoriquePoints::create([
            'institut_id' => $this->institut->id,
            'client_id'   => $this->client->id,
            'user_id'     => $this->admin->id,
            'points'      => 8,
            'type'        => 'ajustement',
            'description' => 'Bonus anniversaire',
        ]);

        $reponse = $this->actingAs($this->admin)
            ->getJson(route('dashboard.fidelite.historique', $this->client))
            ->assertOk();

        $this->assertSame(8, $reponse->json('solde'));
        $this->assertSame('Bonus anniversaire', $reponse->json('lignes.0.description'));
        $this->assertNotEmpty($reponse->json('lignes.0.auteur'));
    }

    public function test_l_export_csv_du_solde(): void
    {
        $this->programme();
        $this->client->update(['points_fidelite' => 25]);

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.fidelite.export'))
            ->assertOk();

        $csv = $reponse->streamedContent();

        $this->assertStringContainsString('Awa Kone', $csv);
        $this->assertStringContainsString('25', $csv);
    }

    public function test_le_coupon_fidelite_reutilise_le_gabarit_unique(): void
    {
        $this->programme();
        $this->client->update(['points_fidelite' => 20]);

        $code = app(FideliteService::class)->convertir($this->client, $this->programme(), $this->admin->id);

        $this->actingAs($this->admin)
            ->get(route('dashboard.fidelite.imprimer-code', $code))
            ->assertRedirect(route('dashboard.codes-reduction.print', $code));
    }

    // ── Préfixe réservé ──────────────────────────────────────────────────────

    public function test_le_prefixe_fid_est_refuse_a_la_creation_manuelle(): void
    {
        $this->actingAs($this->admin)
            ->post(route('dashboard.codes-reduction.store'), [
                'code'              => 'FID-FALSIFIE',
                'type'              => 'pourcentage',
                'valeur'            => 10,
                'client_id'         => '',
                'montant_minimum'   => 0,
                'date_debut'        => null,
                'date_fin'          => null,
                'limite_utilisation'=> null,
            ])
            ->assertSessionHasErrors('code');

        $this->assertSame(0, CodeReduction::count());
    }
}
