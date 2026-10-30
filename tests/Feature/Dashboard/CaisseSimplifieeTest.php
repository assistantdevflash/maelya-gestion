<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategorieProduit;
use App\Models\CategoriePrestation;
use App\Models\Prestation;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Caisse simplifiée : mode commerce / supermarché.
 *
 * - produits uniquement (aucune prestation)
 * - pas de grille catalogue ni de catégories
 * - pas de paiement à crédit
 * - la caisse complète reste strictement inchangée
 */
class CaisseSimplifieeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->creerAdmin(['role' => 'admin'], ['nom' => 'Commerce Test']);

        $catPresta = CategoriePrestation::create([
            'institut_id' => $this->user->institut_id,
            'nom'         => 'Coiffure',
        ]);

        Prestation::create([
            'institut_id'  => $this->user->institut_id,
            'categorie_id' => $catPresta->id,
            'nom'          => 'Coupe homme',
            'prix'         => 5000,
            'duree'        => 30,
            'actif'        => true,
        ]);

        $catProd = CategorieProduit::create([
            'institut_id' => $this->user->institut_id,
            'nom'         => 'Épicerie',
        ]);

        Produit::create([
            'institut_id'  => $this->user->institut_id,
            'categorie_id' => $catProd->id,
            'nom'          => 'Riz 5 kg',
            'prix_vente'   => 4500,
            'prix_achat'   => 3800,
            'stock'        => 12,
            'seuil_alerte' => 2,
            'actif'        => true,
        ]);
    }

    public function test_la_caisse_simplifiee_est_accessible(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard.caisse.simplifiee'))
            ->assertOk()
            ->assertSee('Caisse simplifiée');
    }

    public function test_la_caisse_simplifiee_ne_charge_que_les_produits(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('dashboard.caisse.simplifiee'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Riz 5 kg', $html, 'Les produits doivent être disponibles pour la recherche.');
        $this->assertStringNotContainsString(
            'Coupe homme',
            $html,
            'Aucune prestation ne doit être chargée dans la caisse simplifiée.'
        );
        $this->assertStringNotContainsString('Coiffure', $html, 'Les catégories de prestations ne doivent pas être chargées.');
    }

    public function test_la_caisse_simplifiee_masque_les_onglets_et_categories(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('dashboard.caisse.simplifiee'))
            ->assertOk()
            ->getContent();

        // Les onglets Prestations / Produits du catalogue sont côté serveur
        $this->assertStringNotContainsString("changerOnglet('prestations')", $html);
        $this->assertStringContainsString("mode: 'simple'", $html);
    }

    public function test_la_caisse_simplifiee_propose_un_retour_vers_la_caisse_complete(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard.caisse.simplifiee'))
            ->assertOk()
            ->assertSee('Caisse complète');
    }

    public function test_la_caisse_complete_affiche_le_bouton_caisse_simplifiee(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard.caisse'))
            ->assertOk()
            ->assertSee('Caisse simplifiée')
            ->assertSee("mode: 'complet'", false);
    }

    public function test_la_caisse_complete_reste_inchangee(): void
    {
        $html = $this->actingAs($this->user)
            ->get(route('dashboard.caisse'))
            ->assertOk()
            ->getContent();

        // Prestations + catégories toujours présentes en mode complet
        $this->assertStringContainsString('Coupe homme', $html);
        $this->assertStringContainsString('Coiffure', $html);
        $this->assertStringContainsString("changerOnglet('prestations')", $html);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Restriction au plan Premium+ (scan code-barres)
    // ─────────────────────────────────────────────────────────────────────

    /** Bascule l'utilisateur courant sur un plan Premium (sans scan code-barres). */
    private function passerAuPlanPremium(): void
    {
        $plan = $this->creerPlan([
            'nom'        => 'Premium',
            'slug'       => 'premium',
            'duree_type' => 'mensuel',
            'prix'       => 9900,
        ]);

        \App\Models\Abonnement::where('user_id', $this->user->id)->update(['plan_id' => $plan->id]);

        $this->user = $this->user->fresh();
        $this->assertFalse($this->user->aFonctionnalite('scan_code_barre'));
    }

    public function test_la_caisse_simplifiee_est_inaccessible_sans_premium_plus(): void
    {
        $this->passerAuPlanPremium();

        $this->actingAs($this->user)
            ->get(route('dashboard.caisse.simplifiee'))
            ->assertRedirect(route('abonnement.upgrade', ['feature' => 'scan_code_barre']));
    }

    public function test_le_bouton_est_verrouille_sans_premium_plus(): void
    {
        $this->passerAuPlanPremium();

        $this->actingAs($this->user)
            ->get(route('dashboard.caisse'))
            ->assertOk()
            ->assertSee('Premium+')
            ->assertSee(route('abonnement.upgrade', ['feature' => 'scan_code_barre']), false)
            ->assertDontSee(route('dashboard.caisse.simplifiee'), false);
    }

    public function test_le_mode_simple_ne_peut_pas_etre_force_sans_premium_plus(): void
    {
        $this->passerAuPlanPremium();

        // Garde-fou serveur : même en forçant l'argument du composant,
        // on retombe sur la caisse complète.
        $this->actingAs($this->user);

        \Livewire\Livewire::test(\App\Livewire\Caisse::class, ['mode' => 'simple'])
            ->assertSet('mode', 'complet');

        \Livewire\Livewire::test(\App\Livewire\Caisse::class, ['mode' => 'complet'])
            ->assertSet('mode', 'complet');
    }

    public function test_le_mode_simple_fonctionne_avec_premium_plus(): void
    {
        $this->assertTrue($this->user->aFonctionnalite('scan_code_barre'));
        $this->actingAs($this->user);

        \Livewire\Livewire::test(\App\Livewire\Caisse::class, ['mode' => 'simple'])
            ->assertSet('mode', 'simple');
    }
}
