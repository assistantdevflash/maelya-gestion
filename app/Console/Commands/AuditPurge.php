<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AuditPurge extends Command
{
    protected $signature   = 'audit:purge {--days=90 : Nombre de jours d\'historique à conserver}';
    protected $description = 'Supprime les entrées du journal d\'activité antérieures à N jours.';

    public function handle(): int
    {
        $days      = (int) $this->option('days');
        $cutoff    = now()->subDays($days);
        $batchSize = 500;
        $total     = 0;

        $this->info("Purge des entrées audit_logs antérieures au {$cutoff->format('d/m/Y')} (> {$days} jours)…");

        do {
            $deleted = AuditLog::where('created_at', '<', $cutoff)
                ->limit($batchSize)
                ->delete();
            $total += $deleted;
        } while ($deleted === $batchSize);

        $this->info("✓ {$total} entrée(s) supprimée(s).");
        Log::info("[audit:purge] {$total} entrées supprimées (seuil : {$days} jours).");

        return self::SUCCESS;
    }
}
