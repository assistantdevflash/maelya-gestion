<?php

namespace Tests\Feature\Console;

use App\Models\Client;
use App\Models\HistoriquePoints;
use App\Models\Institut;
use App\Models\ProgrammeFidelite;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Commande `fidelite:rattraper-points` : rattrapage des points jamais
 * attribués, notamment aux ventes à crédit.
 */
class RattrapagePointsFideliteTest extends TestCase
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

        ProgrammeFidelite::create([
            'institut_id'        => $this->institut->id,
            'actif'              => true,
            'tranche_fcfa'       => 10000,
            'points_par_tranche' => 1,
            'seuil_recompense'   => 10,
            'type_recompense'    => 'pourcentage',
            'valeur_recompense'  => 10,
        ]);
    }

    private function vente(int $total = 45000, string $mode = 'credit', string $date = '2026-06-15'): Vente
    {
        $vente = Vente::create([
            'institut_id'   => $this->institut->id,
            'client_id'     => $this->client->id,
            'user_id'       => $this->admin->id,
            'numero'        => 'V-' . uniqid(),
            'total'         => $total,
            'mode_paiement' => $mode,
            'statut'        => 'validee',
        ]);

        // created_at n'est pas assignable en masse.
        $vente->created_at = $date . ' 10:00:00';
        $vente->save();

        return $vente;
    }

    public function test_la_simulation_n_ecrit_rien(): void
    {
        $this->vente(45000);

        $this->artisan('fidelite:rattraper-points', ['--depuis' => '2026-01-01'])
            ->assertExitCode(0);

        $this->assertSame(0, $this->client->fresh()->points_fidelite);
        $this->assertSame(0, HistoriquePoints::count());
    }

    public function test_apply_attribue_les_points_manquants(): void
    {
        $this->vente(45000);

        $this->artisan('fidelite:rattraper-points', [
            '--depuis' => '2026-01-01',
            '--apply'  => true,
        ])->assertExitCode(0);

        $this->assertSame(4, $this->client->fresh()->points_fidelite);

        $mouvement = HistoriquePoints::firstOrFail();

        $this->assertSame('gain', $mouvement->type);
        $this->assertSame(4, (int) $mouvement->points);
        $this->assertStringContainsString('rattrapage', $mouvement->description);
    }

    public function test_la_commande_est_idempotente(): void
    {
        $this->vente(45000);

        $this->artisan('fidelite:rattraper-points', ['--depuis' => '2026-01-01', '--apply' => true]);
        $this->artisan('fidelite:rattraper-points', ['--depuis' => '2026-01-01', '--apply' => true]);

        $this->assertSame(4, $this->client->fresh()->points_fidelite);
        $this->assertSame(1, HistoriquePoints::count());
    }

    public function test_une_vente_deja_creditee_n_est_pas_doublee(): void
    {
        $vente = $this->vente(45000);

        HistoriquePoints::create([
            'institut_id' => $this->institut->id,
            'client_id'   => $this->client->id,
            'vente_id'    => $vente->id,
            'points'      => 4,
            'type'        => 'gain',
            'description' => '+4 pts (vente #' . $vente->numero . ')',
        ]);

        $this->artisan('fidelite:rattraper-points', ['--depuis' => '2026-01-01', '--apply' => true]);

        $this->assertSame(1, HistoriquePoints::count());
        $this->assertSame(0, $this->client->fresh()->points_fidelite);
    }

    public function test_les_ventes_annulees_et_anterieures_sont_ignorees(): void
    {
        $annulee = $this->vente(45000);
        $annulee->update(['statut' => 'annulee']);

        $this->vente(45000, 'credit', '2025-12-31');

        $this->artisan('fidelite:rattraper-points', [
            '--depuis' => '01/01/2026',
            '--apply'  => true,
        ])->assertExitCode(0);

        $this->assertSame(0, HistoriquePoints::count());
        $this->assertSame(0, $this->client->fresh()->points_fidelite);
    }

    public function test_le_mode_credit_ne_touche_pas_les_ventes_comptant(): void
    {
        $this->vente(45000, 'cash');

        $this->artisan('fidelite:rattraper-points', ['--depuis' => '2026-01-01', '--apply' => true]);

        $this->assertSame(0, HistoriquePoints::count());
    }

    public function test_le_mode_tous_couvre_aussi_les_ventes_comptant(): void
    {
        $this->vente(45000, 'cash');

        $this->artisan('fidelite:rattraper-points', [
            '--depuis' => '2026-01-01',
            '--mode'   => 'tous',
            '--apply'  => true,
        ]);

        $this->assertSame(4, $this->client->fresh()->points_fidelite);
    }

    public function test_un_etablissement_sans_programme_est_ignore(): void
    {
        ProgrammeFidelite::query()->delete();

        $this->vente(45000);

        $this->artisan('fidelite:rattraper-points', ['--depuis' => '2026-01-01', '--apply' => true])
            ->assertExitCode(0);

        $this->assertSame(0, HistoriquePoints::count());
    }

    public function test_une_date_illisible_est_signalee(): void
    {
        $this->artisan('fidelite:rattraper-points', ['--depuis' => 'pas-une-date'])
            ->assertExitCode(1);
    }
}
