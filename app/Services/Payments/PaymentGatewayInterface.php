<?php

namespace App\Services\Payments;

interface PaymentGatewayInterface
{
    /**
     * Charge a customer.
     * Returns array with at least ['success' => bool] and optional details.
     */
    public function charge(array $payload): array;

    /**
     * Refund a transaction. Amount optional for partial refunds.
     */
    public function refund(string $transactionId, ?float $amount = null): array;
}
