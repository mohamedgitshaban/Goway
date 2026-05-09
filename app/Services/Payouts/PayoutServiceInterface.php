<?php

namespace App\Services\Payouts;

use App\Models\WithdrawRequest;

interface PayoutServiceInterface
{
    /**
     * Dispatch a payout to the beneficiary.
     *
     * @return array{success: bool, reference: string|null, message: string|null}
     */
    public function payout(WithdrawRequest $request): array;
}
