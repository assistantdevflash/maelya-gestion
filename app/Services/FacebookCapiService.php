<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookCapiService
{
    public function __construct(
        private string $pixelId,
        private string $accessToken,
        private ?string $testCode = null
    ) {}

    /**
     * Envoie un événement à la Conversions API Meta.
     */
    public function sendEvent(string $eventName, array $userData, array $customData, ?string $eventId = null): bool
    {
        if (empty($this->pixelId) || empty($this->accessToken)) {
            return false;
        }

        $eventId ??= (string) \Illuminate\Support\Str::uuid();

        $payload = [
            'data' => [[
                'event_name'  => $eventName,
                'event_time'  => now()->timestamp,
                'event_id'    => $eventId,
                'event_source_url' => request()->fullUrl(),
                'action_source' => 'website',
                'user_data'   => $userData,
                'custom_data' => $customData,
            ]],
        ];

        if ($this->testCode) {
            $payload['test_event_code'] = $this->testCode;
        }

        try {
            $response = Http::timeout(5)
                ->post("https://graph.facebook.com/v21.0/{$this->pixelId}/events?access_token={$this->accessToken}", $payload);

            if ($response->failed()) {
                Log::warning('[FacebookCAPI] Échec envoi', [
                    'event'  => $eventName,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('[FacebookCAPI] Exception: ' . $e->getMessage());
            return false;
        }
    }
}
