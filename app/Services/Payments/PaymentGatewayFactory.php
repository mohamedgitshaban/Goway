<?php

namespace App\Services\Payments;

use App\Services\Payments\BayMobService;

class PaymentGatewayFactory implements PaymentGatewayFactoryInterface
{
    /**
     * Map payment method keys to concrete gateway classes. Extend as needed.
     * Keys should be lowercase.
     * @var array<string,string>
     */
    protected array $map = [
        'visa' => BayMobService::class,
    ];

    public function get(string $method): ?PaymentGatewayInterface
    {
        $key = strtolower((string) $method);
        if (! isset($this->map[$key])) {
            return null;
        }

        $class = $this->map[$key];
        return app()->make($class);
    }
}
