<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategorieProduit;
use App\Models\Devis;
use App\Models\Facture;
use App\Models\Paiement;
use App\Models\PlanAbonnement;
use App\Models\Produit;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Module Devis & Factures : cycle de vie complet.
 *
 * Ces tests couvrent la numérotation, les totaux, la transformation
 * devis → facture, les paiements, l'annulation et les indicateurs.
 */
class DevisFactureModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->creerAdmin(['role' => 'admin']);

        $plan = PlanAbonnement::create([
            'nom' => 'Ultra', 'slug' => 'ultra', 'duree_type' => 'mensuel',
            'duree_jours' => 30, 'prix' => 10000, 'actif' => true,
        ]);
        $this->admin->abonnements()->update(['plan_id' => $plan->id]);
    }

    // ─── Utilitaires ──────────────────────────────────────────────────

    private function produit(int $stock = 10): Produit
    {
        $categorie = CategorieProduit::create([
            'institut_id' => $this->admin->institut_id,
            'nom'         => 'Soins',
        ]);

        return Produit::create([
            'institut_id'  => $this->admin->institut_id,
            'categorie_id' => $categorie->id,
            'nom'          => 'Shampooing',
            'prix_vente'   => 3000,
            'prix_achat'   => 2000,
            'stock'        => $stock,
            'seuil_alerte' => 1,
            'actif'        => true,
        ]);
    }

    private function creerDevis(array $options = []): Devis
    {
        $lignes = $options['lignes'] ?? [[
            'designation'   => 'Coupe homme',
            'quantite'      => 2,
            'prix_unitaire' => 5000,
        ]];

        $this->actingAs($this->admin)
            ->post(route('dashboard.devis.store'), array_merge([
                'client_prenom'    => 'Awa',
                'client_nom'       => 'Traoré',
                'client_telephone' => '0701020304',
                'client_email'     => 'awa@example.com',
                'date_creation'    => now()->toDateString(),
                'date_expiration'  => now()->addDays(30)->toDateString(),
                'lignes'           => json_encode($lignes),
                'tva_applicable'   => 0,
            ], $options['payload'] ?? []))
            ->assertRedirect();

        return Devis::withoutGlobalScopes()->orderByDesc('numero')->firstOrFail();
    }

    private function creerFacture(array $options = []): Facture
    {
        $lignes = $options['lignes'] ?? [[
            'designation'   => 'Coupe homme',
            'quantite'      => 2,
            'prix_unitaire' => 5000,
        ]];

        $this->actingAs($this->admin)
            ->post(route('dashboard.factures.store'), array_merge([
                'client_prenom'    => 'Awa',
                'client_nom'       => 'Traoré',
                'client_telephone' => '0701020304',
                'date_emission'    => now()->toDateString(),
                'date_echeance'    => now()->addDays(30)->toDateString(),
                'lignes'           => json_encode($lignes),
                'tva_applicable'   => 0,
            ], $options['payload'] ?? []))
            ->assertRedirect();

        return Facture::withoutGlobalScopes()->orderByDesc('numero')->firstOrFail();
    }

    // ─── Devis ────────────────────────────────────────────────────────

    public function test_creation_d_un_devis_calcule_les_totaux_et_numerote(): void
    {
        $devis = $this->creerDevis();

        $this->assertSame('DEV-' . now()->format('Ymd') . '-000001', $devis->numero);
        $this->assertSame('brouillon', $devis->statut);
        $this->assertSame(10000, $devis->sous_total);
        $this->assertSame(10000, $devis->total_ttc);
        $this->assertSame(1, $devis->items()->count());
    }

    public function test_la_numerotation_des_devis_est_sequentielle_et_par_institut(): void
    {
        $premier  = $this->creerDevis();
        $second   = $this->creerDevis();

        $this->assertSame('000001', substr($premier->numero, -6));
        $this->assertSame('000002', substr($second->numero, -6));

        $autre = $this->creerAdmin(['role' => 'admin']);
        $plan  = PlanAbonnement::where('slug', 'ultra')->first();
        $autre->abonnements()->update(['plan_id' => $plan->id]);

        $this->actingAs($autre)->post(route('dashboard.devis.store'), [
            'client_prenom' => 'B', 'client_nom' => 'C', 'client_telephone' => '0709',
            'date_creation' => now()->toDateString(),
            'date_expiration' => now()->addDays(10)->toDateString(),
            'lignes' => json_encode([['designation' => 'X', 'quantite' => 1, 'prix_unitaire' => 1000]]),
        ])->assertRedirect();

        $devisAutre = Devis::withoutGlobalScopes()->where('institut_id', $autre->institut_id)->firstOrFail();
        $this->assertSame('000001', substr($devisAutre->numero, -6));
    }

    public function test_une_ligne_de_devis_conserve_l_article_du_catalogue(): void
    {
        $produit = $this->produit();

        $devis = $this->creerDevis(['lignes' => [[
            'designation'   => $produit->nom,
            'quantite'      => 1,
            'prix_unitaire' => 3000,
            'produit_id'    => $produit->id,
        ]]]);

        $this->assertSame($produit->id, $devis->items()->first()->produit_id);
    }

    public function test_la_modification_d_un_devis_recalcule_les_totaux(): void
    {
        $devis = $this->creerDevis();

        $this->actingAs($this->admin)
            ->put(route('dashboard.devis.update', $devis), [
                'date_expiration' => now()->addDays(15)->toDateString(),
                'tva_applicable'  => 0,
                'lignes'          => json_encode([[
                    'designation' => 'Coupe homme', 'quantite' => 3, 'prix_unitaire' => 4000,
                ]]),
            ])
            ->assertRedirect();

        $devis->refresh();
        $this->assertSame(12000, $devis->total_ttc);
        $this->assertSame(1, $devis->items()->count());
    }

    public function test_un_devis_se_transforme_en_facture_une_seule_fois(): void
    {
        $produit = $this->produit();
        $devis = $this->creerDevis(['lignes' => [[
            'designation' => $produit->nom, 'quantite' => 2, 'prix_unitaire' => 3000,
            'produit_id' => $produit->id,
        ]]]);

        $this->actingAs($this->admin)
            ->post(route('dashboard.devis.transformer', $devis))
            ->assertRedirect();

        $devis->refresh();
        $facture = Facture::withoutGlobalScopes()->where('devis_id', $devis->id)->firstOrFail();

        $this->assertSame('accepte', $devis->statut);
        $this->assertSame($devis->total_ttc, $facture->total_ttc);
        $this->assertSame($produit->id, $facture->items()->first()->produit_id);

        // Deuxième tentative : refusée, aucune facture supplémentaire
        $this->actingAs($this->admin)
            ->post(route('dashboard.devis.transformer', $devis))
            ->assertRedirect();

        $this->assertSame(1, Facture::withoutGlobalScopes()->where('devis_id', $devis->id)->count());
    }

    public function test_le_pdf_public_refuse_un_brouillon(): void
    {
        $devis = $this->creerDevis();

        $this->get(route('devis.public', ['id' => $devis->id, 'token' => $devis->token]))->assertNotFound();

        $this->actingAs($this->admin)->post(route('dashboard.devis.envoyer', $devis));

        $devis->refresh();
        $this->get(route('devis.public', ['id' => $devis->id, 'token' => $devis->token]))->assertOk();
    }

    public function test_le_pdf_public_exige_le_bon_token(): void
    {
        $devis = $this->creerDevis();
        $devis->update(['statut' => 'envoye']);

        // Sans token (ou avec un token erroné), le document n'est pas exposé.
        $this->get(route('devis.public', ['id' => $devis->id]))->assertNotFound();
        $this->get(route('devis.public', ['id' => $devis->id, 'token' => 'faux']))->assertNotFound();
        $this->get(route('devis.public', ['id' => $devis->id, 'token' => $devis->token]))->assertOk();
    }

    public function test_un_devis_se_marque_accepte_ou_refuse(): void
    {
        $devis = $this->creerDevis();

        $this->actingAs($this->admin)
            ->post(route('dashboard.devis.statut', $devis), ['statut' => 'accepte'])
            ->assertRedirect();

        $devis->refresh();
        $this->assertSame('accepte', $devis->statut);
        $this->assertNotNull($devis->date_acceptation);

        $this->actingAs($this->admin)
            ->post(route('dashboard.devis.statut', $devis), ['statut' => 'refuse'])
            ->assertRedirect();

        $this->assertSame('refuse', $devis->fresh()->statut);
    }

    public function test_la_commande_expire_les_devis_depasses(): void
    {
        $devis = $this->creerDevis(['payload' => [
            'date_creation'   => now()->subDays(40)->toDateString(),
            'date_expiration' => now()->subDays(10)->toDateString(),
        ]]);

        $this->artisan('devis:expirer')->assertSuccessful();

        $this->assertSame('expire', $devis->fresh()->statut);
    }

    public function test_supprimer_un_devis_supprime_ses_lignes(): void
    {
        $devis = $this->creerDevis();

        $this->actingAs($this->admin)
            ->delete(route('dashboard.devis.destroy', $devis))
            ->assertRedirect(route('dashboard.devis.index'));

        $this->assertNull(Devis::withoutGlobalScopes()->find($devis->id));
        $this->assertDatabaseCount('devis_items', 0);
    }

    // ─── Factures ─────────────────────────────────────────────────────

    public function test_creation_d_une_facture(): void
    {
        $facture = $this->creerFacture();

        $this->assertSame('FAC-' . now()->format('Ymd') . '-000001', $facture->numero);
        $this->assertSame('en_attente', $facture->statut);
        $this->assertSame(10000, $facture->total_ttc);
        $this->assertSame(10000, $facture->resteAPayer);
    }

    public function test_un_paiement_partiel_met_la_facture_a_jour(): void
    {
        $facture = $this->creerFacture();

        $this->actingAs($this->admin)
            ->post(route('dashboard.factures.payer', $facture), [
                'montant'       => 4000,
                'mode_paiement' => 'especes',
                'date_paiement' => now()->toDateString(),
            ])
            ->assertRedirect();

        $facture->refresh();
        $this->assertSame('partiellement_payee', $facture->statut);
        $this->assertSame(4000, $facture->montant_paye);
        $this->assertSame(6000, $facture->resteAPayer);
        $this->assertSame(1, $facture->paiements()->count());
        $this->assertSame(4000, (int) Vente::withoutGlobalScopes()->sum('total'));
    }

    public function test_un_paiement_total_solde_la_facture(): void
    {
        $facture = $this->creerFacture();

        $this->actingAs($this->admin)
            ->post(route('dashboard.factures.payer', $facture), [
                'montant'       => 10000,
                'mode_paiement' => 'mobile_money',
                'date_paiement' => now()->toDateString(),
            ]);

        $facture->refresh();
        $this->assertSame('payee', $facture->statut);
        $this->assertSame(0, $facture->resteAPayer);
    }

    public function test_le_montant_du_paiement_ne_peut_pas_depasser_le_reste_du(): void
    {
        $facture = $this->creerFacture();

        $this->actingAs($this->admin)
            ->post(route('dashboard.factures.payer', $facture), [
                'montant'       => 20000,
                'mode_paiement' => 'especes',
                'date_paiement' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('montant');

        $this->assertSame(0, $facture->fresh()->montant_paye);
    }

    public function test_marquer_payee_cree_un_paiement_une_vente_et_decremente_le_stock(): void
    {
        $produit = $this->produit(10);
        $facture = $this->creerFacture(['lignes' => [[
            'designation' => $produit->nom, 'quantite' => 3, 'prix_unitaire' => 3000,
            'produit_id' => $produit->id,
        ]]]);

        $this->actingAs($this->admin)
            ->post(route('dashboard.factures.marquer-payee', $facture))
            ->assertRedirect();

        $facture->refresh();
        $this->assertSame('payee', $facture->statut);
        $this->assertSame(9000, $facture->montant_paye);
        $this->assertSame(1, $facture->paiements()->count());
        $this->assertNotNull($facture->vente_id);
        $this->assertSame(7, $produit->fresh()->stock);

        $item = \App\Models\VenteItem::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('produit', $item->type);
        $this->assertSame($produit->id, $item->item_id);
    }

    public function test_annuler_une_facture_payee_retire_le_chiffre_d_affaires_et_restaure_le_stock(): void
    {
        $produit = $this->produit(10);
        $facture = $this->creerFacture(['lignes' => [[
            'designation' => $produit->nom, 'quantite' => 4, 'prix_unitaire' => 3000,
            'produit_id' => $produit->id,
        ]]]);

        $this->actingAs($this->admin)->post(route('dashboard.factures.marquer-payee', $facture));
        $this->assertSame(6, $produit->fresh()->stock);
        $this->assertSame(1, Vente::withoutGlobalScopes()->count());

        $this->actingAs($this->admin)
            ->post(route('dashboard.factures.annuler', $facture))
            ->assertRedirect();

        $facture->refresh();
        $this->assertSame('annulee', $facture->statut);
        $this->assertSame(0, $facture->montant_paye);
        $this->assertNull($facture->vente_id);
        $this->assertSame(0, $facture->paiements()->count());
        $this->assertSame(0, Vente::withoutGlobalScopes()->count());
        $this->assertSame(10, $produit->fresh()->stock);
    }

    public function test_aucun_paiement_sur_une_facture_annulee(): void
    {
        $facture = $this->creerFacture();
        $facture->update(['statut' => 'annulee']);

        $this->actingAs($this->admin)
            ->post(route('dashboard.factures.payer', $facture), [
                'montant'       => 1000,
                'mode_paiement' => 'especes',
                'date_paiement' => now()->toDateString(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Paiement::count());
        $this->assertSame(0, Vente::withoutGlobalScopes()->count());
    }

    public function test_une_facture_avec_paiement_ne_peut_pas_etre_supprimee(): void
    {
        $facture = $this->creerFacture();
        $this->actingAs($this->admin)->post(route('dashboard.factures.marquer-payee', $facture));

        $this->actingAs($this->admin)
            ->delete(route('dashboard.factures.destroy', $facture))
            ->assertSessionHas('error');

        $this->assertNotNull(Facture::withoutGlobalScopes()->find($facture->id));
    }

    public function test_les_indicateurs_ignorent_les_factures_annulees(): void
    {
        // En retard : une seule (en attente, échéance passée)
        $echue = [
            'date_emission' => now()->subDays(30)->toDateString(),
            'date_echeance' => now()->subDays(5)->toDateString(),
        ];

        $this->creerFacture(['payload' => $echue]);

        $annulee = $this->creerFacture(['payload' => $echue]);
        $annulee->update(['statut' => 'annulee']);

        $payee = $this->creerFacture(['payload' => $echue]);
        $payee->update(['statut' => 'payee', 'montant_paye' => $payee->total_ttc]);

        $reponse = $this->actingAs($this->admin)->get(route('dashboard.factures.index'))->assertOk();
        $stats = $reponse->viewData('stats');

        $this->assertSame(1, $stats['en_retard']);
        // 3 factures de 10 000, une annulée → 20 000 facturés
        $this->assertSame(20000, $stats['total_ttc']);
        $this->assertSame(10000, $stats['total_paye']);
    }

    public function test_la_liste_des_factures_se_filtre_par_statut_et_recherche(): void
    {
        $a = $this->creerFacture();
        $a->update(['statut' => 'payee', 'montant_paye' => $a->total_ttc]);
        $b = $this->creerFacture(['payload' => ['client_nom' => 'Kone']]);

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.factures.index', ['statut' => 'payee']))
            ->assertOk();

        $this->assertSame(1, $reponse->viewData('factures')->total());

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.factures.index', ['search' => 'Kone']))
            ->assertOk();

        $this->assertSame(1, $reponse->viewData('factures')->total());
        $this->assertSame($b->id, $reponse->viewData('factures')->first()->id);
    }

    // ─── Avoirs, relances, duplication ─────────────────────────────

    public function test_annuler_une_facture_reglee_emet_un_avoir(): void
    {
        $facture = $this->creerFacture();
        $this->actingAs($this->admin)->post(route('dashboard.factures.marquer-payee', $facture));

        $this->actingAs($this->admin)->post(route('dashboard.factures.annuler', $facture));

        $avoir = \App\Models\Avoir::where('facture_id', $facture->id)->firstOrFail();

        $this->assertSame('AV-' . now()->format('Y') . '-000001', $avoir->numero);
        $this->assertSame(10000, $avoir->montant);
        $this->assertSame('emis', $avoir->statut);
        $this->assertSame($this->admin->institut_id, $avoir->institut_id);
    }

    public function test_annuler_une_facture_impayee_n_emet_pas_d_avoir(): void
    {
        $facture = $this->creerFacture();

        $this->actingAs($this->admin)->post(route('dashboard.factures.annuler', $facture));

        $this->assertSame(0, \App\Models\Avoir::count());
    }

    public function test_la_relance_automatique_respecte_les_paliers_et_l_anti_doublon(): void
    {
        Mail::fake();

        $this->admin->institut->update(['facture_relance_active' => true]);

        $facture = $this->creerFacture(['payload' => [
            'client_email'  => 'client@example.com',
            'date_emission' => now()->subDays(30)->toDateString(),
            'date_echeance' => now()->subDays(7)->toDateString(),
        ]]);

        $this->artisan('factures:relances')->assertSuccessful();

        Mail::assertSent(\App\Mail\FactureARelancer::class, 1);
        $this->assertSame(1, \App\Models\FactureRelance::where('facture_id', $facture->id)->count());

        // Deuxième exécution le même jour : aucune nouvelle relance
        $this->artisan('factures:relances')->assertSuccessful();
        Mail::assertSent(\App\Mail\FactureARelancer::class, 1);
    }

    public function test_les_relances_sont_inactives_par_defaut(): void
    {
        Mail::fake();

        $this->creerFacture(['payload' => [
            'client_email'  => 'client@example.com',
            'date_emission' => now()->subDays(30)->toDateString(),
            'date_echeance' => now()->subDays(7)->toDateString(),
        ]]);

        $this->artisan('factures:relances')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_dupliquer_une_facture_prepare_le_formulaire(): void
    {
        $facture = $this->creerFacture();

        $this->actingAs($this->admin)
            ->get(route('dashboard.factures.dupliquer', $facture))
            ->assertRedirect(route('dashboard.factures.create'))
            ->assertSessionHas('duplicate_facture');

        $donnees = session('duplicate_facture');
        $this->assertCount(1, $donnees['lignes']);
        $this->assertSame(5000, $donnees['lignes'][0]['prix_unitaire']);
    }

    public function test_creer_une_facture_depuis_une_vente(): void
    {
        $produit = $this->produit();
        $vente = Vente::create([
            'institut_id'   => $this->admin->institut_id,
            'user_id'       => $this->admin->id,
            'total'         => 9000,
            'montant_paye'  => 9000,
            'mode_paiement' => 'cash',
            'statut'        => 'validee',
        ]);
        \App\Models\VenteItem::create([
            'vente_id'      => $vente->id,
            'type'          => 'produit',
            'item_id'       => $produit->id,
            'nom_snapshot'  => $produit->nom,
            'prix_snapshot' => 3000,
            'quantite'      => 3,
            'sous_total'    => 9000,
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.factures.depuis-vente', $vente))
            ->assertRedirect(route('dashboard.factures.create'));

        $donnees = session('duplicate_facture');
        $this->assertSame($produit->nom, $donnees['lignes'][0]['designation']);
        $this->assertSame(3, $donnees['lignes'][0]['quantite']);
        $this->assertSame($produit->id, $donnees['lignes'][0]['produit_id']);
    }

    public function test_la_numerotation_annuelle_est_configurable(): void
    {
        $this->admin->institut->update(['facturation_sequence' => 'annuelle']);

        $facture = $this->creerFacture();

        $this->assertSame('FAC-' . now()->format('Y') . '-000001', $facture->numero);
    }

    public function test_la_tva_est_calculee_par_ligne_quand_elle_est_renseignee(): void
    {
        $devis = $this->creerDevis(['lignes' => [
            ['designation' => 'Prestation taxée', 'quantite' => 1, 'prix_unitaire' => 10000, 'tva_taux' => 18],
            ['designation' => 'Produit exonéré', 'quantite' => 1, 'prix_unitaire' => 5000, 'tva_taux' => 0],
        ]]);

        $this->assertSame(15000, $devis->total_ht);
        $this->assertSame(1800, $devis->total_tva);
        $this->assertSame(16800, $devis->total_ttc);
    }

    public function test_l_apercu_pdf_est_diffuse_en_ligne(): void
    {
        $facture = $this->creerFacture();

        $this->actingAs($this->admin)
            ->get(route('dashboard.factures.pdf', ['facture' => $facture, 'inline' => 1]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->admin)
            ->get(route('dashboard.factures.pdf', ['facture' => $facture]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=Facture-' . $facture->numero . '.pdf');
    }

    // ─── Rendu des écrans ────────────────────────────────────────────

    public function test_les_ecrans_du_module_s_affichent(): void
    {
        $devis   = $this->creerDevis();
        $facture = $this->creerFacture();

        $this->actingAs($this->admin)->get(route('dashboard.devis.index'))->assertOk()->assertSee('Devis & Factures');
        $this->actingAs($this->admin)->get(route('dashboard.factures.index'))->assertOk()->assertSee('Total facturé');

        // Filtres actifs : la liste reste fonctionnelle
        $this->actingAs($this->admin)->get(route('dashboard.factures.index', ['statut' => 'payee']))->assertOk();
        $this->actingAs($this->admin)->get(route('dashboard.factures.index', ['retard' => 1]))->assertOk();

        $this->actingAs($this->admin)->get(route('dashboard.devis.show', $devis))->assertOk()->assertSee($devis->numero);
        $this->actingAs($this->admin)->get(route('dashboard.devis.create'))->assertOk()->assertSee('documentForm');
        $this->actingAs($this->admin)->get(route('dashboard.devis.edit', $devis))->assertOk();

        $this->actingAs($this->admin)->get(route('dashboard.factures.show', $facture))->assertOk()->assertSee($facture->numero);
        $this->actingAs($this->admin)->get(route('dashboard.factures.create'))->assertOk()->assertSee('documentForm');

        $this->actingAs($this->admin)->get(route('dashboard.mes-instituts.edit', $this->admin->institut))
            ->assertOk()->assertSee('facturation_sequence', false);
    }
}
