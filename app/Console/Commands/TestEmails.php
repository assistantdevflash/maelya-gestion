<?php

namespace App\Console\Commands;

use App\Mail\BienvenueMaelya;
use App\Mail\NouvelleDemandeAbonnement;
use App\Models\Abonnement;
use App\Models\User;
use App\Notifications\ResetPasswordMaelya;
use App\Notifications\VerifyEmailMaelya;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmails extends Command
{
    protected $signature = 'test:emails {email}';
    protected $description = 'Envoyer tous les modèles d\'email de test à une adresse';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user  = User::first();

        // 1. Bienvenue
        $this->info('📧 [1/4] Envoi du modèle "Bienvenue"...');
        if ($user) {
            Mail::to($email)->send(new BienvenueMaelya($user));
            $this->info('   ✅ Envoyé !');
        } else {
            $this->warn('   ⚠️  Aucun utilisateur en BDD.');
        }

        // 2. Vérification d'email
        $this->info('📧 [2/4] Envoi du modèle "Vérification d\'email"...');
        if ($user) {
            $tempUser = $user->replicate();
            $tempUser->email = $email;
            $tempUser->id = $user->id;
            $tempUser->notify(new VerifyEmailMaelya);
            $this->info('   ✅ Envoyé !');
        } else {
            $this->warn('   ⚠️  Aucun utilisateur en BDD.');
        }

        // 3. Réinitialisation de mot de passe
        $this->info('📧 [3/4] Envoi du modèle "Réinitialisation de mot de passe"...');
        if ($user) {
            $tempUser = $user->replicate();
            $tempUser->email = $email;
            $tempUser->id = $user->id;
            $tempUser->notify(new ResetPasswordMaelya('fake-token-for-test-preview'));
            $this->info('   ✅ Envoyé !');
        } else {
            $this->warn('   ⚠️  Aucun utilisateur en BDD.');
        }

        // 4. Nouvelle demande d'abonnement
        $this->info('📧 [4/4] Envoi du modèle "Nouvelle demande d\'abonnement"...');
        $abonnement = Abonnement::with(['user', 'plan'])->latest()->first();
        if ($abonnement) {
            Mail::to($email)->send(new NouvelleDemandeAbonnement($abonnement));
            $this->info('   ✅ Envoyé !');
        } else {
            $this->warn('   ⚠️  Aucun abonnement en BDD — email ignoré.');
        }

        $this->newLine();
        $this->info("🎉 Tous les modèles ont été envoyés à {$email}");

        return self::SUCCESS;
    }
}
