<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class ClientAuthController extends Controller
{
    // POST /client/login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('phone', $request->input('phone'))->where('usertype', User::ROLE_CLIENT)->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);

        $otp = Otp::where('user_id', $user->id)->orderBy('expires_at', 'desc')->first();
        if (! $otp || $otp->code !== $request->input('otp') || $otp->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;
        return response()->json(['token' => $token]);
    }

    // POST /client/register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'phone' => 'required|string|unique:users,phone',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password_hash' => Hash::make($data['password']),
                'usertype' => User::ROLE_CLIENT,
                'status' => 'active',
            ]);

            Wallet::create(['user_id' => $user->id, 'wallet_type' => 'client_wallet', 'balance' => 0]);

            return response()->json($user, 201);
        });
    }

    // POST /client/forgot-password
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('phone', $request->input('phone'))->where('usertype', User::ROLE_CLIENT)->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);

        $code = (string) random_int(100000, 999999);
        Otp::create(['user_id' => $user->id, 'code' => $code, 'expires_at' => now()->addMinutes(10)]);

        // TODO: integrate SMS provider. For now return OK (do not return code in prod)
        return response()->json(['message' => 'OTP sent to phone']);
    }

    // POST /client/reset-password
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string',
            'password' => 'required|string|min:8',
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('phone', $request->input('phone'))->where('usertype', User::ROLE_CLIENT)->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);

        $otp = Otp::where('user_id', $user->id)->where('code', $request->input('otp'))->orderBy('expires_at', 'desc')->first();
        if (! $otp || $otp->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        $user->password_hash = Hash::make($request->input('password'));
        $user->save();

        return response()->json(['message' => 'Password reset successful']);
    }

    // POST /client/logout (protected)
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }
}
