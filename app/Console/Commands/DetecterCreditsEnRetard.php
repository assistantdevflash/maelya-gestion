<?php

namespace App\Console\Commands;

use App\Models\Echeance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DetecterCreditsEnRetard extends Command
{
    protected $signature = 'credits:detecter-retards';
    protected $description = 'Détecte les échéances en retard et met à jour les statuts des crédits';

    public function handle()
    {
        $echeances = Echeance::where('statut', 'en_attente')
            ->where('date_prevue', '<', now()->toDateString())
            ->get();

        $count = 0;
        foreach ($echeances as $e) {
            DB::transaction(function () use ($e, &$count) {
                $e->statut = 'retard';
                $e->save();
                $e->credit->statut = 'retard';
                $e->credit->save();
                $creditVente = $e->credit->vente;
                if ($creditVente) {
                    $creditVente->credit_statut = 'retard';
                    $creditVente->save();
                }
                $count++;
            });
        }

        $this->info("{$count} échéance(s) marquée(s) en retard.");
    }
}
