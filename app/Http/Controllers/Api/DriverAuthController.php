<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DriverAuthController extends Controller
{
    public function send_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Driver::where('phone', $request->input('phone'))->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);

        // $code = (string) random_int(100000, 999999);
        Otp::create(['user_id' => $user->id, 'code' => '12345', 'expires_at' => now()->addMinutes(10)]);
        // TODO: integrate SMS provider. For now return OK (do not return code in prod)
        return response()->json(['message' => 'OTP sent to phone']);
    }
    // POST /Driver/login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Driver::where('phone', $request->input('phone'))->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);

        $otp = Otp::where('user_id', $user->id)->orderBy('expires_at', 'desc')->first();
        if (! $otp || $otp->code !== $request->input('otp') || $otp->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new DriverResource($user),
        ]);
    }

    // POST /Driver/register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'phone' => 'required|string|unique:users,phone|max:11',
            'email' => 'nullable|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        return DB::transaction(function () use ($data) {
            $user = Driver::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'status' => 'pending_otp',
            ]);
            Otp::create(['user_id' => $user->id, 'code' => '12345', 'expires_at' => now()->addMinutes(10)]);
            return response()->json($user, 201);
        });
    }
    public function activatePhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Driver::where('phone', $request->input('phone'))->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);
        $user->status = 'pending_document';
        $otp = Otp::where('user_id', $user->id)->orderBy('expires_at', 'desc')->first();
        if (! $otp || $otp->code !== $request->input('otp') || $otp->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new DriverResource($user),
        ]);
    }
    // POST /Driver/logout (protected)
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }
}
