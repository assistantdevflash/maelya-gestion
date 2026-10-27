<?php

namespace App\Console\Commands;

use App\Mail\RapportMensuelInstitut;
use App\Models\Institut;
use App\Services\RapportMensuelService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie à chaque établissement le bilan financier du mois écoulé.
 *
 * Le mail contient les chiffres du mois, la comparaison avec le mois
 * précédent et un conseil marketing choisi d'après les résultats.
 */
class EnvoyerRapportMensuel extends Command
{
    protected $signature = 'rapports:mensuel
        {--mois= : Mois à analyser au format A-M (défaut : mois écoulé)}
        {--institut= : Limiter à un établissement (identifiant)}
        {--tout : Envoyer aussi aux établissements sans activité sur la période}
        {--dry-run : Simuler sans envoyer d\'email}';

    protected $description = 'Envoie par email le rapport financier mensuel de chaque établissement.';

    public function handle(RapportMensuelService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // ── Mois analysé ────────────────────────────────────────────────────
        $moisOption = $this->option('mois');
        if ($moisOption) {
            try {
                $mois = Carbon::createFromFormat('!Y-m', $moisOption);
            } catch (\Throwable $e) {
                $this->error("Format de mois invalide : {$moisOption}. Attendu : 2026-09");

                return self::FAILURE;
            }
        } else {
            $mois = now()->subMonthNoOverflow();
        }
        $mois = $mois->startOfMonth();

        // ── Établissements concernés ────────────────────────────────────────
        $instituts = Institut::query()
            ->where('actif', true)
            ->where('en_sommeil', false)
            ->when($this->option('institut'), fn ($q, $id) => $q->whereKey($id))
            ->orderBy('nom')
            ->get();

        if ($instituts->isEmpty()) {
            $this->warn('Aucun établissement actif à traiter.');

            return self::SUCCESS;
        }

        $this->info(
            ($dryRun ? '[Simulation] ' : '')
            . 'Rapport de ' . ucfirst($mois->translatedFormat('F Y'))
            . ' — ' . $instituts->count() . ' établissement(s) à traiter.'
        );

        $envoyes  = 0;
        $ignores  = 0;
        $erreurs  = 0;
        $lignes   = [];

        foreach ($instituts as $institut) {
            $proprietaire = $institut->proprietaireEffectif();

            if (!$proprietaire || !$proprietaire->email) {
                $ignores++;
                Log::info("[rapports:mensuel] Aucun destinataire pour « {$institut->nom} ».");
                continue;
            }

            $rapport = $service->construire($institut, $mois);

            // Un établissement inactif n'a pas besoin d'un bilan vide
            if (!$rapport['a_activite'] && !$this->option('tout')) {
                $ignores++;
                continue;
            }

            $lignes[] = [
                $institut->nom,
                $proprietaire->email,
                number_format($rapport['actuel']['ca'], 0, ',', ' ') . ' F',
                number_format($rapport['actuel']['benefice'], 0, ',', ' ') . ' F',
                $rapport['conseil']['titre'],
            ];

            if ($dryRun) {
                $envoyes++;
                continue;
            }

            try {
                Mail::to($proprietaire->email)
                    ->send(new RapportMensuelInstitut($rapport, $proprietaire));
                $envoyes++;
            } catch (\Throwable $e) {
                $erreurs++;
                Log::error("[rapports:mensuel] Échec pour {$institut->nom} : " . $e->getMessage());
                $this->error("Échec pour {$institut->nom} : " . $e->getMessage());
            }
        }

        if ($lignes) {
            $this->table(['Établissement', 'Destinataire', 'CA', 'Bénéfice', 'Conseil'], $lignes);
        }

        $this->info(sprintf(
            '%s%d rapport(s) %s, %d ignoré(s), %d erreur(s).',
            $dryRun ? '[Simulation] ' : '',
            $envoyes,
            $dryRun ? 'à envoyer' : 'envoyé(s)',
            $ignores,
            $erreurs
        ));

        if ($ignores > 0 && !$this->option('tout')) {
            $this->line('Astuce : --tout envoie aussi un bilan aux établissements sans activité.');
        }

        return $erreurs > 0 ? self::FAILURE : self::SUCCESS;
    }
}
