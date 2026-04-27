<?php

namespace App\Services;

use App\Models\Otp;
use Throwable;
use Twilio\Exceptions\RestException as TwilioRestException;

class OtpService
{
    public function __construct(private readonly TwilioWhatsappService $twilioWhatsappService)
    {
    }

    public function issue(int $userId, string $phone): Otp
    {
        // Reuse existing non-expired OTP when possible to avoid changing code between requests
        $existing = Otp::where('user_id', $userId)->orderBy('expires_at', 'desc')->first();
        if ($existing && ! $existing->expires_at->isPast()) {
            try {
                $this->twilioWhatsappService->sendOtp($phone, $existing->code);
            } catch (Throwable $exception) {
                // If provider rate limits or fails, log and return existing OTP so verification can continue
                report($exception);
                return $existing;
            }

            return $existing;
        }

        // No valid existing OTP — delete old records and create a new one
        Otp::where('user_id', $userId)->delete();

        $otp = Otp::create([
            'user_id' => $userId,
            'code' => (string) random_int(10000, 99999),
            'expires_at' => now()->addMinutes((int) config('services.twilio.otp_ttl_minutes', 10)),
        ]);

        try {
            $this->twilioWhatsappService->sendOtp($phone, $otp->code);
        } catch (Throwable $exception) {
            // If Twilio rate-limits on trial accounts, keep the OTP and allow verification to proceed
            if ($exception instanceof TwilioRestException && ($exception->getCode() === 63038 || str_contains($exception->getMessage(), 'exceeded'))) {
                report($exception);
                return $otp;
            }

            $otp->delete();

            throw $exception;
        }

        return $otp;
    }
}