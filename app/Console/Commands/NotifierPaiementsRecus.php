<?php

namespace App\Console\Commands;

use App\Models\Notif;
use App\Models\PaymentTransaction;
use App\Services\Gateways\GeniusPayGateway;
use Illuminate\Console\Command;

class NotifierPaiementsRecus extends Command
{
    /**
     * Rattrape les paiements GeniusPay déjà complétés qui n'ont pas encore
     * généré de notification super-admin (email + in-app + push).
     *
     * Usage : php artisan paiements:notifier [--depuis=2026-08-01]
     */
    protected $signature = 'paiements:notifier {--depuis= : Date limite (Y-m-d) pour rattraper les transactions}';

    protected $description = 'Notifie les super admins des paiements GeniusPay complétés sans notification (email + push + in-app).';

    public function handle(): int
    {
        $depuis = $this->option('depuis');

        $query = PaymentTransaction::query()
            ->where('payment_method_code', 'geniuspay')
            ->where('status', 'completed');

        if ($depuis) {
            $query->whereDate('paid_at', '>=', $depuis);
        }

        // Ne garder que les transactions dont la référence n'a pas encore de notif "paiement_recu"
        $notifies = Notif::where('type', 'paiement_recu')->pluck('corps');

        $count = 0;
        $query->orderBy('paid_at')->get()
            ->filter(function (PaymentTransaction $tx) use ($notifies) {
                return ! $notifies->contains(fn ($corps) => str_contains($corps, $tx->reference));
            })
            ->each(function (PaymentTransaction $tx) use (&$count) {
                $this->line("  → {$tx->reference} ({$tx->type}) — {$tx->amount} FCFA");
                app(GeniusPayGateway::class)->notifierAdminsPaiementRecu($tx);
                $count++;
            });

        $this->info("Terminé : {$count} paiement(s) notifié(s).");

        return self::SUCCESS;
    }
}
