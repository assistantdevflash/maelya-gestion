<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class ClientsRappelAnniversaire extends Command
{
    protected $signature   = 'clients:rappel-anniversaire {--jours=7}';
    protected $description = 'Notifie chaque institut des clients dont l\'anniversaire arrive dans N jours (par défaut 7).';

    public function handle(): int
    {
        $jours = (int) $this->option('jours');
        $cible = now()->addDays($jours)->format('m-d');

        $clients = Client::where('actif', true)
            ->whereNotNull('date_naissance')
            ->where('date_naissance', $cible)
            ->get();

        if ($clients->isEmpty()) {
            $this->info("Aucun client à notifier (J-{$jours}).");
            return self::SUCCESS;
        }

        $parInstitut = $clients->groupBy('institut_id');

        foreach ($parInstitut as $institutId => $liste) {
            $admin = User::where('institut_id', $institutId)
                ->where('role', 'admin')
                ->where('actif', true)
                ->first();
            if (! $admin) continue;

            $noms = $liste->map(fn ($c) => trim($c->prenom . ' ' . $c->nom))->implode(', ');

            NotificationService::notifyUser(
                $admin,
                'anniversaire_a_venir',
                '🎂 Anniversaires dans ' . $jours . ' jours',
                $liste->count() . ' client(s) : ' . $noms,
                '/dashboard/clients?mois_anniv=' . now()->addDays($jours)->format('m')
            );
        }

        $this->info('Notifications envoyées pour ' . $clients->count() . ' client(s).');
        return self::SUCCESS;
    }
}
