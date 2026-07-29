<?php

namespace App\Jobs;

use App\Models\Commande;
use App\Models\FacebookEventsLog;
use App\Models\Institut;
use App\Services\FacebookCapiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFacebookPurchaseEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Commande $commande,
        private string $eventName = 'Purchase'
    ) {}

    public function handle(): void
    {
        $institut = Institut::find($this->commande->institut_id);

        if (!$institut || !$institut->facebook_pixel_id || !$institut->facebook_access_token) {
            return;
        }

        $capi = new FacebookCapiService(
            $institut->facebook_pixel_id,
            $institut->facebook_access_token,
            $institut->facebook_test_code
        );

        $client = $this->commande->client;

        $userData = [
            'client_ip_address' => request()->ip(),
            'client_user_agent' => request()->userAgent(),
            'em' => $client?->email ? hash('sha256', $client->email) : null,
            'ph' => $client?->telephone ? hash('sha256', preg_replace('/\D/', '', $client->telephone)) : null,
            'fn' => $client?->prenom ? hash('sha256', mb_strtolower($client->prenom)) : null,
            'ln' => $client?->nom ? hash('sha256', mb_strtolower($client->nom)) : null,
            'external_id' => (string) ($client?->id ?? ''),
        ];

        $items = $this->commande->items;
        $customData = [
            'value'        => (float) $this->commande->total,
            'currency'     => 'XOF',
            'content_ids'  => $items->pluck('produit_id')->filter()->toArray(),
            'content_type' => 'product',
            'num_items'    => $items->sum('quantite'),
        ];

        $success = $capi->sendEvent($this->eventName, $userData, $customData);

        // Logger l'événement
        FacebookEventsLog::create([
            'institut_id' => $this->commande->institut_id,
            'event_name'  => $this->eventName,
            'source'      => 'server',
            'payload'     => $customData,
            'success'     => $success,
        ]);
    }
}
