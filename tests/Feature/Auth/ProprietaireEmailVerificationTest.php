<?php

namespace Tests\Feature\Auth;

use App\Mail\CodeVerificationEmail;
use App\Models\Institut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests du système de vérification email propriétaire.
 *
 * Scénarios couverts :
 *  1. Propriétaire non vérifié dans les 3 jours → accès autorisé
 *  2. Propriétaire non vérifié passé 3 jours → bloqué + redirection
 *  3. Propriétaire vérifié → accès normal
 *  4. Envoi du code → mail reçu, code stocké
 *  5. Code correct → email marqué vérifié
 *  6. Code incorrect → erreur
 *  7. Code expiré → erreur
 *  8. Employé bloqué si propriétaire non vérifié après 3 jours
 *  9. Employé autorisé dans le délai de grâce
 * 10. Super admin jamais bloqué
 * 11. Admin peut marquer email vérifié depuis l'espace super admin
 */
class ProprietaireEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    // ────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────

    /** Propriétaire non vérifié créé il y a $joursDepuis jours. */
    private function proprietaireNonVerifie(int $joursDepuis = 0): User
    {
        $proprietaire = $this->creerAdmin([
            'email_verified_at' => null,
            'created_at'        => now()->subDays($joursDepuis),
            'updated_at'        => now()->subDays($joursDepuis),
        ]);

        return $proprietaire;
    }

    /** Employé rattaché à l'institut du propriétaire donné. */
    private function employe(User $proprietaire): User
    {
        return User::factory()->create([
            'role'        => 'employe',
            'actif'       => true,
            'institut_id' => $proprietaire->institut_id,
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // 1. Propriétaire non vérifié DANS le délai de grâce → accès OK
    // ────────────────────────────────────────────────────────────────

    public function test_proprietaire_non_verifie_dans_delai_grace_accede_au_dashboard(): void
    {
        $proprietaire = $this->proprietaireNonVerifie(joursDepuis: 1);

        $response = $this->actingAs($proprietaire)->get(route('dashboard.index'));

        $response->assertOk();
    }

    // ────────────────────────────────────────────────────────────────
    // 2. Propriétaire non vérifié APRÈS 3 jours → bloqué
    // ────────────────────────────────────────────────────────────────

    public function test_proprietaire_non_verifie_apres_3_jours_est_redirige(): void
    {
        $proprietaire = $this->proprietaireNonVerifie(joursDepuis: 4);

        $response = $this->actingAs($proprietaire)->get(route('dashboard.index'));

        $response->assertRedirectToRoute('verification.email');
    }

    public function test_proprietaire_bloque_peut_acceder_page_verification(): void
    {
        $proprietaire = $this->proprietaireNonVerifie(joursDepuis: 4);

        $response = $this->actingAs($proprietaire)->get(route('verification.email'));

        $response->assertOk();
    }

    // ────────────────────────────────────────────────────────────────
    // 3. Propriétaire avec email vérifié → accès normal
    // ────────────────────────────────────────────────────────────────

    public function test_proprietaire_verifie_accede_au_dashboard(): void
    {
        $proprietaire = $this->creerAdmin(['email_verified_at' => now()]);

        $response = $this->actingAs($proprietaire)->get(route('dashboard.index'));

        $response->assertOk();
    }

    // ────────────────────────────────────────────────────────────────
    // 4. Envoi du code de vérification
    // ────────────────────────────────────────────────────────────────

    public function test_envoi_code_envoie_mail_et_stocke_code(): void
    {
        Mail::fake();

        $proprietaire = $this->proprietaireNonVerifie();

        $this->actingAs($proprietaire)->post(route('verification.email.envoyer'));

        Mail::assertSent(CodeVerificationEmail::class, fn ($mail) =>
            $mail->hasTo($proprietaire->email)
        );

        $proprietaire->refresh();
        $this->assertNotNull($proprietaire->code_verification_email);
        $this->assertEquals(4, strlen($proprietaire->code_verification_email));
        $this->assertTrue(ctype_digit($proprietaire->code_verification_email));
        $this->assertTrue($proprietaire->code_verification_expire_le->isFuture());
    }

    // ────────────────────────────────────────────────────────────────
    // 5. Code correct → email vérifié
    // ────────────────────────────────────────────────────────────────

    public function test_code_correct_marque_email_comme_verifie(): void
    {
        $proprietaire = $this->proprietaireNonVerifie();
        $proprietaire->update([
            'code_verification_email'     => '1234',
            'code_verification_expire_le' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($proprietaire)
            ->post(route('verification.email.verifier'), ['code' => '1234']);

        $response->assertRedirectToRoute('dashboard.index');
        $proprietaire->refresh();
        $this->assertNotNull($proprietaire->email_verified_at);
        $this->assertNull($proprietaire->code_verification_email);
    }

    // ────────────────────────────────────────────────────────────────
    // 6. Code incorrect → erreur de validation
    // ────────────────────────────────────────────────────────────────

    public function test_code_incorrect_retourne_erreur(): void
    {
        $proprietaire = $this->proprietaireNonVerifie();
        $proprietaire->update([
            'code_verification_email'     => '9999',
            'code_verification_expire_le' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($proprietaire)
            ->post(route('verification.email.verifier'), ['code' => '1111']);

        $response->assertSessionHasErrors('code');
        $this->assertNull($proprietaire->fresh()->email_verified_at);
    }

    // ────────────────────────────────────────────────────────────────
    // 7. Code expiré → erreur
    // ────────────────────────────────────────────────────────────────

    public function test_code_expire_retourne_erreur(): void
    {
        $proprietaire = $this->proprietaireNonVerifie();
        $proprietaire->update([
            'code_verification_email'     => '5678',
            'code_verification_expire_le' => now()->subMinute(),
        ]);

        $response = $this->actingAs($proprietaire)
            ->post(route('verification.email.verifier'), ['code' => '5678']);

        $response->assertSessionHasErrors('code');
        $this->assertNull($proprietaire->fresh()->email_verified_at);
    }

    // ────────────────────────────────────────────────────────────────
    // 8. Employé bloqué si propriétaire non vérifié après 3 jours
    // ────────────────────────────────────────────────────────────────

    public function test_employe_est_bloque_si_proprietaire_non_verifie_apres_3_jours(): void
    {
        $proprietaire = $this->proprietaireNonVerifie(joursDepuis: 4);
        $employe      = $this->employe($proprietaire);

        $response = $this->actingAs($employe)->get(route('dashboard.index'));

        $response->assertRedirectToRoute('verification.email');
    }

    public function test_employe_bloque_voit_message_propriataire(): void
    {
        $proprietaire = $this->proprietaireNonVerifie(joursDepuis: 4);
        $employe      = $this->employe($proprietaire);

        // Suivre la redirection pour vérifier la session flash
        $response = $this->actingAs($employe)
            ->get(route('dashboard.index'))
            ->assertRedirectToRoute('verification.email');

        $response->assertSessionHas('bloque_par_proprietaire', true);
    }

    // ────────────────────────────────────────────────────────────────
    // 9. Employé autorisé si propriétaire dans le délai de grâce
    // ────────────────────────────────────────────────────────────────

    public function test_employe_accede_si_proprietaire_dans_delai_grace(): void
    {
        $proprietaire = $this->proprietaireNonVerifie(joursDepuis: 1);
        $employe      = $this->employe($proprietaire);

        $response = $this->actingAs($employe)->get(route('dashboard.index'));

        // L'employé a un abonnement via le propriétaire (middleware abonnement.actif)
        // On ne teste que la non-redirection vers verification.email
        $this->assertNotEquals(route('verification.email'), $response->headers->get('Location'));
    }

    // ────────────────────────────────────────────────────────────────
    // 10. Super admin jamais bloqué
    // ────────────────────────────────────────────────────────────────

    public function test_super_admin_jamais_bloque(): void
    {
        $superAdmin = User::factory()->create([
            'role'              => 'super_admin',
            'actif'             => true,
            'email_verified_at' => null,
            'created_at'        => now()->subDays(30),
        ]);

        $response = $this->actingAs($superAdmin)->get('/admin/emails');

        // Le super admin accède à l'espace admin sans blocage email
        $response->assertOk();
    }

    // ────────────────────────────────────────────────────────────────
    // 11. Super admin peut marquer un email comme vérifié
    // ────────────────────────────────────────────────────────────────

    public function test_super_admin_peut_marquer_email_comme_verifie(): void
    {
        $superAdmin   = User::factory()->create(['role' => 'super_admin', 'actif' => true]);
        $proprietaire = $this->proprietaireNonVerifie();

        $response = $this->actingAs($superAdmin)
            ->patch(route('admin.users.verifier-email', $proprietaire));

        $response->assertRedirect();
        $this->assertNotNull($proprietaire->fresh()->email_verified_at);
    }
}
