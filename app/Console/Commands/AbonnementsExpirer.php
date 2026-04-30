<?php

namespace App\Console\Commands;

use App\Models\Abonnement;
use Illuminate\Console\Command;

class AbonnementsExpirer extends Command
{
    protected $signature   = 'abonnements:expirer';
    protected $description = 'Marque comme expirés tous les abonnements actifs dont la date est dépassée.';

    public function handle(): int
    {
        $count = Abonnement::where('statut', 'actif')
            ->whereDate('expire_le', '<', now()->toDateString())
            ->update(['statut' => 'expire']);

        $this->info("$count abonnement(s) marqué(s) comme expirés.");

        return self::SUCCESS;
    }
}
