<?php

namespace App\Console\Commands;

use App\Mail\EcheanceRappelClient;
use App\Mail\EcheanceRappelEtablissement;
use App\Models\Echeance;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RappelsEcheances extends Command
{
    protected $signature   = 'credits:rappels-echeances';
    protected $description = 'Envoie les rappels J-1 pour les echeances de credit du lendemain (email client + email/push etablissement).';

    public function handle(): int
    {
        $demain = now()->addDay()->toDateString();

        $echeances = Echeance::with(['credit.client', 'credit.vente.items', 'credit.institut'])
            ->whereDate('date_prevue', $demain)
            ->where('statut', 'en_attente')
            ->get();

        if ($echeances->isEmpty()) {
            $this->info('Aucune echeance demain necessitant un rappel.');
            return self::SUCCESS;
        }

        $envoyes = 0;

        foreach ($echeances as $echeance) {
            $credit   = $echeance->credit;
            $client   = $credit->client;
            $institut = $credit->institut;

            // ── Proprietaire de l'etablissement ────────────────────────────
            $proprietaire = \App\Models\User::where('institut_id', $credit->institut_id)
                ->where('role', 'admin')
                ->where('actif', true)
                ->first();

            // ── Email etablissement ────────────────────────────────────────
            if ($proprietaire?->email) {
                try {
                    Mail::to($proprietaire->email)->send(new EcheanceRappelEtablissement($echeance));
                } catch (\Throwable $e) {
                    Log::warning('[Echeance Rappel Mail Etab] ' . $e->getMessage());
                }
            }

            // ── Push + notification etablissement ──────────────────────────
            if ($proprietaire) {
                try {
                    app(PushNotificationService::class)->sendToUser(
                        $proprietaire,
                        'Rappel echeance credit',
                        ($client?->nom_complet ?? 'Client') . ' — ' . number_format($echeance->montant - $echeance->montant_paye, 0, ',', ' ') . ' FCFA · ' . \Carbon\Carbon::parse($echeance->date_prevue)->format('d/m/Y'),
                        '/dashboard/credits/' . $credit->id
                    );
                } catch (\Throwable $e) {
                    Log::warning('[Echeance Rappel Push] ' . $e->getMessage());
                }

                NotificationService::notifyUser(
                    $proprietaire,
                    'echeance_rappel',
                    'Rappel echeance credit — ' . ($client?->nom_complet ?? 'Client'),
                    $echeance->numero . '/' . $credit->nb_echeances . ' · ' . number_format($echeance->montant - $echeance->montant_paye, 0, ',', ' ') . ' FCFA · ' . \Carbon\Carbon::parse($echeance->date_prevue)->format('d/m/Y'),
                    '/dashboard/credits/' . $credit->id
                );
            }

            // ── Email client ───────────────────────────────────────────────
            if ($client?->email) {
                try {
                    Mail::to($client->email)->send(new EcheanceRappelClient($echeance));
                } catch (\Throwable $e) {
                    Log::warning('[Echeance Rappel Mail Client] ' . $e->getMessage());
                }
            }

            $envoyes++;
        }

        $this->info("{$envoyes} rappel(s) d'echeance envoye(s).");
        return self::SUCCESS;
    }
}
