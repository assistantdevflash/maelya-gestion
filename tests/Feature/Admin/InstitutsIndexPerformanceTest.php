<?php

namespace Tests\Feature\Admin;

use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Garde-fou de performance sur la liste des établissements.
 *
 * Le nombre de requêtes doit rester constant quel que soit le nombre
 * d'établissements affichés (pas de N+1 sur les propriétaires/abonnements).
 */
class InstitutsIndexPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private const MAX_REQUETES = 12;

    /**
     * Crée N établissements répartis sur plusieurs propriétaires,
     * chacun avec un abonnement actif.
     */
    private function semer(int $nbProprietaires, int $etablissementsParProprio): void
    {
        $plan = PlanAbonnement::create([
            'nom'           => 'Premium',
            'slug'          => 'premium',
            'duree_type'    => 'mensuel',
            'duree_jours'   => 30,
            'prix'          => 4900,
            'max_instituts' => 3,
            'max_employes'  => 3,
            'actif'         => true,
            'ordre'         => 1,
        ]);

        for ($p = 1; $p <= $nbProprietaires; $p++) {
            $owner = User::factory()->create([
                'prenom'      => "Proprio{$p}",
                'nom_famille' => 'Test',
                'role'        => 'admin',
                'actif'       => true,
            ]);

            Abonnement::create([
                'user_id'   => $owner->id,
                'plan_id'   => $plan->id,
                'montant'   => 4900,
                'periode'   => 'mensuel',
                'statut'    => 'actif',
                'debut_le'  => now()->toDateString(),
                'expire_le' => now()->addMonth()->toDateString(),
            ]);

            for ($e = 1; $e <= $etablissementsParProprio; $e++) {
                Institut::create([
                    'nom'             => "Etablissement {$p}-{$e}",
                    'email'           => "etab{$p}-{$e}@test.ci",
                    'type'            => $e % 2 === 0 ? 'salon_coiffure' : 'onglerie',
                    'ville'           => 'Abidjan',
                    'actif'           => true,
                    'proprietaire_id' => $owner->id,
                ]);
            }
        }
    }

    /** Compte les requêtes SQL déclenchées par un GET sur la liste. */
    private function requetesPourLaListe(?array &$capture = null): int
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        $requetes = [];
        DB::listen(function ($query) use (&$requetes) {
            $requetes[] = $query->sql;
        });

        $this->actingAs($admin)->get(route('admin.instituts.index'))->assertOk();

        $capture = $requetes;

        return count($requetes);
    }

    public function test_le_nombre_de_requetes_ne_depend_pas_du_volume(): void
    {
        // Volumétrie réelle : 10 propriétaires × 4 établissements
        $this->semer(10, 4);

        $requetes = $this->requetesPourLaListe();

        $this->assertLessThanOrEqual(
            self::MAX_REQUETES,
            $requetes,
            "La liste déclenche {$requetes} requêtes SQL (N+1 probable)."
        );
    }

    public function test_le_nombre_de_requetes_reste_faible_sur_un_petit_volume(): void
    {
        $this->semer(2, 2);

        $requetes = $this->requetesPourLaListe();

        $this->assertLessThanOrEqual(self::MAX_REQUETES, $requetes);
    }

    public function test_les_abonnements_ne_sont_pas_charges_en_n_plus_1(): void
    {
        $this->semer(10, 4);

        $this->requetesPourLaListe($sqls);

        $unitaires = collect($sqls)->filter(
            fn ($sql) => str_contains($sql, 'from "abonnements"')
                && str_contains($sql, '"abonnements"."user_id" = ?')
        );

        $this->assertCount(0, $unitaires, 'Abonnements chargés en N+1.');
    }

    public function test_les_libelles_de_types_sont_charges_en_un_seul_select(): void
    {
        $this->semer(10, 4);

        $this->requetesPourLaListe($sqls);

        $surTypes = collect($sqls)->filter(fn ($sql) => str_contains($sql, 'from "etablissement_types"'));

        $this->assertCount(1, $surTypes, 'Un SELECT par type détecté.');
    }
}
