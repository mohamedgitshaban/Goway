<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
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
    public function send_otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Client::where('phone', $request->input('phone'))->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);

        // $code = (string) random_int(100000, 999999);
        Otp::create(['user_id' => $user->id, 'code' => '12345', 'expires_at' => now()->addMinutes(10)]);
        // TODO: integrate SMS provider. For now return OK (do not return code in prod)
        return response()->json(['message' => 'OTP sent to phone']);
    }
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

        $user = Client::where('phone', $request->input('phone'))->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);

        $otp = Otp::where('user_id', $user->id)->orderBy('expires_at', 'desc')->first();
        if (! $otp || $otp->code !== $request->input('otp') || $otp->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }
        $otp->delete(); // delete OTP after successful login
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new ClientResource($user),
        ]);
    }

    // POST /client/register
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
            $user = Client::create([
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

        $user = Client::where('phone', $request->input('phone'))->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);
        $user->status = 'active';
        $otp = Otp::where('user_id', $user->id)->orderBy('expires_at', 'desc')->first();
        if (! $otp || $otp->code !== $request->input('otp') || $otp->expires_at->isPast()) {
            return response()->json(['message' => 'Invalid or expired OTP'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new ClientResource($user),
        ]);
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
    public function profile(Request $request)
{
    $client = $request->user(); // authenticated client

    return response()->json(new ClientResource($client));
}
public function updateProfile(Request $request)
{
    $client = $request->user();

    $validator = Validator::make($request->all(), [
        'first_name' => 'required|string|max:191',
        'last_name'  => 'required|string|max:191',
        'phone'      => 'required|string|max:11|unique:users,phone,' . $client->id,
        'email'      => 'nullable|email|unique:users,email,' . $client->id,
        'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Update basic fields
    $client->first_name = $request->first_name;
    $client->last_name  = $request->last_name;
    $client->phone      = $request->phone;
    $client->email      = $request->email;

    // Handle profile image upload
    if ($request->hasFile('profile_image')) {

        // delete old image if exists
        if ($client->profile_image && file_exists(storage_path('app/public/' . $client->profile_image))) {
            unlink(storage_path('app/public/' . $client->profile_image));
        }

        $path = $request->file('profile_image')->store('clients/profile', 'public');
        $client->profile_image = $path;
    }

    $client->save();

    return response()->json([
        'message' => 'Profile updated successfully',
        'user'    => new ClientResource($client),
    ]);
}

}
