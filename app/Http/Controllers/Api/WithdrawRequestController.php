<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WithdrawRequestResource;
use App\Models\DriverPayoutAccount;
use App\Models\WithdrawRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WithdrawRequestController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    /**
     * List the authenticated driver's withdrawal requests.
     */
    public function index(Request $request)
    {
        $requests = WithdrawRequest::where('user_id', $request->user()->id)
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('limit', 10));

        return WithdrawRequestResource::collection($requests);
    }

    /**
     * Submit a new withdrawal request.
     *
     * Two modes:
     *  A) Pass `driver_payout_account_id` — payment details are loaded from the
     *     saved encrypted payout account (driver only needs to send amount).
     *  B) Pass payment details manually — they are stored encrypted as a snapshot.
     *
     * Driver may only have one pending request at a time.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Prevent duplicate pending requests
        if (WithdrawRequest::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            return response()->json([
                'status'  => false,
                'message' => 'You already have a pending withdrawal request.',
            ], 422);
        }

        $balance = $this->walletService->getBalance($user);

        // ── Mode A: use a saved payout account ───────────────────────────────
        if ($request->filled('driver_payout_account_id')) {
            $request->validate([
                'driver_payout_account_id' => ['required', 'integer'],
                'amount'                   => ['required', 'numeric', 'min:10', 'max:' . $balance],
            ]);

            $account = DriverPayoutAccount::where('user_id', $user->id)
                ->findOrFail($request->input('driver_payout_account_id'));

            $withdraw = WithdrawRequest::create([
                'user_id'                  => $user->id,
                'amount'                   => $request->input('amount'),
                'status'                   => 'pending',
                'payment_method'           => $account->payment_method,
                'account_name'             => $account->account_name,
                'account_number'           => $account->account_number,
                'bank_name'                => $account->bank_name,
                'iban'                     => $account->iban,
                'driver_payout_account_id' => $account->id,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Withdrawal request submitted successfully.',
                'data'    => new WithdrawRequestResource($withdraw),
            ], 201);
        }

        // ── Mode B: manual entry ─────────────────────────────────────────────
        $data = $request->validate([
            'amount'         => ['required', 'numeric', 'min:10', 'max:' . $balance],
            'payment_method' => ['required', Rule::in(['bank_account', 'vodafone_cash'])],
            'account_name'   => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'bank_name'      => ['required_if:payment_method,bank_account', 'nullable', 'string', 'max:255'],
            'iban'           => ['nullable', 'string', 'max:34'],
        ]);

        $withdraw = WithdrawRequest::create([
            ...$data,
            'user_id' => $user->id,
            'status'  => 'pending',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Withdrawal request submitted successfully.',
            'data'    => new WithdrawRequestResource($withdraw),
        ], 201);
    }

    /**
     * Show a single withdrawal request (driver's own only).
     */
    public function show(Request $request, int $id)
    {
        $withdraw = WithdrawRequest::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => new WithdrawRequestResource($withdraw),
        ]);
    }
}

