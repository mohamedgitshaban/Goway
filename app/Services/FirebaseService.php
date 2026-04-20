<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    /**
     * Send a push notification via FCM v1 API.
     */
    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        try {
            $accessToken = $this->getAccessToken();

            $payload = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $this->stringifyData($data),
                ],
            ];

            $response = Http::withToken($accessToken)
                ->post(config('firebase.api_url'), $payload);

            if ($response->failed()) {
                Log::warning('FCM send failed', [
                    'token' => substr($fcmToken, 0, 20) . '...',
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('FCM send exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send push notification to multiple tokens.
     */
    public function sendToTokens(array $fcmTokens, string $title, string $body, array $data = []): void
    {
        foreach ($fcmTokens as $token) {
            if ($token) {
                $this->sendToToken($token, $title, $body, $data);
            }
        }
    }

    /**
     * Get OAuth2 access token from the service account credentials.
     */
    private function getAccessToken(): string
    {
        $credentialsPath = config('firebase.credentials');

        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException('Firebase service account credentials file not found: ' . $credentialsPath);
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);

        $now = time();
        $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claimSet = base64url_encode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signatureInput = $header . '.' . $claimSet;
        openssl_sign($signatureInput, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        $jwt = $signatureInput . '.' . base64url_encode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to obtain FCM access token: ' . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * FCM data payload values must all be strings.
     */
    private function stringifyData(array $data): array
    {
        return array_map(fn ($v) => (string) $v, $data);
    }
}

/**
 * Base64 URL-safe encode (no padding).
 */
if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
