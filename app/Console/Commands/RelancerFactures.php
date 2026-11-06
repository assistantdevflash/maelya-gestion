<?php

namespace App\Console\Commands;

use App\Mail\FactureARelancer;
use App\Models\Facture;
use App\Models\FactureRelance;
use App\Models\Institut;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Cascade de relances automatiques des factures impayées.
 *
 * Paliers : J-3 (rappel courtois avant échéance), puis J+1, J+7, J+15 et J+30
 * après l'échéance. Une relance n'est envoyée qu'une fois par
 * (facture, palier, canal) — la table facture_relances sert d'anti-doublon.
 */
class RelancerFactures extends Command
{
    protected $signature = 'factures:relances {--dry-run : Simule sans rien envoyer}';

    protected $description = 'Envoie les relances automatiques des factures impayées (J-3, J+1, J+7, J+15, J+30).';

    /** Palier => nombre de jours (négatif = avant l'échéance). */
    private const PALIERS = [
        'j-3'  => -3,
        'j+1'  => 1,
        'j+7'  => 7,
        'j+15' => 15,
        'j+30' => 30,
    ];

    public function handle(): int
    {
        $dryRun   = (bool) $this->option('dry-run');
        $today    = Carbon::today();
        $envoyees = 0;
        $ignorees = 0;

        $instituts = Institut::where('facture_relance_active', true)->get(['id', 'nom']);

        foreach ($instituts as $institut) {
            $factures = Facture::withoutGlobalScope(\App\Scopes\InstitutScope::class)
                ->where('institut_id', $institut->id)
                ->whereNotIn('statut', ['payee', 'annulee'])
                ->whereNotNull('date_echeance')
                ->with(['client', 'institut'])
                ->get();

            foreach ($factures as $facture) {
                $jours = (int) Carbon::parse($facture->date_echeance)->startOfDay()
                    ->diffInDays($today, false);

                $palier = array_search($jours, self::PALIERS, true);
                if ($palier === false) {
                    continue;
                }

                $email = $facture->client_email ?: ($facture->client->email ?? null);
                if (! $email) {
                    $ignorees++;
                    continue;
                }

                // Anti-doublon : une seule relance par palier et par canal
                $dejaEnvoyee = FactureRelance::withoutGlobalScope(\App\Scopes\InstitutScope::class)
                    ->where('facture_id', $facture->id)
                    ->where('palier', $palier)
                    ->where('canal', 'email')
                    ->exists();

                if ($dejaEnvoyee) {
                    $ignorees++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("[simulation] {$palier} → {$facture->numero} ({$email})");
                    $envoyees++;
                    continue;
                }

                try {
                    Mail::to($email)->send(new FactureARelancer($facture, $institut->facture_relance_message));

                    FactureRelance::create([
                        'facture_id'  => $facture->id,
                        'institut_id' => $institut->id,
                        'canal'       => 'email',
                        'palier'      => $palier,
                        'envoye_le'   => now(),
                    ]);

                    $envoyees++;
                } catch (\Throwable $e) {
                    Log::warning('Relance facture échouée', [
                        'facture' => $facture->numero,
                        'erreur'  => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->info("{$envoyees} relance(s) envoyée(s), {$ignorees} ignorée(s).");

        return self::SUCCESS;
    }
}
