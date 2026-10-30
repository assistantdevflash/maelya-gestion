<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\Credit;
use App\Models\Echeance;
use App\Models\Institut;
use App\Models\PaiementCredit;
use App\Models\User;
use App\Models\Vente;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Couverture des 7 axes du module crédit :
 * relances, suivi public, plafond/score, tableau de bord recouvrement,
 * réaménagement, sécurité des liens, confort de saisie.
 */
class CreditModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Institut $institut;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->creerAdmin(['role' => 'admin'], ['credit_plafond_defaut' => 100000]);
        $this->institut = Institut::findOrFail($this->user->institut_id);

        $this->client = Client::create([
            'institut_id'   => $this->institut->id,
            'type_client'   => 'personne_physique',
            'prenom'        => 'Awa',
            'nom'           => 'Kone',
            'telephone'     => '0701020304',
        ]);
    }

    protected function creerCredit(int $montant = 60000, int $nbEcheances = 3, ?string $dateDebut = null): Credit
    {
        $vente = Vente::create([
            'institut_id'   => $this->institut->id,
            'user_id'       => $this->user->id,
            'client_id'     => $this->client->id,
            'numero'        => 'V-' . uniqid(),
            'total'         => $montant,
            'montant_paye'  => 0,
            'mode_paiement' => 'credit',
            'credit_statut' => 'en_cours',
            'statut'        => 'validee',
        ]);

        $credit = Credit::create([
            'institut_id'    => $this->institut->id,
            'client_id'      => $this->client->id,
            'vente_id'       => $vente->id,
            'montant_total'  => $montant,
            'apport_initial' => 0,
            'reste_a_payer'  => $montant,
            'nb_echeances'   => $nbEcheances,
            'frequence'      => 'mensuelle',
            'statut'         => 'en_cours',
            'date_debut'     => $dateDebut ? Carbon::parse($dateDebut) : now(),
        ]);

        $this->genererEcheances($credit, $dateDebut);

        return $credit->fresh();
    }

    /** Génère l'échéancier initial (même logique que CreditController::store). */
    private function genererEcheances(Credit $credit, ?string $dateDebut = null): void
    {
        $parEcheance = (int) round((int) $credit->reste_a_payer / max(1, (int) $credit->nb_echeances));
        $date = Carbon::parse($dateDebut ?? now());

        for ($i = 0; $i < (int) $credit->nb_echeances; $i++) {
            $date = $date->copy()->addMonth();
            $montant = ($i === (int) $credit->nb_echeances - 1)
                ? (int) $credit->reste_a_payer - $parEcheance * ((int) $credit->nb_echeances - 1)
                : $parEcheance;

            Echeance::create([
                'credit_id'   => $credit->id,
                'institut_id' => $this->institut->id,
                'numero'      => $i + 1,
                'date_prevue' => $date->copy(),
                'montant'     => max(0, $montant),
                'statut'      => 'en_attente',
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Axe 3 — Plafond et score
    // ─────────────────────────────────────────────────────────────────────

    public function test_le_plafond_par_defaut_de_l_institut_est_utilise(): void
    {
        $service = app(CreditService::class);

        $this->assertSame(100000, $service->plafondEffectif($this->client));

        $this->client->update(['credit_plafond' => 250000]);

        $this->assertSame(250000, $service->plafondEffectif($this->client->fresh()));
    }

    public function test_un_credit_au_dela_du_plafond_est_refuse(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.credits.store'), [
                'client_id'      => $this->client->id,
                'montant_total'  => 150000,
                'apport_initial' => 0,
                'nb_echeances'   => 3,
                'frequence'      => 'mensuelle',
                'date_debut'     => now()->toDateString(),
                'lignes'         => json_encode([
                    ['designation' => 'Prestation', 'quantite' => 1, 'prix_unitaire' => 150000],
                ]),
            ])
            ->assertSessionHasErrors('plafond');

        $this->assertSame(0, Credit::count());
    }

    public function test_forcer_plafond_permet_de_passer_outre(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.credits.store'), [
                'client_id'      => $this->client->id,
                'montant_total'  => 150000,
                'apport_initial' => 0,
                'nb_echeances'   => 3,
                'frequence'      => 'mensuelle',
                'date_debut'     => now()->toDateString(),
                'forcer_plafond' => 1,
                'lignes'         => json_encode([
                    ['designation' => 'Prestation', 'quantite' => 1, 'prix_unitaire' => 150000],
                ]),
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Credit::count());
    }

    public function test_le_score_penalise_les_retards(): void
    {
        $service = app(CreditService::class);
        $credit  = $this->creerCredit(30000, 2, now()->subMonths(2)->toDateString());

        $credit->echeances()->update(['statut' => 'retard']);

        $score = $service->scoreClient($this->client->fresh());

        $this->assertNotNull($score['score']);
        $this->assertGreaterThan(0, $score['en_retard']);
        $this->assertLessThan(100, $score['score']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Axe 4 — Tableau de bord du recouvrement
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_balance_agee_repartit_les_creances_par_tranche(): void
    {
        $service = app(CreditService::class);
        $this->creerCredit(90000, 3, now()->subDays(50)->toDateString());

        $balance = $service->balanceAgee($this->institut->id);

        $this->assertSame(90000, $balance['total']);
        $this->assertGreaterThan(0, $balance['nb']);

        $somme = $balance['a_venir'] + $balance['t0_30'] + $balance['t31_60']
            + $balance['t61_90'] + $balance['t90_plus'];

        $this->assertSame($balance['total'], $somme);
    }

    public function test_le_taux_de_recouvrement_est_nul_sans_paiement(): void
    {
        $service = app(CreditService::class);
        $this->creerCredit(60000, 3);

        $this->assertSame(0.0, $service->tauxRecouvrement($this->institut->id));
    }

    public function test_le_top_debiteurs_classe_par_montant_du(): void
    {
        $service = app(CreditService::class);

        $autre = Client::create([
            'institut_id' => $this->institut->id,
            'type_client' => 'personne_physique',
            'prenom'      => 'Koffi',
            'nom'         => 'Yao',
            'telephone'   => '0705060708',
        ]);

        $this->creerCredit(30000, 3);
        $vente = Vente::create([
            'institut_id'   => $this->institut->id,
            'user_id'       => $this->user->id,
            'client_id'     => $autre->id,
            'numero'        => 'V-' . uniqid(),
            'total'         => 80000,
            'montant_paye'  => 0,
            'mode_paiement' => 'credit',
            'credit_statut' => 'en_cours',
            'statut'        => 'validee',
        ]);
        $credit = Credit::create([
            'institut_id'   => $this->institut->id,
            'client_id'     => $autre->id,
            'vente_id'      => $vente->id,
            'montant_total' => 80000,
            'apport_initial' => 0,
            'reste_a_payer' => 80000,
            'nb_echeances'  => 2,
            'frequence'     => 'mensuelle',
            'statut'        => 'en_cours',
            'date_debut'    => now(),
        ]);
        $this->genererEcheances($credit);

        $top = $service->topDebiteurs($this->institut->id);

        $this->assertSame($autre->id, $top->first()['client']->id);
    }

    public function test_l_export_csv_des_encours_est_propose(): void
    {
        $this->creerCredit(45000, 3);

        $this->actingAs($this->user)
            ->get(route('dashboard.credits.export-encours'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_la_liste_des_credits_affiche_le_tableau_de_recouvrement(): void
    {
        $this->creerCredit(45000, 3);

        $this->actingAs($this->user)
            ->get(route('dashboard.credits.index'))
            ->assertOk()
            ->assertSee('Balance âgée des créances')
            ->assertSee('Taux de recouvrement')
            ->assertSee('Délai moyen')
            ->assertSee('Clients les plus endettés')
            ->assertSee('Export CSV');
    }

    public function test_la_page_des_parametres_credit_s_affiche(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard.credits.parametres'))
            ->assertOk();
    }

    public function test_les_parametres_credit_sont_enregistrables(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.credits.parametres.store'), [
                'credit_plafond_defaut'  => 350000,
                'credit_relance_active'  => 1,
                'credit_message_relance' => 'Bonjour {client}, reste {reste} FCFA.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(350000, (int) $this->institut->fresh()->credit_plafond_defaut);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Axe 6 — Sécurité des liens publics
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_fiche_credit_refuse_une_url_non_signee(): void
    {
        $credit = $this->creerCredit(30000, 2);

        $this->get('/fiche-credit/' . $credit->id)->assertForbidden();
    }

    public function test_la_fiche_credit_accepte_une_url_signee(): void
    {
        $credit = $this->creerCredit(30000, 2);

        $url = app(CreditService::class)->urlFiche($credit);

        $this->get($url)->assertOk();
    }

    public function test_la_page_de_suivi_publique_affiche_l_echeancier(): void
    {
        $credit = $this->creerCredit(30000, 2);

        $url = app(CreditService::class)->urlSuivi($credit);

        $this->get($url)
            ->assertOk()
            ->assertSee('Awa')
            ->assertSee('Échéancier', false);
    }

    public function test_la_page_de_suivi_refuse_une_url_non_signee(): void
    {
        $credit = $this->creerCredit(30000, 2);

        $this->get('/suivi-credit/' . $credit->id)->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Axe 1 — Relances automatiques
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_relance_ne_part_qu_une_fois_par_palier(): void
    {
        $credit = $this->creerCredit(30000, 1);
        $credit->echeances()->update(['date_prevue' => now()->subDay()]);

        $this->artisan('credits:relances')->assertSuccessful();
        $this->assertSame(1, $credit->relances()->count());

        $this->artisan('credits:relances')->assertSuccessful();
        $this->assertSame(1, $credit->relances()->count());
    }

    public function test_le_dry_run_n_envoie_aucune_relance(): void
    {
        $credit = $this->creerCredit(30000, 1);
        $credit->echeances()->update(['date_prevue' => now()->subDay()]);

        $this->artisan('credits:relances', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(0, $credit->relances()->count());
    }

    public function test_un_institut_desactive_ne_relance_pas(): void
    {
        $this->institut->update(['credit_relance_active' => false]);
        $credit = $this->creerCredit(30000, 1);
        $credit->echeances()->update(['date_prevue' => now()->subDay()]);

        $this->artisan('credits:relances')->assertSuccessful();

        $this->assertSame(0, $credit->relances()->count());
    }

    public function test_le_message_de_relance_remplace_les_placeholders(): void
    {
        $credit  = $this->creerCredit(30000, 2);
        $service = app(CreditService::class);

        $message = $service->messageRelance($credit);

        $this->assertStringContainsString('Awa', $message);
        $this->assertStringNotContainsString('{client}', $message);
        $this->assertStringNotContainsString('{reste}', $message);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Axe 5 — Traitement des impayés
    // ─────────────────────────────────────────────────────────────────────

    public function test_le_reechelonnement_ne_touche_pas_les_echeances_payees(): void
    {
        $credit = $this->creerCredit(60000, 3);
        $premiere = $credit->echeances()->orderBy('numero')->first();
        $premiere->update(['montant_paye' => $premiere->montant, 'statut' => 'payee']);

        app(CreditService::class)->reechelonner($credit, 4, 'mensuelle');

        $echeances = $credit->fresh()->echeances()->orderBy('numero')->get();

        $this->assertTrue($echeances->first()->statut === 'payee');
        $this->assertSame(4, $echeances->where('statut', '!=', 'payee')->count());
    }

    public function test_le_report_d_echeance_remet_le_statut_en_attente(): void
    {
        $credit   = $this->creerCredit(30000, 2, now()->subMonths(2)->toDateString());
        $echeance = $credit->echeances()->orderBy('numero')->first();
        $echeance->update(['statut' => 'retard']);

        app(CreditService::class)->reporterEcheance($echeance, now()->addDays(10));

        $this->assertSame('en_attente', $echeance->fresh()->statut);
    }

    public function test_le_report_refuse_une_date_passee(): void
    {
        $credit   = $this->creerCredit(30000, 2);
        $echeance = $credit->echeances()->orderBy('numero')->first();

        $this->actingAs($this->user)
            ->post(route('dashboard.credits.echeances.reporter', [$credit, $echeance]), [
                'date_prevue' => now()->subDays(3)->toDateString(),
            ])
            ->assertSessionHasErrors('date_prevue');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Trésorerie prévisionnelle
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_tresorerie_previsionnelle_integre_les_echeances_de_credit(): void
    {
        $credit = $this->creerCredit(60000, 3);
        // Une échéance dans la fenêtre de projection (30 jours)
        $credit->echeances()->orderBy('numero')->first()
            ->update(['date_prevue' => now()->addDays(5)]);

        $this->actingAs($this->user)
            ->get(route('dashboard.finances.index'))
            ->assertOk()
            ->assertSee('Projection financière')
            ->assertSee('crédits à recouvrer', false);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Axe 7 — Confort de saisie
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_creation_depuis_une_vente_preremplit_les_lignes(): void
    {
        $vente = Vente::create([
            'institut_id'   => $this->institut->id,
            'user_id'       => $this->user->id,
            'client_id'     => $this->client->id,
            'numero'        => 'V-PREFILL',
            'total'         => 25000,
            'montant_paye'  => 0,
            'mode_paiement' => 'cash',
            'credit_statut' => null,
            'statut'        => 'validee',
        ]);

        $vente->items()->create([
            'type'           => 'prestation',
            'item_id'        => (string) \Illuminate\Support\Str::uuid(),
            'nom_snapshot'   => 'Soin visage',
            'prix_snapshot'  => 25000,
            'quantite'       => 1,
            'sous_total'     => 25000,
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard.credits.create', ['vente' => $vente->id]))
            ->assertOk()
            ->assertSee('Soin visage')
            ->assertSee('V-PREFILL');
    }

    public function test_un_paiement_genere_un_recu_partageable(): void
    {
        $credit   = $this->creerCredit(30000, 2);
        $echeance = $credit->echeances()->orderBy('numero')->first();

        $this->actingAs($this->user)
            ->post(route('dashboard.credits.payer', $credit), [
                'echeance_id'   => $echeance->id,
                'montant'       => 5000,
                'mode_paiement' => 'cash',
            ])
            ->assertSessionHas('recu_credit')
            ->assertSessionHasNoErrors();

        $this->assertSame(1, PaiementCredit::count());
    }
}
