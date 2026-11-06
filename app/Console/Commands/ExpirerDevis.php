<?php

namespace App\Console\Commands;

use App\Models\Devis;
use Illuminate\Console\Command;

/**
 * Passe en « expiré » les devis dont la date de validité est dépassée.
 *
 * Seuls les devis encore en cours (brouillon / envoyé) sont concernés : un
 * devis accepté reste accepté, un devis refusé reste refusé.
 */
class ExpirerDevis extends Command
{
    protected $signature = 'devis:expirer {--dry-run : Simule sans rien modifier}';

    protected $description = 'Marque comme expirés les devis dont la date de validité est dépassée.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = Devis::withoutGlobalScope(\App\Scopes\InstitutScope::class)
            ->whereIn('statut', ['brouillon', 'envoye'])
            ->whereDate('date_expiration', '<', now()->toDateString());

        $total = $query->count();

        if ($total === 0) {
            $this->info('Aucun devis à expirer.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[simulation] {$total} devis seraient marqués comme expirés.");
            return self::SUCCESS;
        }

        $query->update(['statut' => 'expire']);

        $this->info("{$total} devis marqués comme expirés.");

        return self::SUCCESS;
    }
}
