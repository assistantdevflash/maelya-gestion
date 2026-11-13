<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategorieProduit;
use App\Models\Depense;
use App\Models\Produit;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cohérence du tableau de bord avec le point financier.
 *
 * Le tableau de bord affichait « Bénéfice ce mois » = CA − dépenses, sans le
 * coût des marchandises vendues. Deux écrans, deux résultats, même mois.
 */
class DashboardCoherenceTest extends TestCase
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
            'institut_id'  => $this->admin->institut_id,
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

    /** Vente convertie en crédit : seul le montant payé entre en caisse. */
    private function vendreACredit(int $montantPaye, int $quantite = 1): Vente
    {
        $vente = $this->vendre($quantite);

        $vente->update([
            'mode_paiement' => 'credit',
            'montant_paye'  => $montantPaye,
            'credit_statut' => 'en_cours',
        ]);

        return $vente->refresh();
    }

    private function depenser(int $montant): void
    {
        $this->actingAs($this->admin)->post(route('dashboard.depenses.store'), [
            'description' => 'Loyer',
            'categorie'   => 'loyer',
            'montant'     => $montant,
            'date'        => now()->toDateString(),
        ])->assertRedirect();
    }

    public function test_le_resultat_du_mois_deduit_le_cout_des_marchandises(): void
    {
        $this->vendre(1);      // 10 000 encaissés, 8 000 de marchandise
        $this->depenser(500);

        $this->actingAs($this->admin)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertViewHas('resultatMois', function (array $r) {
                $this->assertSame(2000, $r['marge_brute']);
                $this->assertSame(500, $r['depenses']);
                $this->assertSame(1500, $r['resultat'], 'CA − marchandises − dépenses.');

                return true;
            });
    }

    public function test_le_tableau_de_bord_n_affiche_plus_un_benefice_sans_cout(): void
    {
        $this->vendre(1);

        $this->actingAs($this->admin)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Résultat ce mois')
            ->assertSee('Coût des marchandises')
            ->assertSee('Marge brute')
            ->assertSee('Résultat net');
    }

    public function test_un_mois_deficitaire_conserve_son_signe_moins(): void
    {
        $this->vendre(1);        // marge brute 2 000
        $this->depenser(3000);   // résultat −1 000

        $reponse = $this->actingAs($this->admin)->get(route('dashboard.index'));

        $reponse->assertOk()->assertViewHas('resultatMois', function (array $r) {
            $this->assertSame(-1000, $r['resultat']);

            return true;
        });

        // Le montant est précédé du vrai signe moins, pas d'un « abs() » muet.
        $this->assertStringContainsString('−1 000', $reponse->getContent());
    }

    public function test_le_credit_non_encaisse_est_affiche_a_part_du_chiffre_d_affaires(): void
    {
        $this->vendreACredit(0);

        $reponse = $this->actingAs($this->admin)->get(route('dashboard.index'));

        $reponse->assertOk()->assertViewHas('creditMois', 10000);

        $this->assertStringContainsString('dont 10 000 F à crédit', $reponse->getContent());
    }

    public function test_les_dernieres_ventes_affichent_le_vrai_mode_de_paiement(): void
    {
        $this->vendreACredit(0);

        $this->actingAs($this->admin)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Crédit')
            // L'ancien code étiquetait « Cash » tout ce qui n'était pas mobile money.
            ->assertDontSee('>Cash<', false);
    }

    public function test_le_cache_du_tableau_de_bord_est_purge_apres_une_depense(): void
    {
        $this->vendre(1);        // marge brute 2 000, résultat 2 000

        // Premier chargement : met le résultat en cache.
        $this->actingAs($this->admin)
            ->get(route('dashboard.index'))
            ->assertViewHas('resultatMois', fn (array $r) => $r['resultat'] === 2000);

        $this->depenser(500);

        // Sans purge, la valeur figée resterait 2 000 pendant cinq minutes.
        $this->actingAs($this->admin)
            ->get(route('dashboard.index'))
            ->assertViewHas('resultatMois', fn (array $r) => $r['resultat'] === 1500);
    }
}
