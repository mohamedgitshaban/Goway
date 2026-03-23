<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
// Wallets and driver flows moved to dedicated controllers

class AuthController extends Controller
{
    // POST /login
    public function login(Request $request)
    {
        $headerUsertype = $request->header('usertype');

        $validator = Validator::make($request->all(), [
            'phone' => 'sometimes|required_without:email|string',
            'email' => 'sometimes|required_without:phone|email',
            'password' => 'sometimes|required_with:email|string|min:8',
            'otp' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        // Admin login: email + password
        if ($headerUsertype === User::ROLE_ADMIN || $headerUsertype === User::ROLE_SUPER_ADMIN) {
            $user = User::where('email', $request->input('email'))->where('usertype', $headerUsertype)->first();

            if (! $user || ! Hash::check($request->input('password'), $user->password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json(['token' => $token]);
        }

        // Drivers should use /driver/login endpoint (handled in DriverAuthController)
        if ($headerUsertype === User::ROLE_DRIVER) {
            return response()->json(['message' => 'Driver login is available at /driver/login'], 400);
        }

        return response()->json(['message' => 'Invalid usertype header'], 400);
    }

    // POST /forgot-password
    public function forgotPassword(Request $request)
    {
        $headerUsertype = $request->header('usertype');

        if (! $headerUsertype) {
            return response()->json(['message' => 'Header `usertype` required'], 400);
        }

        if ($headerUsertype === User::ROLE_ADMIN || $headerUsertype === User::ROLE_SUPER_ADMIN) {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::where('email', $request->input('email'))->whereIn('usertype', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first();
            if (! $user) return response()->json(['message' => 'User not found'], 404);

            $code = (string) random_int(100000, 999999);
            Otp::create(['user_id' => $user->id, 'code' => $code, 'expires_at' => now()->addMinutes(10)]);

            // send email (simple)
            Mail::raw("Your reset code: {$code}", function ($m) use ($user) {
                $m->to($user->email)->subject('Password reset code');
            });

            return response()->json(['message' => 'OTP sent to email']);
        }

        // Drivers should use driver-specific forgot-password endpoint
        if ($headerUsertype === User::ROLE_DRIVER) {
            return response()->json(['message' => 'Driver forgot-password is available at /driver/forgot-password'], 400);
        }

        return response()->json(['message' => 'Invalid usertype header'], 400);
    }

    // POST /reset-password
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string',
            'password' => 'required|string|min:8',
            'identifier' => 'required|string', // email or phone
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $headerUsertype = $request->header('usertype');
        if (! $headerUsertype) return response()->json(['message' => 'Header `usertype` required'], 400);

        // find user by email or phone depending on type
        if ($headerUsertype === User::ROLE_ADMIN || $headerUsertype === User::ROLE_SUPER_ADMIN) {
            $user = User::where('email', $request->input('identifier'))->whereIn('usertype', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first();
        } else {
            $user = User::where('phone', $request->input('identifier'))->where('usertype', $headerUsertype)->first();
        }

        if (! $user) return response()->json(['message' => 'User not found'], 404);

        $otp = Otp::where('user_id', $user->id)->where('code', $request->input('otp'))->orderBy('expires_at', 'desc')->first();
        if (! $otp || $otp->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        $user->password_hash = Hash::make($request->input('password'));
        $user->save();

        return response()->json(['message' => 'Password reset successful']);
    }

    // POST /logout
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }

    // POST /register
    public function register(Request $request)
    {
        $headerUsertype = $request->header('usertype');
        if (! in_array($headerUsertype, [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN], true)) {
            return response()->json(['message' => 'Admin registration is only available via this endpoint'], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => Hash::make($data['password']),
            'usertype' => $headerUsertype,
            'status' => 'active',
        ]);

        return response()->json($user, 201);
    }
}
