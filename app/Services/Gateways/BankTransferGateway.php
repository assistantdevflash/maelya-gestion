<?php

namespace App\Services\Gateways;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function initiate(PaymentTransaction $transaction, PaymentMethod $method): array
    {
        return [
            'success'      => true,
            'checkout_url' => route('payment.bank-transfer', ['ref' => $transaction->reference]),
        ];
    }

    public function verify(PaymentTransaction $transaction): bool
    {
        return $transaction->status === 'completed';
    }

    public function handleWebhook(array $payload): void
    {
        // Le transfert bancaire ne reçoit pas de webhooks
    }
}
