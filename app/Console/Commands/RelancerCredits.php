<?php

namespace App\Console\Commands;

use App\Mail\RelanceCredit;
use App\Models\Credit;
use App\Models\CreditRelance;
use App\Models\Institut;
use App\Models\User;
use App\Services\CreditService;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Cascade de relances automatiques des échéances de crédit.
 *
 * Paliers : J-3 (rappel courtois), puis J+1, J+7, J+15 et J+30 après l'échéance.
 * Une relance n'est envoyée qu'une fois par (crédit, palier, canal) — la table
 * credit_relances sert d'anti-doublon.
 *
 * Canaux :
 *  - email    : au client, s'il a une adresse ;
 *  - inapp    : notification interne + push pour l'établissement, avec le lien
 *               WhatsApp pré-rempli accessible depuis la fiche du crédit.
 */
class RelancerCredits extends Command
{
    protected $signature = 'credits:relances {--dry-run : Simule sans rien envoyer}';

    protected $description = 'Envoie les relances automatiques des échéances de crédit (J-3, J+1, J+7, J+15, J+30).';

    /** Palier => nombre de jours (négatif = avant l'échéance). */
    private const PALIERS = [
        'j-3'  => -3,
        'j+1'  => 1,
        'j+7'  => 7,
        'j+15' => 15,
        'j+30' => 30,
    ];

    public function handle(CreditService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $today  = Carbon::today();
        $envoyees = 0;
        $ignorees = 0;

        $instituts = Institut::where('credit_relance_active', true)->get(['id', 'nom']);

        foreach ($instituts as $institut) {
            $credits = Credit::withoutGlobalScope(\App\Scopes\InstitutScope::class)
                ->where('institut_id', $institut->id)
                ->where('statut', '!=', 'solde')
                ->with(['client', 'echeances'])
                ->get();

            foreach ($credits as $credit) {
                // Prochaine échéance non réglée
                $echeance = $credit->echeances
                    ->where('statut', '!=', 'payee')
                    ->sortBy('date_prevue')
                    ->first();

                if (! $echeance?->date_prevue) {
                    continue;
                }

                $jours = (int) Carbon::parse($echeance->date_prevue)->startOfDay()
                    ->diffInDays($today, false);

                $palier = array_search($jours, self::PALIERS, true);
                if ($palier === false) {
                    continue;
                }

                $type = $jours < 0 ? 'rappel' : 'relance';
                $message = $service->messageRelance($credit, $type);

                // ── Email au client ────────────────────────────────────────
                if ($credit->client?->email) {
                    $dejaEnvoye = CreditRelance::where('credit_id', $credit->id)
                        ->where('palier', $palier)->where('canal', 'email')->exists();

                    if ($dejaEnvoye) {
                        $ignorees++;
                    } elseif ($dryRun) {
                        $this->line("  [dry] email {$palier} → {$credit->client->email}");
                    } else {
                        try {
                            Mail::to($credit->client->email)->send(new RelanceCredit($credit, $message));
                            $this->journaliser($credit, $palier, 'email');
                            $envoyees++;
                        } catch (\Throwable $e) {
                            Log::warning('[Relance crédit] email : ' . $e->getMessage());
                        }
                    }
                }

                // ── Notification interne à l'établissement ────────────────
                $dejaNotifie = CreditRelance::where('credit_id', $credit->id)
                    ->where('palier', $palier)->where('canal', 'inapp')->exists();

                if ($dejaNotifie) {
                    continue;
                }

                $admin = User::withoutGlobalScope(\App\Scopes\InstitutScope::class)
                    ->where('institut_id', $institut->id)
                    ->where('role', 'admin')
                    ->where('actif', true)
                    ->first();

                if (! $admin) {
                    continue;
                }

                $titre = $type === 'rappel'
                    ? 'Échéance de crédit dans ' . abs($jours) . ' jour' . (abs($jours) > 1 ? 's' : '')
                    : 'Crédit en retard de ' . $jours . ' jour' . ($jours > 1 ? 's' : '');

                $corps = ($credit->client?->nom_complet ?? 'Client')
                    . ' — ' . number_format((int) $credit->reste_a_payer, 0, ',', ' ') . ' FCFA restants';

                if ($dryRun) {
                    $this->line("  [dry] notif {$palier} → {$admin->email} : {$titre}");
                    continue;
                }

                try {
                    NotificationService::notifyUser(
                        $admin,
                        'credit_relance',
                        $titre,
                        $corps,
                        route('dashboard.credits.show', $credit)
                    );

                    app(PushNotificationService::class)->sendToUser(
                        $admin,
                        '💳 ' . $titre,
                        $corps,
                        route('dashboard.credits.show', $credit)
                    );

                    $this->journaliser($credit, $palier, 'inapp');
                    $envoyees++;
                } catch (\Throwable $e) {
                    Log::warning('[Relance crédit] notif : ' . $e->getMessage());
                }
            }
        }

        $this->info(($dryRun ? '[Simulation] ' : '') . "{$envoyees} relance(s) traitée(s), {$ignorees} déjà envoyée(s).");

        return self::SUCCESS;
    }

    private function journaliser(Credit $credit, string $palier, string $canal): void
    {
        CreditRelance::firstOrCreate(
            [
                'credit_id' => $credit->id,
                'palier'    => $palier,
                'canal'     => $canal,
            ],
            [
                'institut_id' => $credit->institut_id,
                'envoye_le'   => now(),
            ]
        );
    }
}
