<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategorieProduit;
use App\Models\Depense;
use App\Models\Produit;
use App\Models\Vente;
use App\Services\FinanceService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Point financier : le résultat net doit tenir compte du coût des
 * marchandises vendues, et non seulement des dépenses.
 */
class FinanceResultatTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private Produit $produit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->creerAdmin();

        $cat = CategorieProduit::create([
            'institut_id' => $this->admin->institut_id,
            'nom'         => 'Épicerie',
            'ordre'       => 1,
        ]);

        $this->produit = Produit::create([
            'institut_id' => $this->admin->institut_id,
            'categorie_id' => $cat->id,
            'nom'          => 'Riz 5 kg',
            'prix_vente'   => 10000,
            'prix_achat'   => 8000,
            'stock'        => 50,
            'seuil_alerte' => 2,
            'actif'        => true,
        ]);
    }

    private function vendre(int $quantite = 1): Vente
    {
        $this->actingAs($this->admin)->post(route('dashboard.ventes.store'), [
            'panier_json' => json_encode([[
                'type'     => 'produit',
                'id'       => $this->produit->id,
                'nom'      => $this->produit->nom,
                'prix'     => 10000,
                'quantite' => $quantite,
            ]]),
            'mode_paiement' => 'cash',
            'total'         => 10000 * $quantite,
        ])->assertRedirect();

        return Vente::orderByDesc('created_at')->firstOrFail();
    }

    public function test_le_resultat_net_deduit_le_cout_des_marchandises(): void
    {
        $this->vendre(1);   // 10 000 encaissés, 8 000 de marchandise

        Depense::create([
            'institut_id' => $this->admin->institut_id,
            'user_id'     => $this->admin->id,
            'description' => 'Loyer',
            'categorie'   => 'loyer',
            'montant'     => 500,
            'date'        => now()->toDateString(),
        ]);

        $resultat = app(FinanceService::class)->resultat(
            $this->admin->institut_id,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertSame(10000, $resultat['ca']);
        $this->assertSame(8000, $resultat['couts_marchandises']);
        $this->assertSame(2000, $resultat['marge_brute']);
        $this->assertSame(500, $resultat['depenses']);
        $this->assertSame(1500, $resultat['resultat'], 'CA − marchandises − dépenses.');
    }

    public function test_une_vente_a_credit_ne_compte_que_le_montant_encaisse_dans_le_ca(): void
    {
        $vente = $this->vendre(2); // total 20 000

        $vente->update([
            'mode_paiement' => 'credit',
            'montant_paye'  => 5000,
            'credit_statut' => 'en_cours',
        ]);

        $resultat = app(FinanceService::class)->resultat(
            $this->admin->institut_id,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertSame(5000, $resultat['ca'], 'Seul l\'encaissé compte dans le chiffre d\'affaires.');
        $this->assertSame(1, $resultat['nb_ventes'], 'La vente existe quand même.');
    }

    public function test_le_nombre_de_ventes_inclut_les_credits_non_regles(): void
    {
        $vente = $this->vendre(1);
        $vente->update([
            'mode_paiement' => 'credit',
            'montant_paye'  => 0,
            'credit_statut' => 'en_cours',
        ]);

        $resultat = app(FinanceService::class)->resultat(
            $this->admin->institut_id,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertSame(1, $resultat['nb_ventes']);
        $this->assertSame(0, $resultat['ca']);
    }

    public function test_le_seuil_de_rentabilite_couvre_les_depenses(): void
    {
        $this->vendre(1); // marge 2 000 sur 10 000 => 20 %

        Depense::create([
            'institut_id' => $this->admin->institut_id,
            'user_id'     => $this->admin->id,
            'description' => 'Charges',
            'categorie'   => 'autres',
            'montant'     => 1000,
            'date'        => now()->toDateString(),
        ]);

        $resultat = app(FinanceService::class)->resultat(
            $this->admin->institut_id,
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        // 1 000 F de charges à 20 % de marge => 5 000 F de CA nécessaires
        $this->assertSame(5000, $resultat['seuil_rentabilite']);
    }

    public function test_la_page_finances_affiche_la_chaine_de_resultat(): void
    {
        $this->vendre(1);

        $this->actingAs($this->admin)
            ->get(route('dashboard.finances.index'))
            ->assertOk()
            ->assertSee("Coût des marchandises")
            ->assertSee('Marge brute')
            ->assertSee('Résultat net');
    }

    public function test_le_rapport_affiche_le_cout_des_marchandises(): void
    {
        $this->vendre(1);

        $this->actingAs($this->admin)
            ->get(route('dashboard.finances.rapport'))
            ->assertOk()
            ->assertSee('Marchandises');
    }

    public function test_l_encours_credit_est_calcule(): void
    {
        $this->assertSame(0, app(FinanceService::class)->encoursCredit($this->admin->institut_id)['encours']);
    }

    public function test_les_depenses_sans_justificatif_sont_comptees(): void
    {
        Depense::create([
            'institut_id' => $this->admin->institut_id,
            'user_id'     => $this->admin->id,
            'description' => 'Sans justificatif',
            'categorie'   => 'autres',
            'montant'     => 100,
            'date'        => now()->toDateString(),
        ]);

        $this->assertSame(1, app(FinanceService::class)
            ->depensesSansJustificatif($this->admin->institut_id, now()->startOfMonth(), now()->endOfMonth()));
    }
}
