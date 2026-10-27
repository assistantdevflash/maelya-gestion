<?php

namespace Tests\Feature\Dashboard;

use App\Models\Institut;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Non-régression sur `ventes.mode_paiement`.
 *
 * Le module crédits écrit `mode_paiement = 'credit'` depuis l'origine, mais la
 * valeur n'existait pas dans l'énumération : en MySQL strict l'enregistrement
 * échouait, en mode permissif la colonne était vidée et les calculs de
 * chiffre d'affaires comptaient le crédit à 100 %.
 */
class ModePaiementCreditTest extends TestCase
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

    public function test_les_cinq_modes_de_paiement_sont_acceptes(): void
    {
        [$institut, $owner] = $this->contexte();

        foreach (['cash', 'mobile_money', 'carte', 'mixte', 'credit'] as $mode) {
            $vente = $this->vente($institut, $owner, $mode, 5000, 5000);

            $this->assertSame($mode, $vente->fresh()->mode_paiement, "Le mode « {$mode} » n'a pas été enregistré.");
        }
    }

    public function test_une_vente_a_credit_est_enregistree_sans_erreur(): void
    {
        [$institut, $owner] = $this->contexte();

        $vente = $this->vente($institut, $owner, 'credit', 50000, 10000, 'en_cours');

        $vente->refresh();
        $this->assertSame('credit', $vente->mode_paiement);
        $this->assertSame('en_cours', $vente->credit_statut);
        $this->assertSame(10000, $vente->montant_paye);
    }

    public function test_le_chiffre_d_affaires_ne_compte_que_l_encaissement_du_credit(): void
    {
        [$institut, $owner] = $this->contexte();

        // Vente comptant : 20 000 encaissés
        $this->vente($institut, $owner, 'cash', 20000, 20000);
        // Vente à crédit : 50 000 au total, 10 000 d'apport
        $this->vente($institut, $owner, 'credit', 50000, 10000, 'en_cours');

        // Formule utilisée dans FinanceController, DashboardController, etc.
        $ca = (int) Vente::where('institut_id', $institut->id)
            ->where('statut', 'validee')
            ->selectRaw("COALESCE(SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END), 0) as ca")
            ->value('ca');

        $this->assertSame(30000, $ca);
    }

    public function test_les_ventes_a_credit_sont_isolees_dans_les_compteurs_de_paiement(): void
    {
        [$institut, $owner] = $this->contexte();

        $this->vente($institut, $owner, 'cash', 8000, 8000);
        $this->vente($institut, $owner, 'credit', 50000, 12000, 'en_cours');

        $ligne = Vente::where('institut_id', $institut->id)
            ->where('statut', 'validee')
            ->selectRaw("
                SUM(CASE WHEN mode_paiement = 'cash' THEN total ELSE 0 END) as cash,
                SUM(CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE 0 END) as credit
            ")
            ->first();

        $this->assertSame(8000, (int) $ligne->cash);
        $this->assertSame(12000, (int) $ligne->credit);
    }

    public function test_un_credit_sans_apport_n_apparait_pas_comme_vente_encaissee(): void
    {
        [$institut, $owner] = $this->contexte();

        $this->vente($institut, $owner, 'credit', 50000, 0, 'en_cours');

        $nb = Vente::where('institut_id', $institut->id)
            ->where('statut', 'validee')
            ->where(function ($q) {
                $q->where('mode_paiement', '!=', 'credit')->orWhere('montant_paye', '>', 0);
            })
            ->count();

        $this->assertSame(0, $nb);
    }
}
