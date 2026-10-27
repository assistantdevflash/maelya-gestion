<?php

namespace Tests\Feature\Rapports;

use App\Mail\RapportMensuelInstitut;
use App\Models\Abonnement;
use App\Models\Commande;
use App\Models\Depense;
use App\Models\Facture;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use App\Models\Vente;
use App\Services\RapportMensuelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Rapport financier mensuel : calculs, comparaison avec le mois précédent
 * et conseil marketing.
 */
class RapportMensuelTest extends TestCase
{
    use RefreshDatabase;

    private const MOIS = '2026-09-15'; // mois analysé

    private function compte(string $nom = 'Salon Test'): array
    {
        $institut = Institut::create([
            'nom'   => $nom,
            'email' => strtolower(str_replace(' ', '', $nom)) . '@test.ci',
            'type'  => 'salon_coiffure',
            'actif' => true,
        ]);

        $owner = User::factory()->create([
            'prenom'      => 'Kone',
            'nom_famille' => 'Test',
            'role'        => 'admin',
            'actif'       => true,
            'institut_id' => $institut->id,
        ]);

        $institut->forceFill(['proprietaire_id' => $owner->id])->save();

        return [$institut->fresh(), $owner];
    }

    private function vente(
        Institut $institut,
        User $owner,
        int $total,
        string $date,
        string $mode = 'cash',
        ?int $montantPaye = null
    ): Vente {
        $vente = Vente::create([
            'institut_id'   => $institut->id,
            'user_id'       => $owner->id,
            'numero'        => 'V-' . uniqid(),
            'total'         => $total,
            'montant_paye'  => $montantPaye ?? $total,
            'mode_paiement' => $mode,
            'statut'        => 'validee',
        ]);

        DB::table('ventes')->where('id', $vente->id)
            ->update(['created_at' => $date, 'updated_at' => $date]);

        return $vente->fresh();
    }

    private function depense(Institut $institut, User $owner, int $montant, string $date, string $categorie = 'fournitures'): Depense
    {
        return Depense::create([
            'institut_id' => $institut->id,
            'user_id'     => $owner->id,
            'description' => 'Dépense de test',
            'categorie'   => $categorie,
            'montant'     => $montant,
            'date'        => $date,
        ]);
    }

    private function facture(Institut $institut, User $owner, string $statut, string $date): Facture
    {
        return Facture::create([
            'institut_id'   => $institut->id,
            'user_id'       => $owner->id,
            'numero'        => 'F-' . uniqid(),
            'statut'        => $statut,
            'date_emission' => substr($date, 0, 10),
            'date_echeance' => substr($date, 0, 10),
            'client_prenom' => 'Client',
            'client_nom'    => 'Test',
            'sous_total'    => 10000,
            'total_ht'      => 10000,
            'total_ttc'     => 10000,
            'montant_paye'  => $statut === 'payee' ? 10000 : 0,
            'token'         => \Illuminate\Support\Str::random(32),
        ]);
    }

    private function commande(Institut $institut, string $statut, bool $payee, string $date): Commande
    {
        $commande = Commande::create([
            'institut_id'      => $institut->id,
            'numero'           => 'CMD-' . uniqid(),
            'client_prenom'    => 'Client',
            'client_nom'       => 'Test',
            'client_telephone' => '0700000000',
            'client_adresse'   => 'Abidjan',
            'sous_total'       => 5000,
            'total'            => 5000,
            'statut'           => $statut,
            'payee'            => $payee,
        ]);

        DB::table('commandes')->where('id', $commande->id)
            ->update(['created_at' => $date, 'updated_at' => $date]);

        return $commande->fresh();
    }

    private function service(): RapportMensuelService
    {
        return app(RapportMensuelService::class);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 1. Calculs
    // ─────────────────────────────────────────────────────────────────────

    public function test_le_rapport_calcule_les_chiffres_du_mois(): void
    {
        [$institut, $owner] = $this->compte();

        $this->vente($institut, $owner, 15000, self::MOIS);
        $this->vente($institut, $owner, 5000, self::MOIS);
        $this->depense($institut, $owner, 4000, self::MOIS);

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame(20000, $rapport['actuel']['ca']);
        $this->assertSame(2, $rapport['actuel']['nb_ventes']);
        $this->assertSame(4000, $rapport['actuel']['depenses']);
        $this->assertSame(16000, $rapport['actuel']['benefice']);
        $this->assertSame(80.0, $rapport['actuel']['marge']);
        $this->assertSame(10000, $rapport['actuel']['panier_moyen']);
        $this->assertTrue($rapport['a_activite']);
    }

    public function test_un_credit_ne_compte_que_le_montant_encaisse(): void
    {
        [$institut, $owner] = $this->compte();

        $vente = $this->vente($institut, $owner, 50000, self::MOIS, 'cash', 10000);
        // Une vente à crédit est identifiée par credit_statut
        DB::table('ventes')->where('id', $vente->id)->update(['credit_statut' => 'en_cours']);

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame(10000, $rapport['actuel']['ca']);
        $this->assertSame(1, $rapport['actuel']['nb_ventes']);
    }

    public function test_un_credit_sans_apport_n_est_pas_compte_comme_vente(): void
    {
        [$institut, $owner] = $this->compte();

        $vente = $this->vente($institut, $owner, 50000, self::MOIS, 'cash', 0);
        DB::table('ventes')->where('id', $vente->id)->update(['credit_statut' => 'en_cours']);

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame(0, $rapport['actuel']['ca']);
        $this->assertSame(0, $rapport['actuel']['nb_ventes']);
    }

    public function test_le_rapport_calcule_la_progression_par_rapport_au_mois_precedent(): void
    {
        [$institut, $owner] = $this->compte();

        // Août : 10 000 · Septembre : 15 000 → +50 %
        $this->vente($institut, $owner, 10000, '2026-08-10');
        $this->vente($institut, $owner, 15000, self::MOIS);

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame(10000, $rapport['precedent']['ca']);
        $this->assertSame(50.0, $rapport['evolutions']['ca']);
    }

    public function test_une_regression_est_negative(): void
    {
        [$institut, $owner] = $this->compte();

        // Août : 20 000 · Septembre : 10 000 → -50 %
        $this->vente($institut, $owner, 20000, '2026-08-10');
        $this->vente($institut, $owner, 10000, self::MOIS);

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame(-50.0, $rapport['evolutions']['ca']);
    }

    public function test_les_depenses_sont_reparties_par_categorie(): void
    {
        [$institut, $owner] = $this->compte();

        $this->vente($institut, $owner, 20000, self::MOIS);
        $this->depense($institut, $owner, 3000, self::MOIS, 'fournitures');
        $this->depense($institut, $owner, 1000, self::MOIS, 'loyer');

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));
        $categories = collect($rapport['depenses_categories']);

        $this->assertCount(2, $categories);
        $this->assertSame('fournitures', $categories[0]['categorie']);
        $this->assertSame('Fournitures', $categories[0]['label']);
        $this->assertSame(75.0, $categories[0]['part']);
    }

    public function test_le_rapport_compte_les_factures_et_leurs_reglements(): void
    {
        [$institut, $owner] = $this->compte();

        $this->facture($institut, $owner, 'payee', self::MOIS);
        $this->facture($institut, $owner, 'en_attente', self::MOIS);
        $this->facture($institut, $owner, 'payee', '2026-08-12'); // hors période

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame(2, $rapport['actuel']['factures']);
        $this->assertSame(1, $rapport['actuel']['factures_reglees']);
    }

    public function test_le_rapport_compte_les_commandes_livrees_et_payees(): void
    {
        [$institut, $owner] = $this->compte();

        $this->commande($institut, 'livree', true, self::MOIS);
        $this->commande($institut, 'livree', false, self::MOIS);   // livrée, non payée
        $this->commande($institut, 'en_preparation', true, self::MOIS);
        $this->commande($institut, 'livree', true, '2026-08-12');  // hors période

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame(3, $rapport['actuel']['commandes']);
        $this->assertSame(1, $rapport['actuel']['commandes_livrees_payees']);
    }

    public function test_le_libelle_de_sante_est_explicite(): void
    {
        [$beneficiaire, $owner] = $this->compte('Institut Beneficiaire');
        $this->vente($beneficiaire, $owner, 20000, self::MOIS);
        $this->assertSame('Mois bénéficiaire', $this->service()->construire($beneficiaire, Carbon::parse(self::MOIS))['sante']['label']);

        [$deficitaire] = $this->compte('Institut Deficitaire');
        $this->depense($deficitaire, $owner, 5000, self::MOIS);
        $this->assertSame('Mois déficitaire', $this->service()->construire($deficitaire, Carbon::parse(self::MOIS))['sante']['label']);

        [$vide] = $this->compte('Institut Vide');
        $this->assertSame('Aucune activité', $this->service()->construire($vide, Carbon::parse(self::MOIS))['sante']['label']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. Conseils marketing
    // ─────────────────────────────────────────────────────────────────────

    public function test_le_conseil_signale_un_mois_sans_vente(): void
    {
        [$institut] = $this->compte();

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame('critique', $rapport['conseil']['niveau']);
        $this->assertStringContainsString('Relancez', $rapport['conseil']['titre']);
        $this->assertFalse($rapport['a_activite']);
    }

    public function test_le_conseil_signale_une_baisse_du_chiffre_d_affaires(): void
    {
        [$institut, $owner] = $this->compte();

        $this->vente($institut, $owner, 50000, '2026-08-10');
        $this->vente($institut, $owner, 20000, self::MOIS);

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame('alerte', $rapport['conseil']['niveau']);
        $this->assertStringContainsString('repli', strtolower($rapport['conseil']['titre']));
    }

    public function test_le_conseil_signale_des_charges_trop_lourdes(): void
    {
        [$institut, $owner] = $this->compte();

        $this->vente($institut, $owner, 10000, self::MOIS);
        $this->depense($institut, $owner, 8000, self::MOIS);

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertStringContainsString('charges', $rapport['conseil']['titre']);
    }

    public function test_le_conseil_signale_une_bonne_progression(): void
    {
        [$institut, $owner] = $this->compte();

        $this->vente($institut, $owner, 10000, '2026-08-10');
        $this->vente($institut, $owner, 30000, self::MOIS);

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame('positif', $rapport['conseil']['niveau']);
        $this->assertStringContainsString('progression', strtolower($rapport['conseil']['titre']));
    }

    public function test_le_conseil_signale_un_panier_moyen_faible(): void
    {
        [$institut, $owner] = $this->compte();

        // Aucune variation forte, mais panier moyen de 3 000 F
        $this->vente($institut, $owner, 3000, self::MOIS);
        $this->vente($institut, $owner, 3000, self::MOIS);

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));

        $this->assertSame('conseil', $rapport['conseil']['niveau']);
        $this->assertStringContainsString('panier moyen', strtolower($rapport['conseil']['titre']));
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Commande d'envoi
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_commande_envoie_le_rapport_par_email(): void
    {
        Mail::fake();

        [$institut, $owner] = $this->compte();
        $this->vente($institut, $owner, 15000, self::MOIS);

        $this->artisan('rapports:mensuel', ['--mois' => '2026-09'])->assertSuccessful();

        Mail::assertSent(RapportMensuelInstitut::class, function (RapportMensuelInstitut $mail) use ($owner) {
            return $mail->hasTo($owner->email)
                && $mail->rapport['actuel']['ca'] === 15000;
        });
    }

    public function test_la_commande_ignore_les_etablissements_sans_activite(): void
    {
        Mail::fake();

        $this->compte('Salon Sans Activite');

        $this->artisan('rapports:mensuel', ['--mois' => '2026-09'])->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_l_option_tout_envoie_meme_sans_activite(): void
    {
        Mail::fake();

        $this->compte('Salon Sans Activite');

        $this->artisan('rapports:mensuel', ['--mois' => '2026-09', '--tout' => true])
            ->assertSuccessful();

        Mail::assertSent(RapportMensuelInstitut::class);
    }

    public function test_le_dry_run_n_envoie_aucun_email(): void
    {
        Mail::fake();

        [$institut, $owner] = $this->compte();
        $this->vente($institut, $owner, 15000, self::MOIS);

        $this->artisan('rapports:mensuel', ['--mois' => '2026-09', '--dry-run' => true])
            ->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_un_mois_invalide_est_refuse(): void
    {
        $this->artisan('rapports:mensuel', ['--mois' => 'nimportequoi'])->assertFailed();
    }

    public function test_le_mail_contient_les_chiffres_et_le_conseil(): void
    {
        [$institut, $owner] = $this->compte();

        $this->vente($institut, $owner, 15000, self::MOIS);
        $this->depense($institut, $owner, 2000, self::MOIS);

        $rapport = $this->service()->construire($institut, Carbon::parse(self::MOIS));
        $mail = new RapportMensuelInstitut($rapport, $owner);

        $html = $mail->render();

        $this->assertStringContainsString('15 000', $html);
        $this->assertStringContainsString('2 000', $html);
        $this->assertStringContainsString($rapport['conseil']['titre'], $html);
        $this->assertStringContainsString('Conseiller du mois', $html);
        $this->assertStringContainsString($institut->nom, $html);
    }
}
