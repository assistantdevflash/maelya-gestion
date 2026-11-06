<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategorieProduit;
use App\Models\Commande;
use App\Models\CommandeItem;
use App\Models\Institut;
use App\Models\Produit;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Commandes boutique : numérotation, transitions, encaissement, compteurs,
 * confidentialité du suivi public et cohérence des montants.
 */
class CommandeModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Institut $institut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->creerAdmin(['role' => 'admin']);
        $this->institut = Institut::findOrFail($this->admin->institut_id);
    }

    private function commande(array $attributs = []): Commande
    {
        return Commande::create(array_merge([
            'institut_id'      => $this->institut->id,
            'client_prenom'    => 'Zaouri',
            'client_nom'       => 'Basile',
            'client_telephone' => '0748606366',
            'client_adresse'   => 'Bingerville, rue 12',
            'sous_total'       => 5000,
            'frais_livraison'  => 1500,
            'total'            => 6500,
            'statut'           => 'nouvelle',
            'mode_paiement'    => 'cash',
            'payee'            => false,
        ], $attributs));
    }

    private function produit(int $stock = 10): Produit
    {
        $categorie = CategorieProduit::create([
            'institut_id' => $this->institut->id,
            'nom'         => 'Épicerie',
        ]);

        return Produit::create([
            'institut_id'  => $this->institut->id,
            'categorie_id' => $categorie->id,
            'nom'          => 'Riz 5 kg',
            'prix_vente'   => 4500,
            'prix_achat'   => 3800,
            'stock'        => $stock,
            'seuil_alerte' => 1,
            'actif'        => true,
        ]);
    }

    // ─── Numérotation ────────────────────────────────────────────────

    public function test_la_numerotation_est_sequentielle(): void
    {
        $premiere = $this->commande();
        $seconde  = $this->commande();

        $prefixe = 'CMD-' . now()->format('Ymd') . '-';

        $this->assertSame($prefixe . '0001', $premiere->numero);
        $this->assertSame($prefixe . '0002', $seconde->numero);
    }

    // ─── Transitions de statut ───────────────────────────────────────

    public function test_une_transition_de_statut_non_autorisee_est_refusee(): void
    {
        $commande = $this->commande(['statut' => 'nouvelle']);

        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.statut', $commande), ['statut' => 'livree'])
            ->assertSessionHas('error');

        $this->assertSame('nouvelle', $commande->fresh()->statut);
    }

    public function test_le_statut_avance_dans_l_ordre_et_horodate(): void
    {
        $commande = $this->commande(['statut' => 'nouvelle']);

        foreach (['acceptee', 'en_preparation', 'en_livraison', 'livree'] as $statut) {
            $this->actingAs($this->admin)
                ->post(route('dashboard.boutique.commandes.statut', $commande), ['statut' => $statut])
                ->assertSessionHas('success');

            $this->assertSame($statut, $commande->fresh()->statut);
        }

        $this->assertNotNull($commande->fresh()->livree_at);
    }

    // ─── Compteurs ───────────────────────────────────────────────────

    public function test_les_compteurs_se_mettent_a_jour_immediatement(): void
    {
        $commande = $this->commande(['statut' => 'nouvelle']);

        $avant = $this->actingAs($this->admin)
            ->get(route('dashboard.boutique.commandes.index'))
            ->viewData('stats');

        $this->assertSame(1, $avant['nouvelles']);

        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.statut', $commande), ['statut' => 'acceptee']);

        $apres = $this->actingAs($this->admin)
            ->get(route('dashboard.boutique.commandes.index'))
            ->viewData('stats');

        $this->assertSame(0, $apres['nouvelles']);
        $this->assertSame(1, $apres['en_cours']);
    }

    // ─── Encaissement ────────────────────────────────────────────────

    public function test_marquer_payee_cree_la_vente_avec_le_bon_mode_de_paiement(): void
    {
        $produit = $this->produit(10);
        $commande = $this->commande([
            'statut'         => 'livree',
            'mode_paiement'  => 'mobile_money',
            'sous_total'     => 13500,
            'frais_livraison' => 0,
            'total'          => 13500,
        ]);

        CommandeItem::create([
            'commande_id'   => $commande->id,
            'produit_id'    => $produit->id,
            'nom_snapshot'  => $produit->nom,
            'prix_snapshot' => 4500,
            'quantite'      => 3,
            'sous_total'    => 13500,
        ]);

        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.payer', $commande))
            ->assertSessionHas('success');

        $vente = Vente::withoutGlobalScopes()->firstOrFail();

        $this->assertSame('mobile_money', $vente->mode_paiement);
        $this->assertSame(13500, (int) $vente->total);
        $this->assertSame(0, (int) $vente->montant_cash);
        $this->assertSame(7, $produit->fresh()->stock);
        $this->assertTrue($commande->fresh()->payee);
        $this->assertNotNull($commande->fresh()->payee_at);
    }

    public function test_une_commande_non_livree_ne_peut_pas_etre_encaissee(): void
    {
        $commande = $this->commande(['statut' => 'en_preparation']);

        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.payer', $commande))
            ->assertSessionHas('error');

        $this->assertSame(0, Vente::withoutGlobalScopes()->count());
        $this->assertFalse($commande->fresh()->payee);
    }

    // ─── Confidentialité du suivi public ─────────────────────────────

    public function test_le_suivi_de_commande_exige_un_lien_signe(): void
    {
        $commande = $this->commande();

        // Sans signature : accès refusé (le numéro est séquentiel, donc devinable)
        $this->get(route('shop.suivi', [
            'slug'   => $this->institut->slug,
            'numero' => $commande->numero,
        ]))->assertForbidden();

        // Avec le lien signé : la page s'affiche
        $url = URL::signedRoute('shop.suivi', [
            'slug'   => $this->institut->slug,
            'numero' => $commande->numero,
        ]);

        $this->get($url)->assertOk()->assertSee($commande->numero);
    }

    // ─── Cohérence des montants ──────────────────────────────────────

    public function test_la_modification_recalcule_le_total_sur_toutes_les_lignes(): void
    {
        $produit = $this->produit();
        $commande = $this->commande(['sous_total' => 9000, 'frais_livraison' => 0, 'total' => 9000]);

        $ligne1 = CommandeItem::create([
            'commande_id' => $commande->id, 'produit_id' => $produit->id,
            'nom_snapshot' => 'Riz', 'prix_snapshot' => 4500, 'quantite' => 1, 'sous_total' => 4500,
        ]);
        CommandeItem::create([
            'commande_id' => $commande->id, 'produit_id' => $produit->id,
            'nom_snapshot' => 'Riz', 'prix_snapshot' => 4500, 'quantite' => 1, 'sous_total' => 4500,
        ]);

        // Le formulaire ne renvoie qu'une ligne (page périmée) : le total doit
        // rester celui de toutes les lignes présentes en base.
        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.update', $commande), [
                'items'           => [['id' => $ligne1->id, 'quantite' => 1]],
                'frais_livraison' => 0,
            ])
            ->assertSessionHas('success');

        $commande->refresh();
        $this->assertSame(2, $commande->items()->count());
        $this->assertSame(9000, (int) $commande->sous_total);
        $this->assertSame(9000, (int) $commande->total);
    }

    public function test_une_commande_ne_peut_pas_perdre_tous_ses_articles(): void
    {
        $produit = $this->produit();
        $commande = $this->commande();

        $ligne = CommandeItem::create([
            'commande_id' => $commande->id, 'produit_id' => $produit->id,
            'nom_snapshot' => 'Riz', 'prix_snapshot' => 4500, 'quantite' => 1, 'sous_total' => 4500,
        ]);

        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.update', $commande), [
                'items' => [['id' => $ligne->id, 'quantite' => 0]],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(1, $commande->items()->count());
    }

    // ─── Liste et export ─────────────────────────────────────────────

    public function test_la_liste_filtre_par_statut_et_recherche(): void
    {
        $a = $this->commande(['statut' => 'nouvelle', 'client_nom' => 'Kone']);
        $this->commande(['statut' => 'livree', 'client_nom' => 'Traore']);

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.boutique.commandes.index', ['statut' => 'livree']))
            ->assertOk();
        $this->assertSame(1, $reponse->viewData('commandes')->total());

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.boutique.commandes.index', ['search' => 'Kone']))
            ->assertOk();
        $this->assertSame(1, $reponse->viewData('commandes')->total());
        $this->assertSame($a->id, $reponse->viewData('commandes')->first()->id);
    }

    public function test_l_export_applique_la_recherche(): void
    {
        $this->commande(['client_nom' => 'Kone']);
        $this->commande(['client_nom' => 'Traore']);

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.boutique.commandes.export', ['search' => 'Kone']));

        $reponse->assertOk();

        $contenu = $reponse->streamedContent();
        $this->assertStringContainsString('Kone', $contenu);
        $this->assertStringNotContainsString('Traore', $contenu);
    }

    public function test_seule_une_commande_annulee_ou_refusee_peut_etre_supprimee(): void
    {
        $enCours = $this->commande(['statut' => 'acceptee']);
        $annulee = $this->commande(['statut' => 'annulee']);

        $this->actingAs($this->admin)
            ->delete(route('dashboard.boutique.commandes.destroy', $enCours))
            ->assertSessionHas('error');
        $this->assertNotNull(Commande::withoutGlobalScopes()->find($enCours->id));

        $this->actingAs($this->admin)
            ->delete(route('dashboard.boutique.commandes.destroy', $annulee))
            ->assertRedirect(route('dashboard.boutique.commandes.index'));
        $this->assertNull(Commande::withoutGlobalScopes()->find($annulee->id));
    }

    // ─── Réservation du stock ────────────────────────────────────────

    private function commandeAvecProduit(int $quantite = 3, int $stock = 10): array
    {
        $produit = $this->produit($stock);
        $commande = $this->commande(['statut' => 'nouvelle', 'sous_total' => 4500 * $quantite, 'total' => 4500 * $quantite]);

        CommandeItem::create([
            'commande_id'   => $commande->id,
            'produit_id'    => $produit->id,
            'nom_snapshot'  => $produit->nom,
            'prix_snapshot' => 4500,
            'quantite'      => $quantite,
            'sous_total'    => 4500 * $quantite,
        ]);

        return [$commande, $produit];
    }

    public function test_accepter_une_commande_reserve_le_stock(): void
    {
        [$commande, $produit] = $this->commandeAvecProduit(3, 10);

        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.statut', $commande), ['statut' => 'acceptee'])
            ->assertSessionHas('success');

        $this->assertSame(7, $produit->fresh()->stock);
        $this->assertTrue($commande->fresh()->stockReserve());
    }

    public function test_accepter_echoue_si_le_stock_est_insuffisant(): void
    {
        [$commande, $produit] = $this->commandeAvecProduit(5, 2);

        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.statut', $commande), ['statut' => 'acceptee'])
            ->assertSessionHas('error');

        $this->assertSame('nouvelle', $commande->fresh()->statut);
        $this->assertSame(2, $produit->fresh()->stock);
        $this->assertFalse($commande->fresh()->stockReserve());
    }

    public function test_refuser_une_commande_acceptee_remet_le_stock_en_rayon(): void
    {
        [$commande, $produit] = $this->commandeAvecProduit(4, 10);

        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.statut', $commande), ['statut' => 'acceptee']);
        $this->assertSame(6, $produit->fresh()->stock);

        // Annulation depuis « acceptée »
        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.statut', $commande), ['statut' => 'annulee'])
            ->assertSessionHas('success');

        $this->assertSame(10, $produit->fresh()->stock);
        $this->assertFalse($commande->fresh()->stockReserve());
        $this->assertSame('admin', $commande->fresh()->annulee_par);
    }

    // ─── Annulation par le client ────────────────────────────────────

    public function test_le_client_peut_annuler_sa_commande_depuis_son_lien_signe(): void
    {
        [$commande, $produit] = $this->commandeAvecProduit(2, 10);

        // On la passe en « acceptée » pour vérifier que le stock est bien rendu
        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.statut', $commande), ['statut' => 'acceptee']);
        $this->assertSame(8, $produit->fresh()->stock);

        $url = URL::signedRoute('shop.annuler', [
            'slug'   => $this->institut->slug,
            'numero' => $commande->numero,
        ]);

        // Sans signature, l'annulation est impossible
        $this->post(route('shop.annuler', ['slug' => $this->institut->slug, 'numero' => $commande->numero]))
            ->assertForbidden();

        $this->post($url)->assertRedirect();

        $commande->refresh();
        $this->assertSame('annulee', $commande->statut);
        $this->assertSame('client', $commande->annulee_par);
        $this->assertSame(10, $produit->fresh()->stock);
    }

    public function test_un_client_ne_peut_pas_annuler_une_commande_expediee(): void
    {
        $commande = $this->commande(['statut' => 'en_livraison']);

        $url = URL::signedRoute('shop.annuler', [
            'slug'   => $this->institut->slug,
            'numero' => $commande->numero,
        ]);

        $this->post($url)->assertSessionHas('error');
        $this->assertSame('en_livraison', $commande->fresh()->statut);
    }

    // ─── Actions groupées ────────────────────────────────────────────

    public function test_les_actions_groupees_acceptent_plusieurs_commandes(): void
    {
        [$a, $produitA] = $this->commandeAvecProduit(1, 10);
        [$b, $produitB] = $this->commandeAvecProduit(2, 10);

        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.actions-groupees'), [
                'ids'    => [$a->id, $b->id],
                'action' => 'accepter',
            ])
            ->assertSessionHas('success');

        $this->assertSame('acceptee', $a->fresh()->statut);
        $this->assertSame('acceptee', $b->fresh()->statut);
        $this->assertSame(9, $produitA->fresh()->stock);
        $this->assertSame(8, $produitB->fresh()->stock);
    }

    public function test_les_actions_groupees_ignorent_les_commandes_deja_traitees(): void
    {
        $traitee = $this->commande(['statut' => 'livree']);
        $nouvelle = $this->commande(['statut' => 'nouvelle']);

        $this->actingAs($this->admin)
            ->post(route('dashboard.boutique.commandes.actions-groupees'), [
                'ids'    => [$traitee->id, $nouvelle->id],
                'action' => 'refuser',
            ]);

        $this->assertSame('livree', $traitee->fresh()->statut);
        $this->assertSame('refusee', $nouvelle->fresh()->statut);
    }

    // ─── Relance panier abandonné ───────────────────────────────────

    public function test_la_relance_des_commandes_non_confirmees_part_une_seule_fois(): void
    {
        Mail::fake();

        $commande = $this->commande([
            'statut'       => 'nouvelle',
            'client_email' => 'client@example.com',
        ]);
        $commande->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->artisan('commandes:relances')->assertSuccessful();
        Mail::assertSent(\App\Mail\CommandeRelanceClient::class, 1);

        $this->artisan('commandes:relances')->assertSuccessful();
        Mail::assertSent(\App\Mail\CommandeRelanceClient::class, 1);

        $this->assertNotNull($commande->fresh()->relance_envoyee_at);
    }

    public function test_une_commande_recente_n_est_pas_relancee(): void
    {
        Mail::fake();

        $this->commande(['statut' => 'nouvelle', 'client_email' => 'client@example.com']);

        $this->artisan('commandes:relances')->assertSuccessful();

        Mail::assertNothingSent();
    }

    // ─── Documents, filtres, rendu ──────────────────────────────────

    public function test_le_bon_de_livraison_est_telechargeable(): void
    {
        $commande = $this->commande();

        $this->actingAs($this->admin)
            ->get(route('dashboard.boutique.commandes.bon-livraison', $commande))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_les_filtres_de_periode_et_d_email_fonctionnent(): void
    {
        $ancienne = $this->commande(['client_email' => 'vieux@example.com']);
        $ancienne->forceFill(['created_at' => now()->subDays(10)])->save();

        $recente = $this->commande(['client_email' => 'recent@example.com']);

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.boutique.commandes.index', ['du' => now()->subDays(2)->toDateString()]))
            ->assertOk();
        $this->assertSame(1, $reponse->viewData('commandes')->total());

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.boutique.commandes.index', ['search' => 'vieux@example.com']))
            ->assertOk();
        $this->assertSame($ancienne->id, $reponse->viewData('commandes')->first()->id);
    }

    public function test_la_fiche_affiche_l_historique_et_la_livraison_promise(): void
    {
        $commande = $this->commande([
            'statut'                => 'acceptee',
            'acceptee_at'           => now(),
            'date_livraison_prevue' => now()->addDays(2),
            'creneau_livraison'     => 'Matin (8h-12h)',
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.boutique.commandes.show', $commande))
            ->assertOk()
            ->assertSee('Historique')
            ->assertSee('Commande reçue')
            ->assertSee('Matin (8h-12h)')
            ->assertSee('Bon de livraison');
    }
}
