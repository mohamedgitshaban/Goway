<?php

namespace App\Services\Payments;

interface PaymentGatewayFactoryInterface
{
    /**
     * Return a payment gateway implementation for the given method (eg. 'visa').
     * Returns null when no gateway is available for the method (eg. cash).
     */
    public function get(string $method): ?PaymentGatewayInterface;
}
