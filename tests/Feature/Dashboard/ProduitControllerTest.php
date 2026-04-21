<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategorieProduit;
use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProduitControllerTest extends TestCase
{
    use RefreshDatabase;

    private function creerCategorie(string $nom = 'Soins'): CategorieProduit
    {
        return CategorieProduit::create(['nom' => $nom]);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_redirige_si_non_authentifie(): void
    {
        $this->get(route('dashboard.produits.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_accessible_pour_admin(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->get(route('dashboard.produits.index'))
            ->assertOk()
            ->assertViewIs('dashboard.produits.index');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_cree_un_produit_valide(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->post(route('dashboard.produits.store'), [
                'nom'          => 'Shampoing Premium',
                'prix_vente'   => 5000,
                'prix_achat'   => 2000,
                'stock'        => 15,
                'seuil_alerte' => 3,
                'unite'        => 'flacon',
            ])
            ->assertRedirect(route('dashboard.produits.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('produits', [
            'nom'        => 'Shampoing Premium',
            'prix_vente' => 5000,
            'stock'      => 15,
        ]);
    }

    public function test_store_echoue_si_champs_requis_manquants(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->post(route('dashboard.produits.store'), [])
            ->assertSessionHasErrors(['nom', 'prix_vente', 'stock', 'seuil_alerte', 'unite']);
    }

    public function test_store_echoue_si_prix_vente_inferieur_au_prix_achat(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->post(route('dashboard.produits.store'), [
                'nom'          => 'Produit Test',
                'prix_vente'   => 1000,
                'prix_achat'   => 3000, // achat > vente
                'stock'        => 5,
                'seuil_alerte' => 1,
                'unite'        => 'u',
            ])
            ->assertSessionHasErrors(['prix_vente']);
    }

    public function test_store_accepte_prix_vente_egal_zero(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->post(route('dashboard.produits.store'), [
                'nom'          => 'Cadeau',
                'prix_vente'   => 0,
                'stock'        => 1,
                'seuil_alerte' => 0,
                'unite'        => 'u',
            ])
            ->assertRedirect(route('dashboard.produits.index'));

        $this->assertDatabaseHas('produits', ['nom' => 'Cadeau', 'prix_vente' => 0]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_update_modifie_le_produit(): void
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        $produit = Produit::create([
            'nom' => 'Ancien Nom', 'prix_vente' => 3000, 'prix_achat' => 0,
            'stock' => 5, 'seuil_alerte' => 1, 'unite' => 'u', 'actif' => true,
        ]);

        $this->actingAs($user)
            ->put(route('dashboard.produits.update', $produit), [
                'nom'          => 'Nouveau Nom',
                'prix_vente'   => 4000,
                'seuil_alerte' => 2,
                'unite'        => 'u',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('produits', ['nom' => 'Nouveau Nom', 'prix_vente' => 4000]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_supprime_le_produit(): void
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        $produit = Produit::create([
            'nom' => 'À Supprimer', 'prix_vente' => 1000, 'prix_achat' => 0,
            'stock' => 1, 'seuil_alerte' => 0, 'unite' => 'u', 'actif' => true,
        ]);

        $this->actingAs($user)
            ->delete(route('dashboard.produits.destroy', $produit))
            ->assertRedirect();

        $this->assertSoftDeleted('produits', ['id' => $produit->id]);
    }
}
