<?php

namespace App\Console\Commands;

use App\Models\MessageContact;
use App\Services\AntiSpamService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Rejoue le moteur anti-spam sur les messages déjà en base.
 *
 * Utile après la mise en place du filtre : les messages reçus avant n'ont
 * jamais été évalués. Le rejeu n'utilise que les signaux vérifiables
 * a posteriori (leurre, liens, mots-clés, email douteux) — les règles
 * temporelles et de répétition ne s'appliquent pas à l'historique.
 */
class ReclasserMessagesSpam extends Command
{
    protected $signature = 'messages:reclasser-spam
        {--dry-run : Afficher le résultat sans rien modifier}
        {--force : Réévaluer aussi les messages déjà classés (peut les réhabiliter)}
        {--seuil= : Score de déclenchement (défaut : seuil du service)}
        {--depuis= : Ne traiter que les messages reçus depuis cette date (A-M-J)}
        {--limite=0 : Nombre maximum de lignes à inspecter (0 = toutes)}
        {--afficher=20 : Nombre de messages détectés à afficher}';

    protected $description = 'Rejoue la détection de spam sur les messages de contact existants.';

    public function handle(AntiSpamService $service): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $forcer  = (bool) $this->option('force');
        $seuil   = (int) ($this->option('seuil') ?: AntiSpamService::SEUIL_SPAM);
        $depuis  = $this->option('depuis');
        $limite  = (int) $this->option('limite');
        $afficher = max(0, (int) $this->option('afficher'));

        if ($depuis && !strtotime($depuis)) {
            $this->error("Date invalide : {$depuis}. Format attendu : 2026-01-31.");

            return self::FAILURE;
        }

        $requete = MessageContact::query()
            ->when($depuis, fn ($q) => $q->where('created_at', '>=', \Carbon\Carbon::parse($depuis)->startOfDay()))
            // Par défaut : on ne touche pas à ce qui a déjà été tranché
            ->when(!$forcer, fn ($q) => $q->where('est_spam', false))
            ->orderBy('created_at')
            ->orderBy('id');

        $total = (clone $requete)->count();
        if ($limite > 0) {
            $total = min($total, $limite);
        }

        if ($total === 0) {
            $this->info('Aucun message à réévaluer.');
            if (!$forcer) {
                $this->line('Astuce : utilise --force pour réévaluer aussi les messages déjà classés.');
            }

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[Simulation] ' : '') . "Analyse de {$total} message(s) — seuil {$seuil}…");

        $barre = $this->output->createProgressBar($total);
        $barre->start();

        $detectes   = 0;
        $rehabilites = 0;
        $exemples   = [];
        $inspecte   = 0;

        $requete->chunk(200, function ($messages) use (
            $service, $seuil, $dryRun, $forcer, $barre,
            &$detectes, &$rehabilites, &$exemples, &$inspecte, $limite, $afficher
        ) {
            foreach ($messages as $message) {
                if ($limite > 0 && $inspecte >= $limite) {
                    return false;
                }
                $inspecte++;
                $barre->advance();

                $analyse = $service->analyser([
                    'nom'     => (string) $message->nom,
                    'email'   => (string) $message->email,
                    'message' => (string) $message->message,
                    'website' => (string) ($message->honeypot ?? ''),
                ], (string) ($message->ip_address ?? ''), verifierRepetition: false);

                $estSpam = $analyse['score'] >= $seuil;

                // Rien à changer
                if ($estSpam === (bool) $message->est_spam) {
                    continue;
                }

                if ($estSpam) {
                    $detectes++;
                    if (count($exemples) < $afficher) {
                        $exemples[] = [
                            $message->created_at?->format('d/m/Y H:i') ?? '—',
                            Str::limit($message->nom, 24),
                            Str::limit($message->email, 28),
                            $analyse['score'],
                            Str::limit(implode(' · ', $analyse['raisons']), 60),
                        ];
                    }
                } else {
                    $rehabilites++;
                }

                if ($dryRun) {
                    continue;
                }

                $message->forceFill([
                    'est_spam'   => $estSpam,
                    'spam_score' => min(255, $analyse['score']),
                    'spam_raison' => $analyse['raisons']
                        ? Str::limit(implode(' · ', $analyse['raisons']), 250)
                        : null,
                    // Un message reclassé ne doit pas gonfler le badge de non-lus
                    'lu' => $estSpam ? true : $message->lu,
                ])->save();
            }
        });

        $barre->finish();
        $this->newLine(2);

        if ($exemples) {
            $this->table(
                ['Reçu le', 'Nom', 'Email', 'Score', 'Raisons'],
                $exemples
            );
        }

        $this->info(sprintf(
            '%s%d message(s) classé(s) indésirable(s), %d réhabilité(s) sur %d inspecté(s).',
            $dryRun ? '[Simulation] ' : '',
            $detectes,
            $rehabilites,
            $inspecte
        ));

        if ($dryRun) {
            $this->warn('Aucune modification enregistrée (--dry-run). Relancez sans l\'option pour appliquer.');
        }

        return self::SUCCESS;
    }
}
