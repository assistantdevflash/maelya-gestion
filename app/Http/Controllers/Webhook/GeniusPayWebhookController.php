<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Services\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GeniusPayWebhookController extends Controller
{
    public function __construct(private PaymentGatewayManager $manager) {}

    public function handle(Request $request)
    {
        $payload   = $request->all();
        $signature = $request->header('X-Webhook-Signature');
        $timestamp = $request->header('X-Webhook-Timestamp');
        $event     = $request->header('X-Webhook-Event');

        $log = WebhookLog::create([
            'provider'   => 'geniuspay',
            'event'      => $event,
            'webhook_id' => $payload['id'] ?? null,
            'payload'    => json_encode($payload),
            'headers'    => json_encode($request->headers->all()),
            'signature'  => $signature,
            'status'     => 'pending',
        ]);

        try {
            if (!$this->verifySignature($payload, $signature, $timestamp)) {
                $log->update([
                    'status'           => 'failed',
                    'signature_valid'  => false,
                    'processing_error' => 'Invalid signature',
                ]);
                Log::warning('[GeniusPay webhook] signature invalide', ['event' => $event]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $log->update(['signature_valid' => true]);

            $this->manager->handleWebhook('geniuspay', $payload);

            $log->update(['status' => 'processed', 'processed_at' => now()]);

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            Log::error('[GeniusPay webhook] erreur traitement', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            $log->update([
                'status'           => 'failed',
                'processing_error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function verifySignature(array $payload, ?string $signature, ?string $timestamp): bool
    {
        $secret = config('services.geniuspay.webhook_secret');

        // Si aucun secret configuré (sandbox sans secret), on accepte
        if (empty($secret)) {
            Log::info('[GeniusPay webhook] aucun secret configuré — signature ignorée');
            return true;
        }

        if (!$signature || !$timestamp) {
            return false;
        }

        // Protection replay attack : max 5 minutes
        if (abs(time() - (int) $timestamp) > 300) {
            Log::warning('[GeniusPay webhook] timestamp trop ancien', ['timestamp' => $timestamp]);
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . json_encode($payload), $secret);
        return hash_equals($expected, $signature);
    }
}
