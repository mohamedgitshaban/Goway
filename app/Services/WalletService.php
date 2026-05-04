<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getBalance(User $user): float
    {
        return $user->wallet ? (float) $user->wallet->balance : 0.0;
    }

    public function getOrCreateWallet(User $user): Wallet
    {
        if ($user->wallet) return $user->wallet;
        return $user->wallet()->create(['balance' => 0]);
    }

    public function decrement(User $user, float $amount, ?string $source = null, array $meta = []): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $wallet = $this->getOrCreateWallet($user);
        if ($wallet->balance < $amount) return false;

        return DB::transaction(function () use ($wallet, $user, $amount, $source, $meta) {
            $wallet->refresh();
            $before = (float) $wallet->balance;

            if ($before < $amount) {
                return false;
            }

            $after = round($before - $amount, 2);
            $wallet->update(['balance' => $after]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'user_type' => $user->usertype,
                'type' => 'burn',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'source' => $source,
                'meta' => $meta ?: null,
            ]);

            return true;
        });
    }

    public function increment(User $user, float $amount, ?string $source = null, array $meta = []): void
    {
        if ($amount <= 0) {
            return;
        }

        $wallet = $this->getOrCreateWallet($user);

        DB::transaction(function () use ($wallet, $user, $amount, $source, $meta) {
            $wallet->refresh();
            $before = (float) $wallet->balance;
            $after = round($before + $amount, 2);

            $wallet->update(['balance' => $after]);

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'user_type' => $user->usertype,
                'type' => 'mint',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'source' => $source,
                'meta' => $meta ?: null,
            ]);
        });
    }
}
