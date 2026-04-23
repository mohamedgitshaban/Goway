<?php

namespace App\Services;

use App\Models\Otp;
use Throwable;

class OtpService
{
    public function __construct(private readonly TwilioWhatsappService $twilioWhatsappService)
    {
    }

    public function issue(int $userId, string $phone): Otp
    {
        Otp::where('user_id', $userId)->delete();

        $otp = Otp::create([
            'user_id' => $userId,
            'code' => (string) random_int(10000, 99999),
            'expires_at' => now()->addMinutes((int) config('services.twilio.otp_ttl_minutes', 10)),
        ]);

        try {
            $this->twilioWhatsappService->sendOtp($phone, $otp->code);
        } catch (Throwable $exception) {
            $otp->delete();

            throw $exception;
        }

        return $otp;
    }
}