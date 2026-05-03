<?php

namespace App\Services;

use App\Models\Otp;
use Throwable;

class OtpService
{
    // public function __construct(private readonly TwilioSmsService $twilioSmsService) {}

    public function issue(int $userId, string $phone): Otp
    {
        Otp::where('user_id', $userId)->delete();

        $otp = Otp::create([
            'user_id' => $userId,
            'code' => (string) random_int(10000, 99999),
            'expires_at' => now()->addMinutes((int) config('services.twilio.otp_ttl_minutes', 10)),
        ]);
        // try {
        //     $this->twilioSmsService->sendOtp($phone, $otp->code);
        // } catch (Throwable $exception) {
        //     // $otp->delete();
        //     $otp = Otp::updateOrCreate(
        //         ['user_id' => $userId],
        //         [
        //             'code' => '12345',
        //             'expires_at' => now()->addHours((int) config('services.twilio.otp_ttl_minutes', 10)),
        //         ]
        //     );
        //     throw $exception;
        // }

        return $otp;
    }
}
