<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Models\Otp;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use App\Mail\WelcomeMail;
use App\Traits\HandlesMultipart;

class DriverAuthController extends Controller
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

        $user = Driver::where('phone', $request->input('phone'))->first();
        if (! $user) return response()->json(['message' => 'User not found'], 404);

        try {
            $this->otpService->issue($user->id, $user->phone);
        } catch (\Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'Unable to send OTP at the moment'], 502);
        }

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
        if (! $user->driverDocument) {
            $user->status = 'pending_document';
            $user->save();
        }
        $token = $user->createToken('api-token')->plainTextToken;
        $user->is_online = true;
        $user->save();
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
            'personal_image' => 'nullable|string|max:191',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if ($request->hasFile('personal_image')) {
            $path = config('filesystems.disks.public.url') . '/' . $request->file('personal_image')->store('drivers/personal', 'public');
            $data['personal_image'] = $path;
        }
        $user = Driver::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'status' => 'pending_otp',
            'personal_image' =>  $data['personal_image'] ?? null,
        ]);
        $this->otpService->issue($user->id, $user->phone);
        return response()->json(['message' => 'Registration successful, OTP sent to phone']);
        // try {
        //     $this->otpService->issue($user->id, $user->phone);

        // } catch (\Throwable $exception) {
        //     report($exception);

        //     return response()->json(['message' => 'Unable to complete registration at the moment'], 502);
        // }
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
        $user->is_online = true;
        $user->save();

        // Send welcome email
        if ($user->email) {
            Mail::to($user->email)->queue(new WelcomeMail($user));
        }

        return response()->json([
            'token' => $token,
            'user'  => new DriverResource($user),
        ]);
    }
    // POST /Driver/logout (protected)
    public function logout(Request $request)
    {
        $driver = $request->user();

        if ($driver && $driver->currentAccessToken()) {
            $driver->is_online = false;
            $driver->save();

            // Remove from Redis
            Redis::del("driver:{$driver->id}:location");

            $keys = Redis::keys("geohash:drivers:*");
            foreach ($keys as $key) {
                Redis::srem($key, $driver->id);
            }

            $driver->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }

    public function profile(Request $request)
    {
        $driver = $request->user(); // authenticated driver

        return response()->json(new DriverResource($driver));
    }
    public function updateProfile(Request $request)
    {
        $this->handleMultipart($request);
        $driver = $request->user();

        $personalImageRules = $request->hasFile('profile_image')
            ? 'sometimes|file|image|max:5120'
            : 'sometimes|nullable|string|max:2048';
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:191',
            'last_name'  => 'required|string|max:191',
            'phone'      => 'required|string|max:11|unique:users,phone,' . $driver->id,
            'email'      => 'nullable|email|unique:users,email,' . $driver->id,
            'profile_image' => $personalImageRules,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update basic fields
        $driver->first_name = $request->first_name;
        $driver->last_name  = $request->last_name;
        $driver->phone      = $request->phone;
        $driver->email      = $request->email;

        // Handle profile image upload
 if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
            // delete old image if exists
            if ($driver->profile_image) {
                $this->deleteStoredFile($driver->profile_image);
            }

            $driver->personal_image = config('filesystems.disks.public.url') . '/' . $request->file('profile_image')->store('drivers/profile', 'public');
        }


        $driver->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => new DriverResource($driver),
        ]);
    }
    public function goOnline()
    {
        $driver = auth()->user();
        $driver->update(['is_online' => true]);
        return response()->json(['message' => 'Driver is now online']);
    }

    public function goOffline()
    {
        $driver = auth()->user();
        $driver->update(['is_online' => false]);

        // Remove from Redis
        Redis::del("driver:{$driver->id}:location");

        // Remove from all geohash sets
        $keys = Redis::keys("geohash:drivers:*");
        foreach ($keys as $key) {
            Redis::srem($key, $driver->id);
        }

        return response()->json(['message' => 'Driver is now offline']);
    }


    public function toggleonlinestatus()
    {
        $driver = auth()->user();
        $driver->update(['is_online' => !$driver->is_online]);
        if (! $driver->is_online) {
            // Remove from Redis
            Redis::del("driver:{$driver->id}:location");

            // Remove from all geohash sets
            $keys = Redis::keys("geohash:drivers:*");
            foreach ($keys as $key) {
                Redis::srem($key, $driver->id);
            }
        }
        return response()->json(['is_online' => $driver->is_online]);
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
}
