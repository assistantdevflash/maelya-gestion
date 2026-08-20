<?php

namespace Tests\Unit\Services;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Gateways\GeniusPayGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeniusPayGatewayTest extends TestCase
{
    use RefreshDatabase;

    private GeniusPayGateway $gateway;
    private PaymentMethod $method;
    private PaymentTransaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = app(GeniusPayGateway::class);

        $user = User::factory()->create(['email' => 'test@test.ci', 'name' => 'Test User']);

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
            'reference'           => 'MGP-TEST001',
            'user_id'             => $user->id,
            'type'                => 'abonnement',
            'amount'              => 9900,
            'net_amount'          => 9900,
            'currency'            => 'XOF',
            'payment_method_id'   => $this->method->id,
            'payment_method_code' => 'geniuspay',
            'status'              => 'pending',
            'metadata'            => ['plan_nom' => 'Premium'],
        ]);
    }

    /** @test */
    public function initiate_creates_gateway_reference_on_success()
    {
        Http::fake([
            '*/payments' => Http::response([
                'success' => true,
                'data' => [
                    'reference'    => 'MTX-ABCDE12345',
                    'checkout_url' => 'https://geniuspay.ci/checkout/MTX-ABCDE12345',
                    'payment_url'  => 'https://geniuspay.ci/checkout/MTX-ABCDE12345',
                    'status'       => 'pending',
                    'amount'       => 9900,
                ],
            ], 201),
        ]);

        $result = $this->gateway->initiate($this->transaction, $this->method);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('geniuspay.ci', $result['checkout_url']);

        $this->assertDatabaseHas('payment_transactions', [
            'reference'         => 'MGP-TEST001',
            'gateway_reference' => 'MTX-ABCDE12345',
            'status'            => 'pending',
        ]);
    }

    /** @test */
    public function initiate_throws_exception_on_api_failure()
    {
        Http::fake([
            '*/payments' => Http::response([
                'success' => false,
                'error'   => ['code' => 'INVALID_API_KEY', 'message' => 'Invalid key'],
            ], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/GeniusPay/i');

        $this->gateway->initiate($this->transaction, $this->method);
    }

    /** @test */
    public function verify_returns_true_and_activates_completed_payment()
    {
        $this->transaction->update(['gateway_reference' => 'MTX-ABCDE12345']);

        Http::fake([
            '*/payments/MTX-ABCDE12345' => Http::response([
                'success' => true,
                'data' => [
                    'reference'  => 'MTX-ABCDE12345',
                    'status'     => 'completed',
                    'amount'     => 9900,
                    'fees'       => 199,
                    'net_amount' => 9701,
                    'metadata'   => ['maelya_ref' => 'MGP-TEST001'],
                ],
            ], 200),
        ]);

        // Pour que completePayment marche, on n'a pas d'abonnement_id → il loggera juste
        $result = $this->gateway->verify($this->transaction);

        $this->assertTrue($result);
        $this->assertDatabaseHas('payment_transactions', [
            'reference'      => 'MGP-TEST001',
            'status'         => 'completed',
            'gateway_status' => 'completed',
            'fees'           => 199,
            'net_amount'     => 9701,
        ]);
    }

    /** @test */
    public function verify_returns_false_for_pending_payment()
    {
        $this->transaction->update(['gateway_reference' => 'MTX-PENDING123']);

        Http::fake([
            '*/payments/MTX-PENDING123' => Http::response([
                'success' => true,
                'data'    => ['reference' => 'MTX-PENDING123', 'status' => 'pending'],
            ], 200),
        ]);

        $result = $this->gateway->verify($this->transaction);

        $this->assertFalse($result);
        $this->assertDatabaseHas('payment_transactions', [
            'reference' => 'MGP-TEST001',
            'status'    => 'pending',
        ]);
    }

    /** @test */
    public function handle_webhook_payment_success_completes_transaction()
    {
        $payload = [
            'event' => 'payment.success',
            'data'  => [
                'reference'  => 'MTX-WEBOOK01',
                'status'     => 'completed',
                'amount'     => 9900,
                'fees'       => 199,
                'net_amount' => 9701,
                'metadata'   => ['maelya_ref' => 'MGP-TEST001'],
            ],
        ];

        $this->gateway->handleWebhook($payload);

        $this->assertDatabaseHas('payment_transactions', [
            'reference' => 'MGP-TEST001',
            'status'    => 'completed',
        ]);
    }

    /** @test */
    public function handle_webhook_payment_failed_marks_transaction_failed()
    {
        $payload = [
            'event' => 'payment.failed',
            'data'  => [
                'reference' => 'MTX-FAILED01',
                'status'    => 'failed',
                'metadata'  => ['maelya_ref' => 'MGP-TEST001'],
            ],
        ];

        $this->gateway->handleWebhook($payload);

        $this->assertDatabaseHas('payment_transactions', [
            'reference' => 'MGP-TEST001',
            'status'    => 'failed',
        ]);
    }

    /** @test */
    public function handle_webhook_without_maelya_ref_does_not_crash()
    {
        $this->gateway->handleWebhook([
            'event' => 'payment.success',
            'data'  => ['reference' => 'MTX-UNKNOWN', 'metadata' => []],
        ]);

        // Aucune exception levée
        $this->assertTrue(true);
    }

    /** @test */
    public function idempotence_does_not_process_completed_transaction_twice()
    {
        $this->transaction->update(['status' => 'completed', 'paid_at' => now()]);

        $payload = [
            'event' => 'payment.success',
            'data'  => [
                'reference'  => 'MTX-DUP01',
                'status'     => 'completed',
                'amount'     => 9900,
                'fees'       => 199,
                'net_amount' => 9701,
                'metadata'   => ['maelya_ref' => 'MGP-TEST001'],
            ],
        ];

        $paidAt = $this->transaction->paid_at;
        $this->gateway->handleWebhook($payload);

        // paid_at ne doit pas changer
        $this->assertEquals($paidAt->toDateTimeString(), $this->transaction->fresh()->paid_at->toDateTimeString());
    }
}
