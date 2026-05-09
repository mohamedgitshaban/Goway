<?php

namespace App\Services\Payouts;

use App\Models\WithdrawRequest;

class PayoutDispatcher
{
    public function dispatch(WithdrawRequest $request): array
    {
        $service = match ($request->payment_method) {
            'vodafone_cash' => new VodafoneCashPayoutService(),
            'bank_account'  => new BankTransferPayoutService(),
            default         => throw new \InvalidArgumentException("Unsupported payment method: {$request->payment_method}"),
        };

        return $service->payout($request);
    }
}
