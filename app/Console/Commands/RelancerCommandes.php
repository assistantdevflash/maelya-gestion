<?php

namespace App\Console\Commands;

use App\Mail\CommandeRelanceClient;
use App\Models\Commande;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Relance des commandes restées sans suite (« panier abandonné »).
 *
 * Une commande encore en statut « nouvelle » 24 h après sa création est
 * relancée une seule fois par e-mail ; le champ relance_envoyee_at sert
 * d'anti-doublon.
 */
class RelancerCommandes extends Command
{
    protected $signature = 'commandes:relances {--dry-run : Simule sans rien envoyer}';

    protected $description = 'Relance par e-mail les commandes non confirmées depuis plus de 24 h.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $commandes = Commande::withoutGlobalScope(\App\Scopes\InstitutScope::class)
            ->where('statut', 'nouvelle')
            ->where('payee', false)
            ->whereNull('relance_envoyee_at')
            ->where('created_at', '<=', now()->subDay())
            ->where('created_at', '>=', now()->subDays(7))
            ->with(['institut', 'items'])
            ->get();

        if ($commandes->isEmpty()) {
            $this->info('Aucune commande à relancer.');
            return self::SUCCESS;
        }

        $envoyees = 0;
        $ignorees = 0;

        foreach ($commandes as $commande) {
            if (! $commande->client_email) {
                $ignorees++;
                continue;
            }

            if ($dryRun) {
                $this->line("[simulation] {$commande->numero} → {$commande->client_email}");
                $envoyees++;
                continue;
            }

            try {
                Mail::to($commande->client_email)->send(new CommandeRelanceClient($commande));
                $commande->update(['relance_envoyee_at' => now()]);
                $envoyees++;
            } catch (\Throwable $e) {
                Log::warning('Relance commande échouée', [
                    'commande' => $commande->numero,
                    'erreur'   => $e->getMessage(),
                ]);
            }
        }

        $this->info("{$envoyees} relance(s) envoyée(s), {$ignorees} ignorée(s) faute d'e-mail.");

        return self::SUCCESS;
    }
}
