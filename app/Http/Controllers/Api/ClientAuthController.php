<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Models\Otp;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OtpService;
use App\Traits\HandlesMultipart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\WelcomeMail;

class ClientAuthController extends Controller
{
    use HandlesMultipart;

    public function __construct(private readonly OtpService $otpService) {}

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

        try {
            $this->otpService->issue($user->id, $user->phone);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'Unable to send OTP at the moment'], 502);
        }

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
        $user = Client::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'status' => 'pending_otp',
        ]);

        try {
            return DB::transaction(function () use ($data, $user) {
                $this->otpService->issue($user->id, $user->phone);

                return response()->json($user, 201);
            });
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Unable to complete registration at the moment'], 502);
        }
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

        // Send welcome email
        if ($user->email) {
            Mail::to($user->email)->queue(new WelcomeMail($user));
        }

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
        $this->handleMultipart($request);
        $client = $request->user();

        $profileImageRules = $request->hasFile('profile_image')
            ? 'sometimes|file|image|mimes:jpg,jpeg,png|max:2048'
            : 'sometimes|nullable|string|max:2048';

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:191',
            'last_name'  => 'required|string|max:191',
            'phone'      => 'required|string|max:11|unique:users,phone,' . $client->id,
            'email'      => 'nullable|email|unique:users,email,' . $client->id,
            'profile_image' => $profileImageRules,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $client->first_name = $request->first_name;
        $client->last_name  = $request->last_name;
        $client->phone      = $request->phone;
        $client->email      = $request->email;
        if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            // delete old image if exists
            if ($client->personal_image) {
                $this->deleteStoredFile($client->personal_image);
            }

            $client->personal_image = config('filesystems.disks.public.url') . '/' . $request->file('profile_image')->store('clients/profile', 'public');
        }


        $client->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => new ClientResource($client),
        ]);
    }

    private function deleteStoredFile($urlOrPath): void
    {
        $relativePath = $this->normalizeStoredFilePath($urlOrPath);

        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        // Soft delete the user. The User model uses the SoftDeletes trait,
        // so this will only set the deleted_at timestamp.
        // We will not delete the personal_image here to allow for account restoration.
        $user->tokens()->delete();
        $user->softDeletes();

        return response()->json([
            'status' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    private function normalizeStoredFilePath($urlOrPath): ?string
    {
        if (! $urlOrPath) {
            return null;
        }

        $storageSegment = '/storage/';
        if (strpos($urlOrPath, $storageSegment) !== false) {
            return substr($urlOrPath, strpos($urlOrPath, $storageSegment) + strlen($storageSegment));
        }

        return ltrim($urlOrPath, '/');
    }
}
