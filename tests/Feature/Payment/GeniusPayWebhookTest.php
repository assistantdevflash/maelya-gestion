<?php

namespace Tests\Feature\Payment;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeniusPayWebhookTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethod $method;
    private PaymentTransaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        $this->method = PaymentMethod::create([
            'code'          => 'geniuspay',
            'name'          => 'GeniusPay',
            'type'          => 'gateway',
            'is_active'     => true,
            'auto_validate' => true,
            'position'      => 1,
        ]);

        $this->transaction = PaymentTransaction::create([
            'id'                  => (string) \Illuminate\Support\Str::uuid(),
            'reference'           => 'MGP-FEAT0001',
            'user_id'             => $user->id,
            'type'                => 'abonnement',
            'amount'              => 9900,
            'net_amount'          => 9900,
            'currency'            => 'XOF',
            'payment_method_id'   => $this->method->id,
            'payment_method_code' => 'geniuspay',
            'gateway_reference'   => 'MTX-WEBHOOK01',
            'status'              => 'pending',
            'metadata'            => ['plan_nom' => 'Premium'],
        ]);
    }

    /** @test */
    public function webhook_success_without_secret_is_accepted()
    {
        config(['services.geniuspay.webhook_secret' => '']);

        $payload = [
            'event' => 'payment.success',
            'id'    => 'whk_001',
            'data'  => [
                'reference'  => 'MTX-WEBHOOK01',
                'status'     => 'completed',
                'amount'     => 9900,
                'fees'       => 199,
                'net_amount' => 9701,
                'metadata'   => ['maelya_ref' => 'MGP-FEAT0001'],
            ],
        ];

        $response = $this->postJson('/webhooks/geniuspay', $payload);
        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('payment_transactions', [
            'reference' => 'MGP-FEAT0001',
            'status'    => 'completed',
        ]);
        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'geniuspay',
            'status'   => 'processed',
        ]);
    }

    /** @test */
    public function webhook_with_invalid_signature_returns_401()
    {
        config(['services.geniuspay.webhook_secret' => 'secret123']);

        $payload = [
            'event' => 'payment.success',
            'data'  => ['metadata' => ['maelya_ref' => 'MGP-FEAT0001']],
        ];

        $response = $this->postJson('/webhooks/geniuspay', $payload, [
            'X-Webhook-Signature' => 'invalide',
            'X-Webhook-Timestamp' => (string) time(),
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseHas('webhook_logs', [
            'provider'        => 'geniuspay',
            'status'          => 'failed',
            'signature_valid' => false,
        ]);
    }

    /** @test */
    public function webhook_with_valid_signature_is_processed()
    {
        $secret    = 'whsec_test_secret';
        $timestamp = (string) time();

        config(['services.geniuspay.webhook_secret' => $secret]);

        $payload   = [
            'event' => 'payment.failed',
            'id'    => 'whk_002',
            'data'  => [
                'reference' => 'MTX-WEBHOOK01',
                'status'    => 'failed',
                'metadata'  => ['maelya_ref' => 'MGP-FEAT0001'],
            ],
        ];
        $signature = hash_hmac('sha256', $timestamp . '.' . json_encode($payload), $secret);

        $response = $this->postJson('/webhooks/geniuspay', $payload, [
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => $timestamp,
            'X-Webhook-Event'     => 'payment.failed',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('payment_transactions', [
            'reference' => 'MGP-FEAT0001',
            'status'    => 'failed',
        ]);
    }

    /** @test */
    public function webhook_with_expired_timestamp_returns_401()
    {
        config(['services.geniuspay.webhook_secret' => 'secret123']);

        $oldTimestamp = (string) (time() - 400); // > 5 minutes
        $payload      = ['event' => 'payment.success', 'data' => []];
        $signature    = hash_hmac('sha256', $oldTimestamp . '.' . json_encode($payload), 'secret123');

        $response = $this->postJson('/webhooks/geniuspay', $payload, [
            'X-Webhook-Signature' => $signature,
            'X-Webhook-Timestamp' => $oldTimestamp,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function webhook_logs_all_incoming_requests()
    {
        config(['services.geniuspay.webhook_secret' => '']);

        $this->postJson('/webhooks/geniuspay', [
            'event' => 'payment.success',
            'data'  => ['metadata' => ['maelya_ref' => 'REF-INEXISTANT']],
        ]);

        $this->assertDatabaseCount('webhook_logs', 1);
    }
}
