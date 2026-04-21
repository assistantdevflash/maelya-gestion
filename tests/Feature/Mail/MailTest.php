<?php

namespace Tests\Feature\Mail;

use App\Mail\BienvenueMaelya;
use App\Notifications\ResetPasswordMaelya;
use App\Notifications\VerifyEmailMaelya;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MailTest extends TestCase
{
    use RefreshDatabase;

    // ── BienvenueMaelya ───────────────────────────────────────────────────────

    public function test_bienvenue_mailable_peut_etre_instancie(): void
    {
        $user     = $this->creerAdmin(['prenom' => 'Awa', 'nom_famille' => 'Koné']);
        $mailable = new BienvenueMaelya($user);

        $this->assertSame('emails.bienvenue', $mailable->content()->view);
    }

    public function test_bienvenue_sujet_contient_prenom(): void
    {
        $user     = $this->creerAdmin(['prenom' => 'Awa']);
        $mailable = new BienvenueMaelya($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Awa', $envelope->subject);
        $this->assertStringContainsString('Bienvenue', $envelope->subject);
    }

    public function test_bienvenue_sujet_utilise_name_si_pas_de_prenom(): void
    {
        $user = $this->creerAdmin(['prenom' => '', 'name' => 'Marie Admin']);
        $mailable = new BienvenueMaelya($user);
        $envelope = $mailable->envelope();

        $this->assertStringContainsString('Marie Admin', $envelope->subject);
    }

    public function test_bienvenue_est_envoye(): void
    {
        Mail::fake();

        $user = $this->creerAdmin();
        Mail::to($user->email)->send(new BienvenueMaelya($user));

        Mail::assertSent(BienvenueMaelya::class, fn($m) => $m->hasTo($user->email));
    }

    // ── VerifyEmailMaelya ─────────────────────────────────────────────────────

    public function test_verify_email_notification_peut_etre_envoyee(): void
    {
        Notification::fake();

        $user = $this->creerAdmin();
        $user->notify(new VerifyEmailMaelya);

        Notification::assertSentTo($user, VerifyEmailMaelya::class);
    }

    // ── ResetPasswordMaelya ───────────────────────────────────────────────────

    public function test_reset_password_notification_peut_etre_envoyee(): void
    {
        Notification::fake();

        $user = $this->creerAdmin();
        $user->notify(new ResetPasswordMaelya('fake-token'));

        Notification::assertSentTo($user, ResetPasswordMaelya::class);
    }

    // ── User overrides ────────────────────────────────────────────────────────

    public function test_send_email_verification_utilise_la_notification_custom(): void
    {
        Notification::fake();

        $user = $this->creerAdmin();
        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmailMaelya::class);
    }

    public function test_send_password_reset_utilise_la_notification_custom(): void
    {
        Notification::fake();

        $user = $this->creerAdmin();
        $user->sendPasswordResetNotification('token-test');

        Notification::assertSentTo($user, ResetPasswordMaelya::class);
    }
}
