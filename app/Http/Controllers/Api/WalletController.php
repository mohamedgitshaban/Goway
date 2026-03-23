<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Admins: return all wallets
        if (in_array($user->usertype, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
            return response()->json(Wallet::with('user')->get());
        }

        // Drivers/Clients: return single wallet matching their type
        $expectedType = $user->isDriver() ? 'driver_wallet' : ($user->isClient() ? 'client_wallet' : null);

        if (! $expectedType) {
            return response()->json(['message' => 'No wallet available for this user type'], 404);
        }

        $wallet = Wallet::where('user_id', $user->id)->where('wallet_type', $expectedType)->first();

        return response()->json($wallet);
    }

    public function transaction(Request $request)
    {
        $data = $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:credit,debit',
            'description' => 'nullable|string',
        ]);

        $user = $request->user();

        $wallet = Wallet::findOrFail($data['wallet_id']);

        // If not admin, ensure wallet belongs to user and matches their wallet type
        if (! in_array($user->usertype, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
            if ($wallet->user_id !== $user->id) {
                return response()->json(['message' => 'غير متاح لهذا المستخدم'], 403);
            }

            $expectedType = $user->isDriver() ? 'driver_wallet' : ($user->isClient() ? 'client_wallet' : null);
            if ($expectedType && $wallet->wallet_type !== $expectedType) {
                return response()->json(['message' => 'غير متاح لهذا المستخدم'], 403);
            }
        }

        return DB::transaction(function () use ($wallet, $data, $user) {
            $amount = $data['amount'];

            if ($data['type'] === 'debit') {
                if ($wallet->balance < $amount) {
                    return response()->json(['message' => 'Insufficient balance'], 422);
                }
                $wallet->balance -= $amount;
            } else {
                $wallet->balance += $amount;
            }

            $wallet->save();

            $tx = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'performed_by' => $user->id,
            ]);

            return response()->json(['wallet' => $wallet, 'transaction' => $tx]);
        });
    }

    /**
     * Show a single wallet. Admins can view any wallet; users can view their own.
     */
    public function show(Request $request, $id)
    {
        $wallet = Wallet::with('user')->findOrFail($id);
        $user = $request->user();
        // Admins can view any wallet
        if (in_array($user->usertype, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
            return response()->json($wallet);
        }

        // Non-admins can only view their own wallet and only the wallet matching their role
        if ($wallet->user_id !== $user->id) {
            return response()->json(['message' => 'غير متاح لهذا المستخدم'], 403);
        }

        $expectedType = $user->isDriver() ? 'driver_wallet' : ($user->isClient() ? 'client_wallet' : null);
        if ($expectedType && $wallet->wallet_type !== $expectedType) {
            return response()->json(['message' => 'غير متاح لهذا المستخدم'], 403);
        }

        return response()->json($wallet);
    }

    /**
     * List wallets for a specific user (admin only)
     */
    public function userWallets(Request $request, $userId)
    {
        $user = $request->user();

        if (! in_array($user->usertype, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_SUPER_ADMIN], true)) {
            return response()->json(['message' => 'غير متاح لهذا المستخدم'], 403);
        }

        $wallets = Wallet::where('user_id', $userId)->get();
        return response()->json($wallets);
    }
}
