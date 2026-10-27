<?php

namespace Tests\Feature\Dashboard;

use App\Models\Institut;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Commande `ventes:reparer-mode-credit` : rattrapage des ventes à crédit dont
 * `mode_paiement` a été vidé avant l'ajout de la valeur à l'énumération.
 */
class ReparerModeCreditCommandeTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(): array
    {
        $owner = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($owner->institut_id);

        return [$institut, $owner];
    }

    private function vente(Institut $institut, User $owner, string $mode, int $total, int $paye, ?string $creditStatut = null): Vente
    {
        return Vente::create([
            'institut_id'   => $institut->id,
            'user_id'       => $owner->id,
            'numero'        => 'V-' . uniqid(),
            'total'         => $total,
            'montant_paye'  => $paye,
            'mode_paiement' => $mode,
            'credit_statut' => $creditStatut,
            'statut'        => 'validee',
        ]);
    }

    /**
     * Simule une ligne héritée : la contrainte de mode_paiement est neutralisée
     * le temps d'écrire une valeur vide, comme le faisait MySQL en mode permissif.
     */
    private function viderModePaiement(Vente $vente): void
    {
        DB::statement('PRAGMA ignore_check_constraints = ON');
        DB::table('ventes')->where('id', $vente->id)->update(['mode_paiement' => '']);
        DB::statement('PRAGMA ignore_check_constraints = OFF');
    }

    private function chiffreAffaires(Institut $institut): int
    {
        return (int) Vente::where('institut_id', $institut->id)
            ->where('statut', 'validee')
            ->selectRaw("COALESCE(SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END), 0) as ca")
            ->value('ca');
    }

    public function test_la_commande_corrige_les_ventes_a_credit_sans_mode(): void
    {
        [$institut, $owner] = $this->contexte();

        $vente = $this->vente($institut, $owner, 'cash', 50000, 10000, 'en_cours');
        $this->viderModePaiement($vente);

        $this->assertSame('', $vente->fresh()->mode_paiement);

        $this->artisan('ventes:reparer-mode-credit', ['--force' => true])->assertSuccessful();

        $this->assertSame('credit', $vente->fresh()->mode_paiement);
    }

    public function test_le_rattrapage_corrige_le_chiffre_d_affaires(): void
    {
        [$institut, $owner] = $this->contexte();

        $this->vente($institut, $owner, 'cash', 20000, 20000);
        $credit = $this->vente($institut, $owner, 'cash', 50000, 10000, 'en_cours');
        $this->viderModePaiement($credit);

        // Avant : la vente à crédit est comptée à 50 000 au lieu de 10 000
        $this->assertSame(70000, $this->chiffreAffaires($institut));

        $this->artisan('ventes:reparer-mode-credit', ['--force' => true])->assertSuccessful();

        // Après : 20 000 comptant + 10 000 encaissés sur le crédit
        $this->assertSame(30000, $this->chiffreAffaires($institut));
    }

    public function test_le_dry_run_ne_modifie_rien(): void
    {
        [$institut, $owner] = $this->contexte();

        $vente = $this->vente($institut, $owner, 'cash', 50000, 10000, 'en_cours');
        $this->viderModePaiement($vente);

        $this->artisan('ventes:reparer-mode-credit', ['--dry-run' => true])
            ->expectsOutputToContain('Simulation')
            ->assertSuccessful();

        $this->assertSame('', $vente->fresh()->mode_paiement);
    }

    public function test_les_ventes_normales_ne_sont_pas_touchees(): void
    {
        [$institut, $owner] = $this->contexte();

        $comptant = $this->vente($institut, $owner, 'cash', 8000, 8000);
        $mobile   = $this->vente($institut, $owner, 'mobile_money', 6000, 6000);

        $this->artisan('ventes:reparer-mode-credit', ['--force' => true])->assertSuccessful();

        $this->assertSame('cash', $comptant->fresh()->mode_paiement);
        $this->assertSame('mobile_money', $mobile->fresh()->mode_paiement);
    }

    public function test_les_ventes_deja_correctes_sont_ignorees(): void
    {
        [$institut, $owner] = $this->contexte();

        $credit = $this->vente($institut, $owner, 'credit', 50000, 10000, 'en_cours');

        $this->artisan('ventes:reparer-mode-credit', ['--force' => true])
            ->expectsOutputToContain('Aucune vente à corriger')
            ->assertSuccessful();

        $this->assertSame('credit', $credit->fresh()->mode_paiement);
    }

    public function test_l_option_limite_borne_le_nombre_de_corrections(): void
    {
        [$institut, $owner] = $this->contexte();

        $ventes = [];
        for ($i = 0; $i < 3; $i++) {
            $v = $this->vente($institut, $owner, 'cash', 50000, 10000, 'en_cours');
            $this->viderModePaiement($v);
            $ventes[] = $v;
        }

        $this->artisan('ventes:reparer-mode-credit', ['--force' => true, '--limite' => 2])
            ->assertSuccessful();

        $corrigees = Vente::where('mode_paiement', 'credit')->count();
        $this->assertSame(2, $corrigees);
    }

    public function test_les_ventes_sans_suivi_de_credit_ne_sont_pas_touchees(): void
    {
        [$institut, $owner] = $this->contexte();

        // Mode vide mais aucun credit_statut : ce n'est pas une vente à crédit
        $vente = $this->vente($institut, $owner, 'cash', 5000, 5000);
        $this->viderModePaiement($vente);

        $this->artisan('ventes:reparer-mode-credit', ['--force' => true])->assertSuccessful();

        $this->assertSame('', $vente->fresh()->mode_paiement);
    }
}
