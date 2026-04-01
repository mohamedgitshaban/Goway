<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// Wallets and admin flows moved to dedicated controllers

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

        // Validate password
        if (! $admin ||! Hash::check($request->password, $admin->password)) {
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

    // POST /admin/reset-password
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
    public function profile(Request $request)
    {
        $admin = $request->user(); // authenticated admin

        return response()->json(new AdminResource($admin));
    }
    public function updateProfile(Request $request)
    {
        $admin = $request->user();
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:191',
            'last_name'  => 'required|string|max:191',
            'phone'      => 'required|string|max:11|unique:users,phone,' . $admin->id,
            'email'      => 'nullable|email|unique:users,email,' . $admin->id,
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update basic fields
        $admin->first_name = $request->first_name;
        $admin->last_name  = $request->last_name;
        $admin->phone      = $request->phone;
        $admin->email      = $request->email;

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {

            // delete old image if exists
            if ($admin->profile_image && file_exists(storage_path('app/public/' . $admin->profile_image))) {
                unlink(storage_path('app/public/' . $admin->profile_image));
            }

            $path = $request->file('profile_image')->store('admins/profile', 'public');
            $admin->profile_image = $path;
        }

        $admin->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => new AdminResource($admin),
        ]);
    }
}
