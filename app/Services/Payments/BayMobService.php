<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;

class BayMobService implements PaymentGatewayInterface
{
    protected string $endpoint;
    protected string $apiKey;

    public function __construct()
    {
        $this->endpoint = config('services.baymob.endpoint');
        $this->apiKey = config('services.baymob.api_key');
    }

    /**
     * Charge a customer's card via BayMob.
     * Returns an array with success and transaction_id (if available).
     */
    public function charge(array $payload): array
    {
        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->post(rtrim($this->endpoint, '/').'/charge', $payload);

            if ($resp->successful()) {
                $body = $resp->json();
                return [
                    'success' => true,
                    'transaction_id' => $body['transaction_id'] ?? null,
                    'raw' => $body,
                ];
            }

            return [
                'success' => false,
                'transaction_id' => null,
                'raw' => $resp->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'transaction_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refund a BayMob transaction.
     * Returns array with success and raw response.
     */
    public function refund(string $transactionId, ?float $amount = null): array
    {
        try {
            $payload = ['transaction_id' => $transactionId];
            if ($amount !== null) $payload['amount'] = $amount;

            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->post(rtrim($this->endpoint, '/').'/refund', $payload);

            if ($resp->successful()) {
                return ['success' => true, 'raw' => $resp->json()];
            }

            return ['success' => false, 'raw' => $resp->json()];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
