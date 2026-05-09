<?php

namespace App\Jobs;

use App\Models\WithdrawRequest;
use App\Services\Payouts\PayoutDispatcher;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWithdrawalPayout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public WithdrawRequest $withdraw) {}

    public function handle(PayoutDispatcher $dispatcher, WalletService $walletService): void
    {
        // Guard: only process requests in 'processing' status
        if ($this->withdraw->status !== 'processing') {
            return;
        }

        $result = $dispatcher->dispatch($this->withdraw);

        if ($result['success']) {
            $this->withdraw->update([
                'status'           => 'paid',
                'payout_reference' => $result['reference'],
                'processed_at'     => now(),
                'admin_note'       => $this->withdraw->admin_note,
            ]);

            Log::info('WithdrawalPayout: paid.', [
                'withdraw_request_id' => $this->withdraw->id,
                'reference'           => $result['reference'],
            ]);
        } else {
            // Refund the wallet so the driver is not out of funds
            $walletService->increment(
                $this->withdraw->user,
                $this->withdraw->amount,
                'withdrawal_refund',
                ['withdraw_request_id' => $this->withdraw->id, 'reason' => $result['message']]
            );

            $this->withdraw->update([
                'status'       => 'failed',
                'processed_at' => now(),
                'admin_note'   => $result['message'],
            ]);

            Log::error('WithdrawalPayout: failed — wallet refunded.', [
                'withdraw_request_id' => $this->withdraw->id,
                'reason'              => $result['message'],
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('WithdrawalPayout job permanently failed.', [
            'withdraw_request_id' => $this->withdraw->id,
            'error'               => $exception->getMessage(),
        ]);

        // Refund on permanent failure
        $walletService = app(WalletService::class);
        $walletService->increment(
            $this->withdraw->user,
            $this->withdraw->amount,
            'withdrawal_refund',
            ['withdraw_request_id' => $this->withdraw->id, 'reason' => 'Job permanently failed']
        );

        $this->withdraw->update([
            'status'       => 'failed',
            'processed_at' => now(),
            'admin_note'   => 'Payout job permanently failed: ' . $exception->getMessage(),
        ]);
    }
}
