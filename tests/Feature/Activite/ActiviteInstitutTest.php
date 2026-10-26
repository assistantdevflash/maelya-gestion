<?php

namespace Tests\Feature\Activite;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Institut;
use App\Models\User;
use App\Models\Vente;
use App\Services\ActiviteInstitutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Suivi de l'activité d'un établissement : date et heure de la dernière
 * opération métier, niveau d'activité et filtrage côté admin.
 */
class ActiviteInstitutTest extends TestCase
{
    use RefreshDatabase;

    private ActiviteInstitutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ActiviteInstitutService::class);
    }

    private function creerInstitutEtAdmin(): array
    {
        $admin = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($admin->institut_id);

        return [$institut, $admin];
    }

    private function creerVente(Institut $institut, User $user, string $numero = 'V-001'): Vente
    {
        return Vente::create([
            'institut_id' => $institut->id,
            'user_id'     => $user->id,
            'numero'      => $numero,
            'total'       => 5000,
            'statut'      => 'validee',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 1. Les écritures métier alimentent la dernière activité
    // ─────────────────────────────────────────────────────────────────────

    public function test_creer_une_vente_enregistre_l_activite_de_l_institut(): void
    {
        [$institut, $admin] = $this->creerInstitutEtAdmin();

        $this->assertNull($institut->fresh()->derniere_activite_le);

        $this->creerVente($institut, $admin);

        $institut->refresh();
        $this->assertNotNull($institut->derniere_activite_le);
        $this->assertSame('vente', $institut->derniere_activite_type);
        $this->assertSame($admin->id, $institut->derniere_activite_user_id);
        $this->assertTrue($institut->derniere_activite_le->diffInSeconds(now()) < 5);
    }

    public function test_creer_ou_modifier_un_client_met_a_jour_l_activite(): void
    {
        [$institut, $admin] = $this->creerInstitutEtAdmin();

        $client = Client::create([
            'institut_id' => $institut->id,
            'telephone'   => '0700000001',
            'prenom'      => 'Awa',
            'nom'         => 'Kone',
        ]);

        $this->assertSame('client', $institut->fresh()->derniere_activite_type);

        // Remonter artificiellement la date, puis modifier le client
        Cache::flush();
        $institut->update(['derniere_activite_le' => now()->subDays(3)]);

        $client->update(['prenom' => 'Awaa']);

        $this->assertSame(0, (int) $institut->fresh()->derniere_activite_le->diffInDays(now()));
    }

    public function test_creer_une_commande_boutique_enregistre_l_activite(): void
    {
        [$institut] = $this->creerInstitutEtAdmin();

        Commande::create([
            'institut_id'       => $institut->id,
            'numero'            => 'CMD-001',
            'client_prenom'     => 'Mariam',
            'client_nom'        => 'Diallo',
            'client_telephone'  => '0700000002',
            'client_adresse'    => 'Abidjan',
            'sous_total'        => 5000,
            'total'             => 5000,
        ]);

        $this->assertSame('commande', $institut->fresh()->derniere_activite_type);
    }

    public function test_marquer_sans_institut_est_sans_effet_et_sans_erreur(): void
    {
        // Garde-fou : un appel sans établissement ne doit jamais lever d'exception
        $this->service->marquer(null, 'vente');
        $this->service->marquer('', 'vente');

        $this->assertSame(0, Institut::whereNotNull('derniere_activite_le')->count());
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. Anti-rafale
    // ─────────────────────────────────────────────────────────────────────

    public function test_l_anti_rafale_evite_les_ecritures_repetees(): void
    {
        [$institut, $admin] = $this->creerInstitutEtAdmin();

        $this->creerVente($institut, $admin, 'V-001');
        $premiere = $institut->fresh()->derniere_activite_le;

        // Simuler une seconde action immédiate
        $this->travel(5)->seconds();
        Client::create([
            'institut_id' => $institut->id,
            'telephone'   => '0700000003',
            'prenom'      => 'Fatou',
            'nom'         => 'Bamba',
        ]);

        // La date n'a pas bougé et le type reste celui de la 1re action
        $this->assertEquals($premiere, $institut->fresh()->derniere_activite_le);
        $this->assertSame('vente', $institut->fresh()->derniere_activite_type);
    }

    public function test_le_forcage_ignore_l_anti_rafale(): void
    {
        [$institut, $admin] = $this->creerInstitutEtAdmin();

        $this->service->marquer($institut, 'vente', $admin);
        $premiere = $institut->fresh()->derniere_activite_le;

        $this->travel(5)->seconds();
        $this->service->marquer($institut, 'client', $admin, true);

        $institut->refresh();
        $this->assertSame('client', $institut->derniere_activite_type);
        $this->assertTrue($institut->derniere_activite_le->greaterThan($premiere));
    }

    public function test_oublier_le_throttle_permet_une_nouvelle_ecriture(): void
    {
        [$institut, $admin] = $this->creerInstitutEtAdmin();

        $this->service->marquer($institut, 'vente', $admin);
        $this->service->oublierThrottle($institut->id);

        $this->travel(2)->seconds();
        $this->service->marquer($institut, 'client', $admin);

        $this->assertSame('client', $institut->fresh()->derniere_activite_type);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Niveaux d'activité
    // ─────────────────────────────────────────────────────────────────────

    public function test_niveau_jamais_utilise(): void
    {
        [$institut] = $this->creerInstitutEtAdmin();

        $niveau = $this->service->niveau($institut);

        $this->assertSame('jamais', $niveau['code']);
        $this->assertNull($niveau['jours']);
    }

    public function test_niveau_actif_recent_et_dormant(): void
    {
        [$institut] = $this->creerInstitutEtAdmin();

        $institut->update(['derniere_activite_le' => now()->subDays(2)]);
        $this->assertSame('actif', $this->service->niveau($institut->fresh())['code']);

        $institut->update(['derniere_activite_le' => now()->subDays(15)]);
        $this->assertSame('recent', $this->service->niveau($institut->fresh())['code']);

        $institut->update(['derniere_activite_le' => now()->subDays(45)]);
        $niveau = $this->service->niveau($institut->fresh());
        $this->assertSame('dormant', $niveau['code']);
        $this->assertSame('Dormant', $niveau['label']);
    }

    public function test_libelle_du_type_d_activite(): void
    {
        $this->assertSame('Vente', $this->service->libelleType('vente'));
        $this->assertSame('Rendez-vous', $this->service->libelleType('rendez_vous'));
        $this->assertSame('—', $this->service->libelleType(null));
    }

    // ─────────────────────────────────────────────────────────────────────
    // 4. Connexion utilisateur
    // ─────────────────────────────────────────────────────────────────────

    public function test_la_connexion_met_a_jour_derniere_connexion_et_activite(): void
    {
        [$institut, $admin] = $this->creerInstitutEtAdmin();

        $this->assertNull($admin->fresh()->derniere_connexion_le);

        $this->post(route('login'), [
            'login'    => $admin->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertNotNull($admin->fresh()->derniere_connexion_le);
        $this->assertSame('connexion', $institut->fresh()->derniere_activite_type);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 5. Page admin
    // ─────────────────────────────────────────────────────────────────────

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'actif' => true]);
    }

    public function test_la_fiche_admin_affiche_la_derniere_activite(): void
    {
        [$institut, $admin] = $this->creerInstitutEtAdmin();

        $institut->update([
            'derniere_activite_le'      => now()->subDays(3),
            'derniere_activite_type'    => 'vente',
            'derniere_activite_user_id' => $admin->id,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.instituts.show', $institut))
            ->assertOk()
            ->assertSee('Dernière activité')
            ->assertSee($institut->fresh()->derniere_activite_le->format('d/m/Y à H:i'))
            ->assertSee('Vente')
            ->assertSee('Actif');
    }

    public function test_la_fiche_admin_signale_un_etablissement_jamais_utilise(): void
    {
        [$institut] = $this->creerInstitutEtAdmin();

        $this->actingAs($this->superAdmin())
            ->get(route('admin.instituts.show', $institut))
            ->assertOk()
            ->assertSee('Jamais utilisé');
    }

    public function test_le_filtre_activite_isole_les_etablissements_dormants(): void
    {
        [$actif]   = $this->creerInstitutEtAdmin();
        [$dormant] = $this->creerInstitutEtAdmin();
        [$jamais]  = $this->creerInstitutEtAdmin();

        $actif->update(['nom' => 'Salon Actif', 'derniere_activite_le' => now()->subDay()]);
        $dormant->update(['nom' => 'Salon Dormant', 'derniere_activite_le' => now()->subDays(45)]);
        $jamais->update(['nom' => 'Salon Jamais']);

        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.instituts.index', ['activite' => 'actif']))
            ->assertOk()
            ->assertSee('Salon Actif')
            ->assertDontSee('Salon Dormant');

        $this->actingAs($admin)
            ->get(route('admin.instituts.index', ['activite' => 'inactif']))
            ->assertOk()
            ->assertSee('Salon Dormant')
            ->assertDontSee('Salon Actif');

        $this->actingAs($admin)
            ->get(route('admin.instituts.index', ['activite' => 'jamais']))
            ->assertOk()
            ->assertSee('Salon Jamais')
            ->assertDontSee('Salon Actif');
    }
}
