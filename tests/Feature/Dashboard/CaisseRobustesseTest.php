<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Caisse;
use App\Models\CategorieProduit;
use App\Models\Produit;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Robustesse de la caisse : cohérence du stock, du cache et des erreurs.
 */
class CaisseRobustesseTest extends TestCase
{
    use RefreshDatabase;

    private function creerProduit($admin, string $nom, int $prix, int $stock): Produit
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

    private function panier(Produit $produit, int $quantite): array
    {
        return [[
            'type'     => 'produit',
            'id'       => $produit->id,
            'nom'      => $produit->nom,
            'prix'     => (int) $produit->prix_vente,
            'quantite' => $quantite,
        ]];
    }

    public function test_une_vente_purge_le_cache_du_catalogue(): void
    {
        $admin = $this->creerAdmin();
        $produit = $this->creerProduit($admin, 'Shampooing', 3000, 10);

        $cle = 'caisse_catalog_' . $admin->institut_id;
        Cache::put($cle, ['peu-importe' => true], 3600);
        $this->assertNotNull(Cache::get($cle));

        $this->actingAs($admin)
            ->post(route('dashboard.ventes.store'), [
                'panier_json'   => json_encode($this->panier($produit, 2)),
                'mode_paiement' => 'cash',
                'total'         => 6000,
            ])
            ->assertRedirect();

        $this->assertSame(1, Vente::count());
        $this->assertNull(Cache::get($cle), 'Le cache du catalogue doit être purgé après une vente.');
    }

    public function test_stock_insuffisant_renvoie_une_erreur_lisible_sans_creer_de_vente(): void
    {
        $admin = $this->creerAdmin();
        $produit = $this->creerProduit($admin, 'Parfum', 8000, 1);

        $reponse = $this->actingAs($admin)
            ->from(route('dashboard.caisse'))
            ->post(route('dashboard.ventes.store'), [
                'panier_json'   => json_encode($this->panier($produit, 5)),
                'mode_paiement' => 'cash',
                'total'         => 40000,
            ]);

        $reponse->assertRedirect(route('dashboard.caisse'));
        $reponse->assertSessionHas('error');

        $this->assertSame(0, Vente::count(), 'Aucune vente ne doit être créée.');
        $this->assertSame(1, (int) $produit->fresh()->stock, 'Le stock doit rester intact.');
    }

    public function test_l_annulation_restaure_le_stock_et_purge_le_cache(): void
    {
        $admin = $this->creerAdmin();
        $produit = $this->creerProduit($admin, 'Crème', 4000, 10);

        $this->actingAs($admin)
            ->post(route('dashboard.ventes.store'), [
                'panier_json'   => json_encode($this->panier($produit, 3)),
                'mode_paiement' => 'cash',
                'total'         => 12000,
            ])
            ->assertRedirect();

        $vente = Vente::firstOrFail();
        $this->assertSame(7, (int) $produit->fresh()->stock);

        $cle = 'caisse_catalog_' . $admin->institut_id;
        Cache::put($cle, ['peu-importe' => true], 3600);

        $this->actingAs($admin)
            ->post(route('dashboard.ventes.annuler', $vente), [
                'motif_annulation' => 'Erreur de saisie',
            ])
            ->assertRedirect();

        $this->assertSame(10, (int) $produit->fresh()->stock, 'Le stock doit être restauré.');
        $this->assertNull(Cache::get($cle), 'Le cache du catalogue doit être purgé après annulation.');
    }

    public function test_l_annulation_en_un_clic_cible_la_derniere_vente(): void
    {
        $admin = $this->creerAdmin();
        $produit = $this->creerProduit($admin, 'Gel douche', 2500, 20);

        foreach ([1, 2] as $quantite) {
            $this->actingAs($admin)
                ->post(route('dashboard.ventes.store'), [
                    'panier_json'   => json_encode($this->panier($produit, $quantite)),
                    'mode_paiement' => 'cash',
                    'total'         => 2500 * $quantite,
                ])
                ->assertRedirect();
        }

        $ventes = Vente::orderBy('created_at')->get();
        $this->assertCount(2, $ventes);
        $this->assertSame(17, (int) $produit->fresh()->stock);

        $this->actingAs($admin)
            ->post(route('dashboard.ventes.annuler-derniere'))
            ->assertRedirect();

        $this->assertSame('validee', $ventes[0]->fresh()->statut, 'La première vente reste valide.');
        $this->assertSame('annulee', $ventes[1]->fresh()->statut, 'Seule la dernière vente est annulée.');
        $this->assertSame(19, (int) $produit->fresh()->stock, 'Le stock de la dernière vente est rendu.');
    }

    public function test_un_employe_ne_peut_pas_annuler_la_derniere_vente(): void
    {
        $admin = $this->creerAdmin();
        $employe = $this->creerAdmin(['role' => 'employe']);

        $this->actingAs($employe)
            ->post(route('dashboard.ventes.annuler-derniere'))
            ->assertForbidden();
    }

    public function test_le_detail_de_la_derniere_vente_est_expose_pour_la_confirmation(): void
    {
        $admin = $this->creerAdmin();
        $produit = $this->creerProduit($admin, 'Baume corps', 6500, 10);

        $this->actingAs($admin)
            ->post(route('dashboard.ventes.store'), [
                'panier_json'   => json_encode($this->panier($produit, 2)),
                'mode_paiement' => 'cash',
                'total'         => 13000,
            ])
            ->assertRedirect();

        $reponse = $this->actingAs($admin)->getJson(route('dashboard.ventes.derniere-json'));

        $reponse->assertOk();

        $vente = $reponse->json('vente');
        $this->assertNotNull($vente);
        $this->assertSame(13000, $vente['total']);
        $this->assertSame('Espèces', $vente['mode_paiement_lib']);
        $this->assertSame(2, $vente['nb_articles']);
        $this->assertCount(1, $vente['items']);
        $this->assertSame('Baume corps', $vente['items'][0]['nom']);
        $this->assertSame(2, $vente['items'][0]['quantite']);
        $this->assertSame(13000, $vente['items'][0]['sous_total']);
        $this->assertNotEmpty($vente['date']);
    }

    public function test_le_detail_de_la_derniere_vente_est_vide_sans_vente_recente(): void
    {
        $admin = $this->creerAdmin();

        $reponse = $this->actingAs($admin)->getJson(route('dashboard.ventes.derniere-json'));

        $reponse->assertOk();
        $this->assertNull($reponse->json('vente'));
        $this->assertNotEmpty($reponse->json('message'));
    }

    public function test_le_detail_de_la_derniere_vente_est_interdit_aux_employes(): void
    {
        $employe = $this->creerAdmin(['role' => 'employe']);

        $this->actingAs($employe)
            ->getJson(route('dashboard.ventes.derniere-json'))
            ->assertForbidden();
    }

    public function test_la_purge_du_cache_est_exposee_en_statique(): void
    {
        $admin = $this->creerAdmin();

        Cache::put('caisse_catalog_' . $admin->institut_id, ['x'], 3600);
        Cache::put('caisse_produits_simple_' . $admin->institut_id, ['x'], 3600);

        Caisse::purgerCache($admin->institut_id);

        $this->assertNull(Cache::get('caisse_catalog_' . $admin->institut_id));
        $this->assertNull(Cache::get('caisse_produits_simple_' . $admin->institut_id));
    }
}
