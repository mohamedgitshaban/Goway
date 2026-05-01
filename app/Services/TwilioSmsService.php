<?php

namespace App\Services;

use RuntimeException;
use Twilio\Rest\Client as TwilioClient;

class TwilioSmsService
{
    private ?TwilioClient $client = null;

    public function sendOtp(string $phone, string $code): void
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.sms_from');
        if ($sid === '' || $token === '' || $from === '') {
            throw new RuntimeException('Twilio SMS credentials are not configured.');
        }
    
        $payload = [
            'from' => $this->normalizePhoneNumber($from),
            'body' => $this->formatOtpMessage($code),
        ];

        try {
            $this->client()->messages->create($this->normalizePhoneNumber($phone), $payload);
        } catch (\Throwable $e) {
            report($e);
            throw new RuntimeException('Failed to send SMS message: ' . $e->getMessage(), 0, $e);
        }
    }

    private function client(): TwilioClient
    {
        return $this->client ??= new TwilioClient(
            (string) config('services.twilio.sid'),
            (string) config('services.twilio.token'),
        );
    }

    private function formatOtpMessage(string $code): string
    {
        $template = (string) config(
            'services.twilio.otp_message',
            'Your Goway verification code is :code. It expires in :minutes minutes.'
        );

        return str_replace(
            [':code', ':minutes'],
            [$code, (string) config('services.twilio.otp_ttl_minutes', 10)],
            $template,
        );
    }

    private function normalizePhoneNumber(string $address): string
    {
        $trimmed = trim($address);
        // If already starts with +, assume E.164
        if (str_starts_with($trimmed, '+')) {
            return $trimmed;
        }

        // Add default country code if missing
        $digits = preg_replace('/\D+/', '', $trimmed);
        $defaultCountryCode = preg_replace('/\D+/', '', (string) config('services.twilio.default_country_code', ''));

        if ($digits !== '' && $defaultCountryCode !== '') {
            if (str_starts_with($digits, $defaultCountryCode)) {
                return '+' . $digits;
            }

            return '+' . $defaultCountryCode . ltrim($digits, '0');
        }

        if ($digits === '') {
            throw new RuntimeException('Invalid phone number for SMS: ' . $address);
        }

        return '+' . $digits;
    }
}
