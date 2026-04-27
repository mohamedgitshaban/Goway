<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;

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

    public function decrement(User $user, float $amount): bool
    {
        $wallet = $this->getOrCreateWallet($user);
        if ($wallet->balance < $amount) return false;
        $wallet->decrement('balance', $amount);
        return true;
    }

    public function increment(User $user, float $amount): void
    {
        $wallet = $this->getOrCreateWallet($user);
        $wallet->increment('balance', $amount);
    }
}
