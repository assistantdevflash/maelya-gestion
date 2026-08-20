<?php

namespace App\Services\Gateways;

use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;

interface PaymentGatewayInterface
{
    /**
     * Initialise un paiement et retourne les URLs de redirection.
     *
     * @return array{success: bool, checkout_url: string, reference?: string}
     */
    public function initiate(PaymentTransaction $transaction, PaymentMethod $method): array;

    /**
     * Vérifie le statut d'un paiement auprès du gateway.
     */
    public function verify(PaymentTransaction $transaction): bool;

    /**
     * Traite un payload webhook entrant.
     */
    public function handleWebhook(array $payload): void;
}
