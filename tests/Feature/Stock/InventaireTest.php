<?php

namespace Tests\Feature\Stock;

use App\Models\EtablissementType;
use App\Models\Inventaire;
use App\Models\Institut;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Inventaire physique.
 *
 * Deux défauts corrigés : la validation écrasait le stock sans vérifier qu'il
 * avait bougé depuis le comptage (une vente survenue entre-temps disparaissait),
 * et un brouillon n'était pas modifiable.
 */
class InventaireTest extends TestCase
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

        $produit = Produit::withoutGlobalScopes()->create([
            'institut_id' => $institut->id, 'nom' => 'Masque',
            'reference' => 'MASK-1',
            'prix_achat' => 1000, 'prix_vente' => 2500,
            'stock' => 20, 'seuil_alerte' => 2, 'unite' => 'pot', 'actif' => true,
        ]);

        return [$admin, $institut, $produit];
    }

    private function creerInventaire($admin, $produit, int $compte): Inventaire
    {
        $this->actingAs($admin)->post(route('dashboard.inventaires.store'), [
            'date_inventaire' => now()->toDateString(),
            'comptes'         => [$produit->id => $compte],
        ]);

        return Inventaire::latest('id')->firstOrFail();
    }

    public function test_le_comptage_cree_un_brouillon_sans_toucher_au_stock(): void
    {
        [$admin, , $produit] = $this->contexte();

        $inventaire = $this->creerInventaire($admin, $produit, 15);

        $this->assertSame('en_cours', $inventaire->statut);
        $this->assertSame(20, $produit->fresh()->stock);
        $this->assertSame(-5, (int) $inventaire->lignes->first()->ecart);
        $this->assertSame(-5000, (int) $inventaire->total_ecart_valeur);
    }

    public function test_la_validation_applique_le_stock_et_trace_le_mouvement(): void
    {
        [$admin, , $produit] = $this->contexte();
        $inventaire = $this->creerInventaire($admin, $produit, 15);

        $this->actingAs($admin)
            ->post(route('dashboard.inventaires.valider', $inventaire))
            ->assertRedirect();

        $this->assertSame(15, $produit->fresh()->stock);
        $this->assertSame('valide', $inventaire->fresh()->statut);
        $this->assertSame(1, MouvementStock::withoutGlobalScopes()->where('type', 'correction')->count());
    }

    public function test_la_validation_est_bloquee_si_le_stock_a_bouge_depuis_le_comptage(): void
    {
        [$admin, , $produit] = $this->contexte();
        $inventaire = $this->creerInventaire($admin, $produit, 15);

        // Une vente intervient après le comptage : 20 → 18
        app(StockService::class)->sortir($produit, 2, ['note' => 'Vente après comptage']);

        $this->actingAs($admin)
            ->from(route('dashboard.inventaires.show', $inventaire))
            ->post(route('dashboard.inventaires.valider', $inventaire))
            ->assertRedirect(route('dashboard.inventaires.show', $inventaire))
            ->assertSessionHas('error');

        // Rien n'a été écrasé
        $this->assertSame(18, $produit->fresh()->stock);
        $this->assertSame('en_cours', $inventaire->fresh()->statut);
    }

    public function test_la_validation_confirmee_recalcule_les_ecarts_sur_le_stock_reel(): void
    {
        [$admin, , $produit] = $this->contexte();
        $inventaire = $this->creerInventaire($admin, $produit, 15);

        app(StockService::class)->sortir($produit, 2);   // stock réel : 18

        $this->actingAs($admin)->post(route('dashboard.inventaires.valider', $inventaire), [
            'confirmer_mouvements' => 1,
        ])->assertRedirect();

        $ligne = $inventaire->fresh()->lignes->first();

        // Le théorique de référence devient le stock réel (18), pas les 20 du comptage
        $this->assertSame(18, (int) $ligne->stock_theorique);
        $this->assertSame(-3, (int) $ligne->ecart);
        $this->assertSame(15, $produit->fresh()->stock);
    }

    public function test_un_brouillon_peut_etre_modifie(): void
    {
        [$admin, , $produit] = $this->contexte();
        $inventaire = $this->creerInventaire($admin, $produit, 15);

        $this->actingAs($admin)
            ->get(route('dashboard.inventaires.edit', $inventaire))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('dashboard.inventaires.update', $inventaire), [
                'date_inventaire' => now()->toDateString(),
                'comptes'         => [$produit->id => 19],
            ])
            ->assertRedirect(route('dashboard.inventaires.show', $inventaire));

        $this->assertSame(19, (int) $inventaire->fresh()->lignes->first()->stock_compte);
        $this->assertSame(1, $inventaire->fresh()->lignes->count());
    }

    public function test_un_inventaire_valide_ne_peut_plus_etre_modifie(): void
    {
        [$admin, , $produit] = $this->contexte();
        $inventaire = $this->creerInventaire($admin, $produit, 20);

        $this->actingAs($admin)->post(route('dashboard.inventaires.valider', $inventaire));

        $this->actingAs($admin)
            ->get(route('dashboard.inventaires.edit', $inventaire->fresh()))
            ->assertForbidden();
    }

    public function test_le_formulaire_offre_le_scan_et_les_filtres(): void
    {
        [$admin] = $this->contexte();

        $this->actingAs($admin)
            ->get(route('dashboard.inventaires.create'))
            ->assertOk()
            ->assertSee('Scanner un produit')
            ->assertSee('Écarts uniquement')
            ->assertSee('comptageInventaire', false);
    }
}
