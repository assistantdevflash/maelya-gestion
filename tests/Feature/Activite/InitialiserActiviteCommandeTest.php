<?php

namespace Tests\Feature\Activite;

use App\Models\Client;
use App\Models\Institut;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Commande `activite:initialiser` : amorçage de la date de dernière activité
 * des établissements créés avant la mise en place du suivi.
 */
class InitialiserActiviteCommandeTest extends TestCase
{
    use RefreshDatabase;

    private function creerCompte(string $nom = 'Institut Test'): array
    {
        $admin = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($admin->institut_id);

        DB::table('instituts')->where('id', $institut->id)->update([
            'nom'                    => $nom,
            'derniere_activite_le'   => null,
            'derniere_activite_type' => null,
            'derniere_activite_user_id' => null,
        ]);

        return [$institut->fresh(), $admin];
    }

    private function creerVenteDatee(Institut $institut, User $user, int $joursDansLePasse, string $numero = 'V-001'): Vente
    {
        $vente = Vente::create([
            'institut_id' => $institut->id,
            'user_id'     => $user->id,
            'numero'      => $numero,
            'total'       => 5000,
            'statut'      => 'validee',
        ]);

        DB::table('ventes')->where('id', $vente->id)->update([
            'created_at' => now()->subDays($joursDansLePasse),
            'updated_at' => now()->subDays($joursDansLePasse),
        ]);

        // Le trait a enregistré l'activité à la création : on remet à zéro
        DB::table('instituts')->where('id', $institut->id)->update([
            'derniere_activite_le' => null,
            'derniere_activite_type' => null,
            'derniere_activite_user_id' => null,
        ]);

        return $vente->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────

    public function test_la_commande_renseigne_la_date_depuis_les_donnees_existantes(): void
    {
        [$institut, $admin] = $this->creerCompte();
        $vente = $this->creerVenteDatee($institut, $admin, 10);

        $this->artisan('activite:initialiser')->assertSuccessful();

        $institut->refresh();
        $this->assertNotNull($institut->derniere_activite_le);
        $this->assertSame('vente', $institut->derniere_activite_type);
        $this->assertSame($admin->id, $institut->derniere_activite_user_id);
        $this->assertSame(
            $vente->updated_at->format('Y-m-d H:i:s'),
            $institut->derniere_activite_le->format('Y-m-d H:i:s')
        );
    }

    public function test_la_commande_retient_la_source_la_plus_recente(): void
    {
        [$institut, $admin] = $this->creerCompte();

        // Vente ancienne
        $this->creerVenteDatee($institut, $admin, 30, 'V-001');

        // Client plus récent
        $client = Client::create([
            'institut_id' => $institut->id,
            'telephone'   => '0700000001',
            'prenom'      => 'Awa',
            'nom'         => 'Kone',
        ]);
        DB::table('clients')->where('id', $client->id)->update([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);
        DB::table('instituts')->where('id', $institut->id)->update([
            'derniere_activite_le' => null,
        ]);

        $this->artisan('activite:initialiser')->assertSuccessful();

        $institut->refresh();
        $this->assertSame('client', $institut->derniere_activite_type);
        $this->assertSame(
            now()->subDays(3)->format('Y-m-d'),
            $institut->derniere_activite_le->format('Y-m-d')
        );
    }

    public function test_un_etablissement_sans_donnee_reste_jamais_utilise(): void
    {
        [$institut] = $this->creerCompte();

        $this->artisan('activite:initialiser')
            ->expectsOutputToContain('0 établissement(s) mis à jour')
            ->assertSuccessful();

        $this->assertNull($institut->fresh()->derniere_activite_le);
    }

    public function test_la_commande_ne_touche_pas_aux_etablissements_deja_renseignes(): void
    {
        [$institut, $admin] = $this->creerCompte();
        $this->creerVenteDatee($institut, $admin, 10);

        // Valeur déjà connue, volontairement plus récente
        $connue = now()->subHours(2);
        DB::table('instituts')->where('id', $institut->id)->update([
            'derniere_activite_le'   => $connue,
            'derniere_activite_type' => 'connexion',
        ]);

        $this->artisan('activite:initialiser')->assertSuccessful();

        $institut->refresh();
        $this->assertSame('connexion', $institut->derniere_activite_type);
        $this->assertSame(
            $connue->format('Y-m-d H:i:s'),
            $institut->derniere_activite_le->format('Y-m-d H:i:s')
        );
    }

    public function test_l_option_force_recalcule(): void
    {
        [$institut, $admin] = $this->creerCompte();
        $this->creerVenteDatee($institut, $admin, 10);

        DB::table('instituts')->where('id', $institut->id)->update([
            'derniere_activite_le'   => now()->subHours(2),
            'derniere_activite_type' => 'connexion',
        ]);

        $this->artisan('activite:initialiser', ['--force' => true])->assertSuccessful();

        $institut->refresh();
        $this->assertSame('vente', $institut->derniere_activite_type);
        $this->assertSame(
            now()->subDays(10)->format('Y-m-d'),
            $institut->derniere_activite_le->format('Y-m-d')
        );
    }

    public function test_le_dry_run_n_ecrit_rien(): void
    {
        [$institut, $admin] = $this->creerCompte();
        $this->creerVenteDatee($institut, $admin, 10);

        $this->artisan('activite:initialiser', ['--dry-run' => true])
            ->expectsOutputToContain('Simulation')
            ->assertSuccessful();

        $this->assertNull($institut->fresh()->derniere_activite_le);
    }

    public function test_l_option_institut_limite_le_traitement(): void
    {
        [$cible, $admin1]     = $this->creerCompte('Salon Cible');
        [$autre, $admin2]     = $this->creerCompte('Salon Autre');

        $this->creerVenteDatee($cible, $admin1, 10, 'V-001');
        $this->creerVenteDatee($autre, $admin2, 10, 'V-002');

        $this->artisan('activite:initialiser', ['--institut' => $cible->id])->assertSuccessful();

        $this->assertNotNull($cible->fresh()->derniere_activite_le);
        $this->assertNull($autre->fresh()->derniere_activite_le);
    }
}
