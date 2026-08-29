<?php

namespace App\Services;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Services\Gateways\BankTransferGateway;
use App\Services\Gateways\GeniusPayGateway;
use App\Services\Gateways\PaymentGatewayInterface;

class PaymentGatewayManager
{
    /** @var array<string, PaymentGatewayInterface> */
    private array $gateways;

    public function __construct(
        GeniusPayGateway $geniuspay,
        BankTransferGateway $bankTransfer,
    ) {
        $this->gateways = [
            'geniuspay'     => $geniuspay,
            'bank_transfer' => $bankTransfer,
        ];
    }

    public function initiate(PaymentTransaction $transaction, PaymentMethod $method): array
    {
        return $this->get($method->code)->initiate($transaction, $method);
    }

    public function verify(PaymentTransaction $transaction): bool
    {
        return $this->get($transaction->payment_method_code)->verify($transaction);
    }

    public function handleWebhook(string $provider, array $payload): void
    {
        $this->get($provider)->handleWebhook($payload);
    }

    public function refund(PaymentTransaction $transaction, ?string $reason = null): array
    {
        return $this->get($transaction->payment_method_code)->refund($transaction, $reason);
    }

    private function get(string $code): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$code])) {
            throw new \InvalidArgumentException("Gateway '{$code}' non enregistré.");
        }
        return $this->gateways[$code];
    }
}
