<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// Wallets and driver flows moved to dedicated controllers

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find admin by email
        $admin = Admin::where('email', $request->email)->first();

        if (! $admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        // Validate password
        if (! Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Create token
        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new AdminResource($admin),
        ]);
    }
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Admin::where('email', $request->input('email'))->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);

        $code = (string) random_int(100000, 999999);
        Otp::create(['user_id' => $user->id, 'code' => "12345", 'expires_at' => now()->addMinutes(10)]);

        // TODO: integrate SMS provider. For now return OK (do not return code in prod)
        return response()->json(['message' => 'OTP sent to email']);
    }

    // POST /Driver/reset-password
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string',
            'confirm_password' => 'required|string|same:password',
            'password' => 'required|string|min:8',
            'email' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Admin::where('email', $request->input('email'))->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);

        $otp = Otp::where('user_id', $user->id)->where('code', $request->input('otp'))->orderBy('expires_at', 'desc')->first();
        if (! $otp || $otp->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return response()->json(['message' => 'Password reset successful']);
    }
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }
}
