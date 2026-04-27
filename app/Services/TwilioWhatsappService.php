<?php

namespace App\Services;

use RuntimeException;
use Twilio\Rest\Client as TwilioClient;

class TwilioWhatsappService
{
    private ?TwilioClient $client = null;

    public function sendOtp(string $phone, string $code): void
    {
        $sid = (string) config('services.twilio.sid');
        $token = (string) config('services.twilio.token');
        $from = (string) config('services.twilio.whatsapp_from');
    
        if ($sid === '' || $token === '' || $from === '') {
            throw new RuntimeException('Twilio WhatsApp credentials are not configured.');
        }

        // Determine whether we're sending via WhatsApp or SMS
        $fromAddress = $this->formatWhatsappAddress($from);
        $toAddress = $this->formatWhatsappAddress($phone);

        $isWhatsapp = str_starts_with($fromAddress, 'whatsapp:') || str_starts_with($toAddress, 'whatsapp:');

        if ($isWhatsapp) {
            $payload = [
                'from' => $fromAddress,
                'body' => $this->formatOtpMessage($code),
            ];

            $contentSid = (string) config('services.twilio.whatsapp_content_sid');
            if ($contentSid !== '') {
                $payload['contentSid'] = $contentSid;
                $payload['contentVariables'] = json_encode($this->buildContentVariables($code), JSON_THROW_ON_ERROR);
            }

            try {
                $this->client()->messages->create($toAddress, $payload);
            } catch (\Throwable $e) {
                report($e);
                throw new RuntimeException('Failed to send WhatsApp message: ' . $e->getMessage(), 0, $e);
            }
        } else {
            // Send as SMS. Normalize numbers to E.164 where possible.
            $fromSms = $this->normalizePhoneNumber($from);
            $toSms = $this->normalizePhoneNumber($phone);

            $payload = [
                'from' => $fromSms,
                'body' => $this->formatOtpMessage($code),
            ];

            try {
                $this->client()->messages->create($toSms, $payload);
            } catch (\Throwable $e) {
                report($e);
                throw new RuntimeException('Failed to send SMS message: ' . $e->getMessage(), 0, $e);
            }
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

    private function buildContentVariables(string $code): array
    {
        return [
            '1' => $code,
            '2' => (string) config('services.twilio.otp_ttl_minutes', 10),
        ];
    }

    private function formatWhatsappAddress(string $address): string
    {
        $trimmed = trim($address);
        if ($trimmed === '') {
            throw new RuntimeException('Twilio WhatsApp address cannot be empty.');
        }

        if (str_starts_with($trimmed, 'whatsapp:')) {
            return $trimmed;
        }

        if (str_starts_with($trimmed, '+')) {
            return 'whatsapp:' . $trimmed;
        }

        $digits = preg_replace('/\D+/', '', $trimmed);
        $defaultCountryCode = preg_replace('/\D+/', '', (string) config('services.twilio.default_country_code', ''));

        if ($digits !== '' && $defaultCountryCode !== '') {
            if (str_starts_with($digits, $defaultCountryCode)) {
                return 'whatsapp:+' . $digits;
            }

            return 'whatsapp:+' . $defaultCountryCode . ltrim($digits, '0');
        }

        return 'whatsapp:' . $trimmed;
    }

    private function normalizePhoneNumber(string $address): string
    {
        $trimmed = trim($address);
        // If already starts with +, assume E.164
        if (str_starts_with($trimmed, '+')) {
            return $trimmed;
        }

        // Strip non-digits and prefix +
        $digits = preg_replace('/\D+/', '', $trimmed);
        if ($digits === '') {
            throw new RuntimeException('Invalid phone number for SMS: ' . $address);
        }

        return '+' . $digits;
    }
}