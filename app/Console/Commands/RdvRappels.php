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
            }

            // ── Email client ───────────────────────────────────────────────
            if ($rdv->client_email) {
                try {
                    $rdv->loadMissing('prestations');
                    Mail::to($rdv->client_email)->send(new RdvRappelClient($rdv));
                } catch (\Throwable $e) {
                    Log::warning('[RDV Rappel Mail Client] ' . $e->getMessage());
                }
            }

            $rdv->update(['rappel_envoye' => true]);
            $envoyes++;
        }

        $this->info("Rappels envoyés pour {$envoyes} rendez-vous.");
        return self::SUCCESS;
    }
}
