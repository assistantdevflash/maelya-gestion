<?php

namespace Tests\Feature\Commercial;

use App\Models\CommercialParrainage;
use App\Models\CommercialProfile;
use App\Models\Institut;
use App\Models\Parrainage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests du code de parrainage/commercial à l'inscription.
 */
class InscriptionCodeCommercialTest extends TestCase
{
    use RefreshDatabase;

    private array $base = [
        'nom_institut'      => 'Salon Test',
        'type_institut'     => 'salon_coiffure',
        'ville'             => 'Abidjan',
        'telephone_institut'=> '0102030405',
        'prenom'            => 'Fatou',
        'nom_famille'       => 'Diallo',
        'telephone'         => '0707070707',
        'password'          => 'Password123!',
        'password_confirmation' => 'Password123!',
        'cgu'               => '1',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('commercial_config')->insertOrIgnore(['taux' => 20, 'duree_mois' => 6]);
        Mail::fake();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function creerCommercialActif(string $code = 'TEST01'): CommercialProfile
    {
        $user = User::factory()->create([
            'role'  => 'commercial',
            'actif' => true,
        ]);

        return CommercialProfile::create([
            'user_id' => $user->id,
            'code'    => strtoupper($code),
        ]);
    }

    private function creerParrain(string $code = 'PARR01'): User
    {
        $parrain = $this->creerAdmin(['code_parrainage' => strtoupper($code)]);
        return $parrain;
    }

    // =========================================================================
    // Code commercial valide
    // =========================================================================

    public function test_code_commercial_valide_cree_un_parrainage_commercial(): void
    {
        $profil = $this->creerCommercialActif('COMTEST');

        $this->post(route('inscription.store'), array_merge($this->base, [
            'email'           => 'fatou@example.com',
            'code_parrainage' => 'COMTEST',
        ]))->assertRedirect(route('dashboard.index'));

        $nouvelUtilisateur = User::where('email', 'fatou@example.com')->first();
        $this->assertNotNull($nouvelUtilisateur);

        $this->assertDatabaseHas('commercial_parrainages', [
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $nouvelUtilisateur->id,
        ]);
    }

    public function test_code_commercial_ne_cree_pas_de_parrainage_classique(): void
    {
        $this->creerCommercialActif('COMTEST');

        $this->post(route('inscription.store'), array_merge($this->base, [
            'email'           => 'fatou@example.com',
            'code_parrainage' => 'COMTEST',
        ]));

        $nouvelUtilisateur = User::where('email', 'fatou@example.com')->first();
        $this->assertNotNull($nouvelUtilisateur);

        $this->assertDatabaseMissing('parrainages', [
            'filleul_id' => $nouvelUtilisateur->id,
        ]);
    }

    public function test_parrainage_commercial_a_la_bonne_duree_expiration(): void
    {
        DB::table('commercial_config')->update(['duree_mois' => 3]);
        $profil = $this->creerCommercialActif('COMTEST');

        $this->post(route('inscription.store'), array_merge($this->base, [
            'email'           => 'fatou@example.com',
            'code_parrainage' => 'COMTEST',
        ]));

        $nouvelUtilisateur = User::where('email', 'fatou@example.com')->first();
        $parrainage = CommercialParrainage::where('proprietaire_id', $nouvelUtilisateur->id)->first();

        $this->assertNotNull($parrainage);

        $attendu = now()->addMonths(3)->toDateString();
        $this->assertEquals($attendu, $parrainage->expire_le->toDateString());
    }

    // =========================================================================
    // Code parrain classique valide
    // =========================================================================

    public function test_code_parrain_classique_valide_cree_parrainage_classique(): void
    {
        $parrain = $this->creerParrain('PARR01');

        $this->post(route('inscription.store'), array_merge($this->base, [
            'email'           => 'fatou@example.com',
            'code_parrainage' => 'PARR01',
        ]))->assertRedirect(route('dashboard.index'));

        $nouvelUtilisateur = User::where('email', 'fatou@example.com')->first();
        $this->assertNotNull($nouvelUtilisateur);

        $this->assertDatabaseHas('parrainages', [
            'parrain_id' => $parrain->id,
            'filleul_id' => $nouvelUtilisateur->id,
        ]);
    }

    public function test_code_parrain_classique_ne_cree_pas_de_parrainage_commercial(): void
    {
        $this->creerParrain('PARR01');

        $this->post(route('inscription.store'), array_merge($this->base, [
            'email'           => 'fatou@example.com',
            'code_parrainage' => 'PARR01',
        ]));

        $nouvelUtilisateur = User::where('email', 'fatou@example.com')->first();
        $this->assertNotNull($nouvelUtilisateur);

        $this->assertDatabaseMissing('commercial_parrainages', [
            'proprietaire_id' => $nouvelUtilisateur->id,
        ]);
    }

    // =========================================================================
    // Code invalide
    // =========================================================================

    public function test_code_invalide_retourne_erreur_de_validation(): void
    {
        $response = $this->post(route('inscription.store'), array_merge($this->base, [
            'email'           => 'fatou@example.com',
            'code_parrainage' => 'INEXISTANT',
        ]));

        $response->assertSessionHasErrors('code_parrainage');

        $this->assertDatabaseMissing('users', ['email' => 'fatou@example.com']);
    }

    public function test_code_invalide_message_erreur_correct(): void
    {
        $response = $this->post(route('inscription.store'), array_merge($this->base, [
            'email'           => 'fatou@example.com',
            'code_parrainage' => 'INVALIDE',
        ]));

        $response->assertSessionHasErrors([
            'code_parrainage' => 'Ce code est invalide ou n\'existe pas.',
        ]);
    }

    // =========================================================================
    // Code absent (champ vide = optionnel)
    // =========================================================================

    public function test_inscription_sans_code_fonctionne_normalement(): void
    {
        $this->post(route('inscription.store'), array_merge($this->base, [
            'email' => 'fatou@example.com',
        ]))->assertRedirect(route('dashboard.index'));

        $this->assertDatabaseHas('users', ['email' => 'fatou@example.com']);
        $this->assertDatabaseEmpty('commercial_parrainages');
        $this->assertDatabaseEmpty('parrainages');
    }

    // =========================================================================
    // Code commercial d'un commercial inactif
    // =========================================================================

    public function test_code_commercial_inactif_retourne_erreur(): void
    {
        $user = User::factory()->create([
            'role'  => 'commercial',
            'actif' => false, // inactif
        ]);
        CommercialProfile::create([
            'user_id' => $user->id,
            'code'    => 'INACTIF',
        ]);

        $response = $this->post(route('inscription.store'), array_merge($this->base, [
            'email'           => 'fatou@example.com',
            'code_parrainage' => 'INACTIF',
        ]));

        $response->assertSessionHasErrors('code_parrainage');
    }

    // =========================================================================
    // Priorité : commercial > parrain classique
    // =========================================================================

    public function test_si_code_matche_commercial_et_parrain_commercial_est_prioritaire(): void
    {
        // Même code pour les deux — situation impossible en prod car les codes sont distincts,
        // mais on teste la logique de priorité
        $code = 'SAME01';

        $profilCommercial = $this->creerCommercialActif($code);
        // Crée un parrain classique avec le même code (edge case)
        User::factory()->create([
            'role'            => 'admin',
            'actif'           => true,
            'code_parrainage' => $code,
        ]);

        $this->post(route('inscription.store'), array_merge($this->base, [
            'email'           => 'fatou@example.com',
            'code_parrainage' => $code,
        ]));

        $nouvelUtilisateur = User::where('email', 'fatou@example.com')->first();
        $this->assertNotNull($nouvelUtilisateur);

        // Le parrainage commercial doit être créé
        $this->assertDatabaseHas('commercial_parrainages', [
            'commercial_id'   => $profilCommercial->id,
            'proprietaire_id' => $nouvelUtilisateur->id,
        ]);

        // Pas de parrainage classique
        $this->assertDatabaseMissing('parrainages', [
            'filleul_id' => $nouvelUtilisateur->id,
        ]);
    }
}
