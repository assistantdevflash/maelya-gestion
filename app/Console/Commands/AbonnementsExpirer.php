<?php

namespace App\Console\Commands;

use App\Mail\AbonnementExpire;
use App\Mail\RappelAbonnement;
use App\Models\Abonnement;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AbonnementsExpirer extends Command
{
    protected $signature   = 'abonnements:expirer';
    protected $description = 'Marque comme expirés les abonnements dont la date est dépassée et envoie les emails de rappel/expiration.';

    public function handle(): int
    {
        // ── 1. Rappels J-3 et J-1 ─────────────────────────────────────────────
        // On envoie un rappel pour les abonnements payants qui expirent dans exactement 3 jours ou 1 jour.
        $rappels = 0;
        foreach ([3, 1] as $jours) {
            $date = now()->addDays($jours)->toDateString();

            Abonnement::where('statut', 'actif')
                ->whereDate('expire_le', $date)
                ->whereHas('plan', fn ($q) => $q->where('duree_type', '!=', 'essai'))
                ->with(['user', 'plan'])
                ->get()
                ->each(function (Abonnement $abo) use ($jours, &$rappels) {
                    if ($abo->user?->email) {
                        Mail::to($abo->user->email)->send(new RappelAbonnement($abo, $jours));
                        try {
                            app(PushNotificationService::class)->sendToUser(
                                $abo->user,
                                '⏰ Abonnement bientôt expiré',
                                'Votre abonnement expire dans ' . $jours . ' jour' . ($jours > 1 ? 's' : '') . '. Renouvelez-le dès maintenant.',
                                '/abonnement/plans'
                            );
                        } catch (\Throwable $e) { Log::warning('[Push] ' . $e->getMessage()); }
                        $rappels++;
                    }
                });
        }

        // ── 2. Expiration + email J0 ───────────────────────────────────────────
        $aExpirer = Abonnement::where('statut', 'actif')
            ->whereDate('expire_le', '<', now()->toDateString())
            ->with(['user', 'plan'])
            ->get();

        $expires = 0;
        foreach ($aExpirer as $abo) {
            $abo->update(['statut' => 'expire']);

            // Envoyer le mail d'expiration uniquement pour les plans payants
            if ($abo->plan?->duree_type !== 'essai' && $abo->user?->email) {
                Mail::to($abo->user->email)->send(new AbonnementExpire($abo));
                try {
                    app(PushNotificationService::class)->sendToUser(
                        $abo->user,
                        '🔴 Abonnement expiré',
                        'Votre abonnement Maëlya Gestion est expiré. Renouvelez-le pour retrouver l\'accès complet.',
                        '/abonnement/plans'
                    );
                } catch (\Throwable $e) { Log::warning('[Push] ' . $e->getMessage()); }
            }

            $expires++;
        }

        $this->info("{$expires} abonnement(s) expiré(s), {$rappels} rappel(s) envoyé(s).");

        return self::SUCCESS;
    }
}
