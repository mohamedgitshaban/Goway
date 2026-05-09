<?php

namespace App\Services\Payouts;

use App\Models\WithdrawRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Vodafone Cash payout via Paymob Disbursement API.
 *
 * Required env variables:
 *   PAYMOB_API_KEY                      — Paymob secret API key
 *   PAYMOB_VF_CASH_INTEGRATION_ID       — Paymob integration ID for VF Cash wallet
 */
class VodafoneCashPayoutService implements PayoutServiceInterface
{
    private const BASE_URL = 'https://accept.paymob.com/api';

    private string $apiKey;
    private int    $integrationId;

    public function __construct()
    {
        $this->apiKey        = config('payouts.paymob.api_key', '');
        $this->integrationId = (int) config('payouts.paymob.vf_cash_integration_id', 0);
    }

    public function payout(WithdrawRequest $request): array
    {
        if (empty($this->apiKey) || empty($this->integrationId)) {
            Log::warning('VodafoneCashPayoutService: Paymob not configured.', ['withdraw_request_id' => $request->id]);
            return ['success' => false, 'reference' => null, 'message' => 'Paymob (VF Cash) is not configured.'];
        }

        try {
            $token = $this->authenticate();
            if (! $token) {
                return ['success' => false, 'reference' => null, 'message' => 'Paymob authentication failed.'];
            }

            // Amount in cents (Paymob uses the smallest currency unit)
            $amountCents = (int) round($request->amount * 100);

            $response = Http::withToken($token)
                ->timeout(30)
                ->post(self::BASE_URL . '/acceptance/payments/pay', [
                    'source' => [
                        'identifier'    => $request->account_number, // VF Cash mobile number
                        'subtype'       => 'WALLET',
                    ],
                    'payment_token' => $this->createPaymentKey($token, $amountCents, $request),
                ]);

            if ($response->successful() && in_array($response->json('id'), [null], true) === false) {
                return [
                    'success'   => true,
                    'reference' => (string) $response->json('id'),
                    'message'   => 'Payout dispatched via Paymob Vodafone Cash.',
                ];
            }

            Log::error('VodafoneCashPayoutService: Paymob error.', [
                'withdraw_request_id' => $request->id,
                'status'              => $response->status(),
                'body'                => $response->body(),
            ]);

            return [
                'success'   => false,
                'reference' => null,
                'message'   => $response->json('detail') ?? $response->json('message') ?? 'Paymob VF Cash payout failed.',
            ];
        } catch (\Throwable $e) {
            Log::error('VodafoneCashPayoutService: exception.', [
                'withdraw_request_id' => $request->id,
                'error'               => $e->getMessage(),
            ]);

            return ['success' => false, 'reference' => null, 'message' => $e->getMessage()];
        }
    }

    private function authenticate(): ?string
    {
        $response = Http::timeout(15)->post(self::BASE_URL . '/auth/tokens', [
            'api_key' => $this->apiKey,
        ]);

        return $response->successful() ? $response->json('token') : null;
    }

    private function createPaymentKey(string $token, int $amountCents, WithdrawRequest $request): ?string
    {
        $response = Http::withToken($token)
            ->timeout(15)
            ->post(self::BASE_URL . '/acceptance/payment_keys', [
                'amount_cents'   => $amountCents,
                'expiration'     => 3600,
                'order_id'       => $this->getOrCreateOrderId($token, $amountCents, $request),
                'billing_data'   => [
                    'first_name'    => $request->account_name,
                    'last_name'     => 'Driver',
                    'email'         => 'NA',
                    'phone_number'  => $request->account_number,
                    'apartment'     => 'NA',
                    'floor'         => 'NA',
                    'street'        => 'NA',
                    'building'      => 'NA',
                    'shipping_method' => 'NA',
                    'postal_code'   => 'NA',
                    'city'          => 'NA',
                    'country'       => 'EG',
                    'state'         => 'NA',
                ],
                'currency'       => 'EGP',
                'integration_id' => $this->integrationId,
            ]);

        return $response->successful() ? $response->json('token') : null;
    }

    private function getOrCreateOrderId(string $token, int $amountCents, WithdrawRequest $request): int
    {
        $response = Http::withToken($token)
            ->timeout(15)
            ->post(self::BASE_URL . '/ecommerce/orders', [
                'delivery_needed' => false,
                'amount_cents'    => $amountCents,
                'currency'        => 'EGP',
                'merchant_order_id' => 'WR-' . $request->id,
                'items'           => [],
            ]);

        return $response->json('id', 0);
    }
}
