<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverPayoutAccountResource;
use App\Models\DriverPayoutAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DriverPayoutAccountController extends Controller
{
    /**
     * List all saved payout accounts for the authenticated driver.
     */
    public function index(Request $request)
    {
        $accounts = DriverPayoutAccount::where('user_id', $request->user()->id)
            ->get();

        return response()->json([
            'status' => true,
            'data'   => DriverPayoutAccountResource::collection($accounts),
        ]);
    }

    /**
     * Get a single saved payout account by payment method.
     * GET /driver/payout-accounts/{payment_method}
     */
    public function show(Request $request, string $paymentMethod)
    {
        $account = DriverPayoutAccount::where('user_id', $request->user()->id)
            ->where('payment_method', $paymentMethod)
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data'   => new DriverPayoutAccountResource($account),
        ]);
    }

    /**
     * Create or update the saved payout account for the given payment method.
     * POST /driver/payout-accounts
     *
     * Sending the same payment_method a second time updates the existing record.
     */
    public function storeOrUpdate(Request $request)
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['bank_account', 'vodafone_cash'])],
            'account_name'   => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'bank_name'      => ['required_if:payment_method,bank_account', 'nullable', 'string', 'max:255'],
            'iban'           => ['nullable', 'string', 'max:34'],
        ]);

        $account = DriverPayoutAccount::updateOrCreate(
            [
                'user_id'        => $request->user()->id,
                'payment_method' => $data['payment_method'],
            ],
            [
                'account_name'   => $data['account_name'],
                'account_number' => $data['account_number'],
                'bank_name'      => $data['bank_name'] ?? null,
                'iban'           => $data['iban'] ?? null,
            ]
        );

        $isNew = $account->wasRecentlyCreated;

        return response()->json([
            'status'  => true,
            'message' => $isNew ? 'Payout account saved.' : 'Payout account updated.',
            'data'    => new DriverPayoutAccountResource($account),
        ], $isNew ? 201 : 200);
    }

    /**
     * Delete a saved payout account.
     * DELETE /driver/payout-accounts/{payment_method}
     */
    public function destroy(Request $request, string $paymentMethod)
    {
        $deleted = DriverPayoutAccount::where('user_id', $request->user()->id)
            ->where('payment_method', $paymentMethod)
            ->delete();

        if (! $deleted) {
            return response()->json(['status' => false, 'message' => 'Payout account not found.'], 404);
        }

        return response()->json(['status' => true, 'message' => 'Payout account deleted.']);
    }
}
