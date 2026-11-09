<?php

namespace Tests\Feature\Stock;

use App\Models\BonCommande;
use App\Models\EtablissementType;
use App\Models\Fournisseur;
use App\Models\Institut;
use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Réapprovisionnement : détection des produits sous seuil et génération d'un
 * bon de commande par fournisseur.
 */
class ReapprovisionnementTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(): array
    {
        EtablissementType::create([
            'code' => 'salon_coiffure', 'libelle' => 'Salon',
            'actif' => true, 'position' => 1,
        ]);

        $admin = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($admin->institut_id);

        $fournisseurA = Fournisseur::create([
            'institut_id' => $institut->id, 'nom' => 'Beauté Import', 'actif' => true,
        ]);
        $fournisseurB = Fournisseur::create([
            'institut_id' => $institut->id, 'nom' => 'Coiffure Plus', 'actif' => true,
        ]);

        $bas = Produit::withoutGlobalScopes()->create([
            'institut_id' => $institut->id, 'fournisseur_id' => $fournisseurA->id,
            'nom' => 'Huile', 'prix_achat' => 1000, 'prix_vente' => 2000,
            'stock' => 2, 'seuil_alerte' => 5, 'unite' => 'flacon', 'actif' => true,
        ]);
        $bas2 = Produit::withoutGlobalScopes()->create([
            'institut_id' => $institut->id, 'fournisseur_id' => $fournisseurA->id,
            'nom' => 'Masque', 'prix_achat' => 500, 'prix_vente' => 1200,
            'stock' => 0, 'seuil_alerte' => 3, 'unite' => 'pot', 'actif' => true,
        ]);
        $bas3 = Produit::withoutGlobalScopes()->create([
            'institut_id' => $institut->id, 'fournisseur_id' => $fournisseurB->id,
            'nom' => 'Peigne', 'prix_achat' => 300, 'prix_vente' => 900,
            'stock' => 1, 'seuil_alerte' => 4, 'unite' => 'pièce', 'actif' => true,
        ]);
        $ok = Produit::withoutGlobalScopes()->create([
            'institut_id' => $institut->id, 'fournisseur_id' => $fournisseurA->id,
            'nom' => 'Shampoing', 'prix_achat' => 700, 'prix_vente' => 1500,
            'stock' => 50, 'seuil_alerte' => 5, 'unite' => 'flacon', 'actif' => true,
        ]);

        return [$admin, $institut, [$bas, $bas2, $bas3, $ok], $fournisseurA, $fournisseurB];
    }

    public function test_la_page_liste_uniquement_les_produits_sous_seuil(): void
    {
        [$admin] = $this->contexte();

        $this->actingAs($admin)
            ->get(route('dashboard.stock.reappro'))
            ->assertOk()
            ->assertSee('Huile')
            ->assertSee('Masque')
            ->assertSee('Peigne')
            ->assertDontSee('Shampoing');
    }

    public function test_la_generation_cree_un_bon_par_fournisseur(): void
    {
        [$admin, , [$bas, $bas2, $bas3]] = $this->contexte();

        $this->actingAs($admin)->post(route('dashboard.stock.generer-bc'), [
            'lignes' => [
                $bas->id  => 8,
                $bas2->id => 6,
                $bas3->id => 7,
            ],
        ])->assertRedirect(route('dashboard.bons-commande.index'));

        $bons = BonCommande::withoutGlobalScopes()->get();

        // Deux fournisseurs distincts → deux bons
        $this->assertCount(2, $bons);
        $this->assertSame('brouillon', $bons->first()->statut);

        $parFournisseur = $bons->groupBy('fournisseur_id');
        $this->assertCount(2, $parFournisseur);

        $totalQuantites = $bons->flatMap->lignes->sum('quantite_commandee');
        $this->assertSame(21, $totalQuantites);
    }

    public function test_le_total_du_bon_correspond_aux_lignes(): void
    {
        [$admin, , [$bas, $bas2]] = $this->contexte();
        $produitSansFournisseur = Produit::withoutGlobalScopes()->create([
            'institut_id' => $bas->institut_id, 'nom' => 'Sans fournisseur',
            'prix_achat' => 100, 'prix_vente' => 300, 'stock' => 0,
            'seuil_alerte' => 2, 'unite' => 'u', 'actif' => true,
        ]);

        $this->actingAs($admin)->post(route('dashboard.stock.generer-bc'), [
            'lignes' => [
                $bas->id  => 8,   // 8 × 1 000 = 8 000
                $bas2->id => 6,   // 6 × 500   = 3 000
                $produitSansFournisseur->id => 5,
            ],
        ]);

        $bonA = BonCommande::withoutGlobalScopes()->firstOrFail();

        $this->assertSame(11000, (int) $bonA->total_ht);
        $this->assertCount(2, $bonA->lignes);
    }

    public function test_les_produits_sans_fournisseur_sont_ignores_avec_un_message(): void
    {
        [$admin, , ] = $this->contexte();

        $orphelin = Produit::withoutGlobalScopes()->create([
            'institut_id' => Institut::first()->id, 'nom' => 'Orphelin',
            'prix_achat' => 100, 'prix_vente' => 300, 'stock' => 0,
            'seuil_alerte' => 2, 'unite' => 'u', 'actif' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('dashboard.stock.generer-bc'), [
                'lignes' => [$orphelin->id => 5],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, BonCommande::withoutGlobalScopes()->count());
    }
}
