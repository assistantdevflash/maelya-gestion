<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Caisse;
use App\Models\CategorieProduit;
use App\Models\Produit;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Confort d'utilisation de la caisse : ordre du catalogue, panier vide,
 * annulation rapide, pas de bannière PWA par-dessus le panier.
 */
class CaisseExperienceTest extends TestCase
{
    use RefreshDatabase;

    private function produit($admin, string $nom, int $prix, int $stock = 50): Produit
    {
        $cat = CategorieProduit::firstOrCreate(
            ['institut_id' => $admin->institut_id, 'nom' => 'Divers'],
            ['ordre' => 1]
        );

        return Produit::create([
            'institut_id'  => $admin->institut_id,
            'categorie_id' => $cat->id,
            'nom'          => $nom,
            'prix_vente'   => $prix,
            'prix_achat'   => (int) round($prix * 0.8),
            'stock'        => $stock,
            'seuil_alerte' => 1,
            'actif'        => true,
        ]);
    }

    private function vendre($admin, Produit $produit, int $quantite): void
    {
        $this->actingAs($admin)->post(route('dashboard.ventes.store'), [
            'panier_json' => json_encode([[
                'type'     => 'produit',
                'id'       => $produit->id,
                'nom'      => $produit->nom,
                'prix'     => (int) $produit->prix_vente,
                'quantite' => $quantite,
            ]]),
            'mode_paiement' => 'cash',
            'total'         => (int) $produit->prix_vente * $quantite,
        ])->assertRedirect();
    }

    public function test_les_produits_les_plus_vendus_passent_en_tete_du_catalogue(): void
    {
        $admin = $this->creerAdmin();

        $rare  = $this->produit($admin, 'Article rare', 1000);
        $star  = $this->produit($admin, 'Article star', 2000);
        $moyen = $this->produit($admin, 'Article moyen', 1500);

        $this->vendre($admin, $star, 9);
        $this->vendre($admin, $moyen, 4);
        $this->vendre($admin, $rare, 1);

        Caisse::purgerCache($admin->institut_id);

        $produits = Livewire::actingAs($admin)
            ->test(Caisse::class)
            ->viewData('produits');

        $this->assertSame(
            ['Article star', 'Article moyen', 'Article rare'],
            collect($produits)->pluck('nom')->take(3)->all()
        );
    }

    public function test_le_panier_vide_propose_les_derniers_vendus(): void
    {
        $admin = $this->creerAdmin();
        $produit = $this->produit($admin, 'Huile de coco', 3500);

        $this->vendre($admin, $produit, 1);
        Caisse::purgerCache($admin->institut_id);

        $composant = Livewire::actingAs($admin)->test(Caisse::class);

        $derniers = $composant->viewData('derniersVendus');
        $this->assertNotEmpty($derniers);
        $this->assertSame('Huile de coco', $derniers->first()['nom']);
        $this->assertSame('produit', $derniers->first()['type']);

        $composant->assertSee('Derniers vendus')
            ->assertSee('Vente libre');
    }

    public function test_le_bouton_d_annulation_rapide_est_reserve_a_l_admin(): void
    {
        $admin = $this->creerAdmin();
        $employe = $this->creerAdmin(['role' => 'employe']);

        $this->actingAs($admin)
            ->get(route('dashboard.caisse'))
            ->assertOk()
            ->assertSee('Annuler la dernière vente');

        $this->actingAs($employe)
            ->get(route('dashboard.caisse'))
            ->assertOk()
            ->assertDontSee('Annuler la dernière vente');
    }

    public function test_la_caisse_est_marquee_pour_ne_pas_afficher_la_banniere_pwa(): void
    {
        $admin = $this->creerAdmin();

        $this->actingAs($admin)
            ->get(route('dashboard.caisse'))
            ->assertOk()
            ->assertSee('data-caisse-pos', false);
    }

    public function test_les_derniers_vendus_ignorent_les_produits_desactives(): void
    {
        $admin = $this->creerAdmin();
        $produit = $this->produit($admin, 'Produit retiré', 1200);

        $this->vendre($admin, $produit, 2);
        $produit->update(['actif' => false]);

        Caisse::purgerCache($admin->institut_id);

        $derniers = Livewire::actingAs($admin)
            ->test(Caisse::class)
            ->viewData('derniersVendus');

        $this->assertTrue(
            $derniers->isEmpty(),
            'Un produit retiré du catalogue ne doit pas réapparaître en raccourci.'
        );
    }

    public function test_les_ventes_annulees_ne_comptent_pas_dans_les_derniers_vendus(): void
    {
        $admin = $this->creerAdmin();
        $produit = $this->produit($admin, 'Savon', 800);

        $this->vendre($admin, $produit, 1);
        $vente = Vente::firstOrFail();

        $this->actingAs($admin)
            ->post(route('dashboard.ventes.annuler', $vente), ['motif_annulation' => 'Test'])
            ->assertRedirect();

        $derniers = Livewire::actingAs($admin)
            ->test(Caisse::class)
            ->viewData('derniersVendus');

        $this->assertTrue($derniers->isEmpty());
    }
}
