<?php

namespace Tests\Feature\Dashboard;

use App\Models\Commande;
use App\Models\CommandeItem;
use App\Models\Produit;
use App\Models\ProduitImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fiche d'une commande boutique.
 *
 * Bug corrigé : la carte « Paiement » était dupliquée (deux boutons
 * « Marquer comme payée ») car les deux blocs utilisaient la même condition
 * `statut === 'livree' && !payee`.
 */
class CommandeShowTest extends TestCase
{
    use RefreshDatabase;

    private function creerCommande(array $attrs = []): Commande
    {
        $admin = $this->creerAdmin();

        return Commande::create(array_merge([
            'institut_id'      => $admin->institut_id,
            'numero'           => 'CMD-TEST-' . uniqid(),
            'client_prenom'    => 'Basile',
            'client_nom'       => 'Tie',
            'client_telephone' => '0748606366',
            'client_adresse'   => 'Bingerville',
            'sous_total'       => 5000,
            'frais_livraison'  => 1500,
            'total'            => 6500,
            'statut'           => 'livree',
            'mode_paiement'    => 'cash',
            'payee'            => false,
        ], $attrs));
    }

    private function compterBoutons(string $html): int
    {
        // Les deux libellés historiques possibles
        return substr_count($html, 'Marquer comme payée') + substr_count($html, '>Marquer payée<');
    }

    public function test_une_seule_carte_paiement_pour_une_commande_livree_non_payee(): void
    {
        $commande = $this->creerCommande(['statut' => 'livree', 'payee' => false]);

        $response = $this->actingAs($commande->institut->proprietaire)
            ->get(route('dashboard.boutique.commandes.show', $commande));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, '>Paiement<'), 'La carte Paiement doit apparaître une seule fois.');
        $this->assertSame(1, $this->compterBoutons($html), 'Le bouton de paiement doit apparaître une seule fois.');
        $this->assertStringContainsString('Montant à encaisser', $html);
    }

    public function test_pas_de_bouton_de_paiement_pour_une_commande_non_livree(): void
    {
        $commande = $this->creerCommande(['statut' => 'en_preparation', 'payee' => false]);

        $response = $this->actingAs($commande->institut->proprietaire)
            ->get(route('dashboard.boutique.commandes.show', $commande));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, '>Paiement<'));
        $this->assertSame(0, $this->compterBoutons($html));
        $this->assertStringContainsString('une fois la commande livrée', $html);
    }

    public function test_commande_payee_affiche_l_etat_et_aucun_bouton(): void
    {
        $commande = $this->creerCommande([
            'statut'   => 'livree',
            'payee'    => true,
            'payee_at' => '2026-07-29 08:47:00',
        ]);

        $response = $this->actingAs($commande->institut->proprietaire)
            ->get(route('dashboard.boutique.commandes.show', $commande));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, '>Paiement<'));
        $this->assertSame(0, $this->compterBoutons($html));
        $this->assertStringContainsString('Commande payée', $html);
        // La colonne est `payee_at` (et non `payee_le`) : la date doit s'afficher.
        $this->assertStringContainsString('Payée le 29/07/2026 à 08:47', $html);
    }

    public function test_commande_payee_sans_date_n_affiche_pas_de_le_orphelin(): void
    {
        $commande = $this->creerCommande([
            'statut'   => 'livree',
            'payee'    => true,
            'payee_at' => null,
        ]);

        $response = $this->actingAs($commande->institut->proprietaire)
            ->get(route('dashboard.boutique.commandes.show', $commande));

        $response->assertOk();

        // Ancien bug : « Le » restait affiché seul, sans date derrière.
        $this->assertDoesNotMatchRegularExpression('/>\s*Le\s*</u', $response->getContent());
    }

    // ── Colonne image ────────────────────────────────────────────────────────

    private function ajouterItem(Commande $commande, ?Produit $produit, string $nom = 'Livret CP2'): CommandeItem
    {
        return CommandeItem::create([
            'commande_id'   => $commande->id,
            'produit_id'    => $produit?->id,
            'nom_snapshot'  => $nom,
            'prix_snapshot' => 5000,
            'quantite'      => 1,
            'sous_total'    => 5000,
        ]);
    }

    public function test_la_ligne_affiche_la_miniature_du_produit(): void
    {
        $commande = $this->creerCommande(['payee' => true]);
        $produit  = Produit::create([
            'institut_id' => $commande->institut_id,
            'nom'         => 'Livret CP2',
            'prix_achat'  => 3000,
            'prix_vente'  => 5000,
            'stock'       => 10,
            'seuil_alerte'=> 1,
            'unite'       => 'pièce',
            'actif'       => true,
        ]);
        ProduitImage::create([
            'produit_id'    => $produit->id,
            'chemin'        => 'produits/livret-cp2.jpg',
            'ordre'         => 1,
            'is_principale' => true,
        ]);

        $this->ajouterItem($commande, $produit);

        $response = $this->actingAs($commande->institut->proprietaire)
            ->get(route('dashboard.boutique.commandes.show', $commande));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('>Image</th>', $html);
        $this->assertStringContainsString('produits/livret-cp2.jpg', $html);
        $this->assertStringContainsString('alt="Livret CP2"', $html);
    }

    public function test_produit_sans_image_affiche_un_placeholder(): void
    {
        $commande = $this->creerCommande(['payee' => true]);
        $produit  = Produit::create([
            'institut_id' => $commande->institut_id,
            'nom'         => 'Sans photo',
            'prix_achat'  => 1000,
            'prix_vente'  => 2000,
            'stock'       => 3,
            'seuil_alerte'=> 1,
            'unite'       => 'u',
            'actif'       => true,
        ]);

        $this->ajouterItem($commande, $produit);

        $response = $this->actingAs($commande->institut->proprietaire)
            ->get(route('dashboard.boutique.commandes.show', $commande));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('Aucune image', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function test_produit_supprime_ne_casse_pas_la_ligne(): void
    {
        $commande = $this->creerCommande(['payee' => true]);
        // produit_id reste NULL : le produit a été supprimé après la commande
        $this->ajouterItem($commande, null);

        $response = $this->actingAs($commande->institut->proprietaire)
            ->get(route('dashboard.boutique.commandes.show', $commande));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('Livret CP2', $html);
        $this->assertStringContainsString('Produit supprimé', $html);
        $this->assertStringContainsString('Aucune image', $html);
    }
}
