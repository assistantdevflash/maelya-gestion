<?php

namespace App\Console\Commands;

use App\Mail\RdvRappelClient;
use App\Mail\RdvRappelEtablissement;
use App\Models\RendezVous;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RdvRappels extends Command
{
    protected $signature   = 'rdv:rappels';
    protected $description = 'Envoie les rappels J-1 pour les rendez-vous du lendemain (push + email établissement et email client).';

    public function handle(): int
    {
        $demain = now()->addDay()->toDateString();

        $rdvs = RendezVous::with(['employe', 'prestations'])
            ->whereDate('debut_le', $demain)
            ->whereIn('statut', ['en_attente', 'confirme'])
            ->where('rappel_envoye', false)
            ->get();

        if ($rdvs->isEmpty()) {
            $this->info('Aucun rendez-vous demain nécessitant un rappel.');
            return self::SUCCESS;
        }

        $envoyes = 0;

        foreach ($rdvs as $rdv) {
            $proprietaire = \App\Models\User::where('institut_id', $rdv->institut_id)
                ->where('role', 'admin')
                ->where('actif', true)
                ->first();

            // ── Email + push établissement ─────────────────────────────────
            if ($proprietaire) {
                try {
                    Mail::to($proprietaire->email)->send(new RdvRappelEtablissement($rdv));
                } catch (\Throwable $e) {
                    Log::warning('[RDV Rappel Mail Etab] ' . $e->getMessage());
                }
                try {
                    app(PushNotificationService::class)->sendToUser(
                        $proprietaire,
                        '📅 RDV demain',
                        ($rdv->client_nom) . ' — ' . $rdv->debut_le->format('H\hi') . ' · ' . $rdv->label_prestations,
                        '/dashboard/rdv/' . $rdv->id
                    );
                } catch (\Throwable $e) {
                    Log::warning('[RDV Rappel Push] ' . $e->getMessage());
                }
                \App\Services\NotificationService::notifyUser(
                    $proprietaire,
                    'rdv_rappel',
                    '📅 RDV demain — ' . $rdv->client_nom,
                    $rdv->debut_le->format('H\hi') . ($rdv->label_prestations ? ' · ' . $rdv->label_prestations : ''),
                    '/dashboard/rdv/' . $rdv->id
                );
            }

            // ── Rappel client : e-mail si disponible, sinon WhatsApp ──────
            if ($rdv->client_email) {
                try {
                    $rdv->loadMissing('prestations');
                    Mail::to($rdv->client_email)->send(new RdvRappelClient($rdv));
                } catch (\Throwable $e) {
                    Log::warning('[RDV Rappel Mail Client] ' . $e->getMessage());
                }
            } elseif ($rdv->client_telephone) {
                // Pas d'e-mail : on prépare un message WhatsApp et on le signale
                // à l'établissement. La majorité des clients n'ont pas d'e-mail,
                // ils ne recevaient donc jusqu'ici AUCUN rappel.
                $lien = $this->lienWhatsApp($rdv);

                if ($lien && $proprietaire) {
                    try {
                        app(PushNotificationService::class)->sendToUser(
                            $proprietaire,
                            '📲 Rappel WhatsApp à envoyer',
                            $rdv->client_nom . ' — ' . $rdv->debut_le->format('d/m à H\hi'),
                            $lien
                        );
                    } catch (\Throwable $e) {
                        Log::warning('[RDV Rappel WhatsApp Push] ' . $e->getMessage());
                    }

                    \App\Services\NotificationService::notifyUser(
                        $proprietaire,
                        'rdv_rappel_whatsapp',
                        '📲 Rappel WhatsApp à envoyer',
                        $rdv->client_nom . ' — ' . $rdv->debut_le->format('H\hi'),
                        '/dashboard/rdv/' . $rdv->id
                    );
                }
            }

            $rdv->update(['rappel_envoye' => true]);
            $envoyes++;
        }

        $this->info("Rappels envoyés pour {$envoyes} rendez-vous.");
        return self::SUCCESS;
    }

    /**
     * Lien wa.me prêt à envoyer au client (rappels sans e-mail).
     */
    private function lienWhatsApp(\App\Models\RendezVous $rdv): ?string
    {
        $numero = preg_replace('/\D+/', '', (string) $rdv->client_telephone);
        if (! $numero) {
            return null;
        }

        if (str_starts_with($numero, '0') && strlen($numero) === 10) {
            $numero = '225' . $numero;
        } elseif (strlen($numero) === 8) {
            $numero = '225' . $numero;
        }

        $institut = \App\Models\Institut::find($rdv->institut_id);

        $message = "Bonjour {$rdv->client_nom},\n\n"
            . "Petit rappel : votre rendez-vous est prévu le "
            . $rdv->debut_le->format('d/m/Y') . " à " . $rdv->debut_le->format('H\hi') . ".\n"
            . ($rdv->label_prestations && $rdv->label_prestations !== '—' ? "Prestation : {$rdv->label_prestations}\n" : '')
            . "\n" . ($institut->nom ?? config('app.name'))
            . ($institut?->telephone ? "\n" . $institut->telephone : '');

        return 'https://wa.me/' . $numero . '?text=' . rawurlencode($message);
    }
}
