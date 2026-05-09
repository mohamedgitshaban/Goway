<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WithdrawRequestResource;
use App\Jobs\ProcessWithdrawalPayout;
use App\Models\WithdrawRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminWithdrawRequestController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    /**
     * List all withdrawal requests with filtering and pagination.
     */
    public function index(Request $request)
    {
        $limit   = $request->input('limit', 15);
        $status  = $request->input('status');
        $method  = $request->input('payment_method');
        $search  = $request->input('search');
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowed = ['id', 'amount', 'status', 'created_at', 'processed_at'];
        if (! in_array($sortBy, $allowed)) {
            $sortBy = 'created_at';
        }

        $query = WithdrawRequest::with(['user', 'admin'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($method, fn($q) => $q->where('payment_method', $method))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('id', $search)
                          ->orWhere('account_number', 'LIKE', "%{$search}%")
                          ->orWhereHas('user', fn($u) => $u->where('name', 'LIKE', "%{$search}%"));
                });
            })
            ->orderBy($sortBy, $sortDir);

        $results = $query->paginate($limit);

        return WithdrawRequestResource::collection($results);
    }

    /**
     * Show a single withdrawal request.
     */
    public function show(int $id)
    {
        $withdraw = WithdrawRequest::with(['user', 'admin'])->findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => new WithdrawRequestResource($withdraw),
        ]);
    }

    /**
     * Approve a pending withdrawal request.
     *
     * Flow:
     *  1. Verify request is still pending.
     *  2. Deduct the amount from the driver's wallet.
     *  3. Set status → processing.
     *  4. Dispatch async job to send the actual payout.
     */
    public function approve(Request $request, int $id)
    {
        $withdraw = WithdrawRequest::with('user')->findOrFail($id);

        if ($withdraw->status !== 'pending') {
            return response()->json([
                'status'  => false,
                'message' => "Cannot approve a request with status '{$withdraw->status}'.",
            ], 422);
        }

        $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $result = DB::transaction(function () use ($withdraw, $request) {
            // Attempt to debit wallet
            $debited = $this->walletService->decrement(
                $withdraw->user,
                $withdraw->amount,
                'withdrawal',
                ['withdraw_request_id' => $withdraw->id]
            );

            if (! $debited) {
                return false;
            }

            $withdraw->update([
                'status'     => 'processing',
                'admin_id'   => $request->user()->id,
                'admin_note' => $request->input('admin_note'),
            ]);

            return true;
        });

        if (! $result) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient wallet balance. Cannot process withdrawal.',
            ], 422);
        }

        // Dispatch payout job to queue
        ProcessWithdrawalPayout::dispatch($withdraw);

        return response()->json([
            'status'  => true,
            'message' => 'Withdrawal approved. Payout is being processed.',
            'data'    => new WithdrawRequestResource($withdraw->fresh(['user', 'admin'])),
        ]);
    }

    /**
     * Reject a pending withdrawal request.
     */
    public function reject(Request $request, int $id)
    {
        $withdraw = WithdrawRequest::with('user')->findOrFail($id);

        if ($withdraw->status !== 'pending') {
            return response()->json([
                'status'  => false,
                'message' => "Cannot reject a request with status '{$withdraw->status}'.",
            ], 422);
        }

        $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $withdraw->update([
            'status'       => 'rejected',
            'admin_id'     => $request->user()->id,
            'admin_note'   => $request->input('admin_note'),
            'processed_at' => now(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Withdrawal request rejected.',
            'data'    => new WithdrawRequestResource($withdraw->fresh(['user', 'admin'])),
        ]);
    }
}
