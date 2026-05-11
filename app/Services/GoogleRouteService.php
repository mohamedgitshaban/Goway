<?php

namespace App\Services;

use App\Support\GeoHash;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleRouteService
{
    private const ROUTES_ENDPOINT = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    public function compute(array $data): array
    {
        $apiKey = (string) config('services.google_maps.api_key');

        if ($apiKey === '') {
            return $this->fallbackSnapshot($data, 'missing_api_key');
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-Goog-Api-Key' => $apiKey,
                    'X-Goog-FieldMask' => implode(',', [
                        'routes.distanceMeters',
                        'routes.duration',
                        'routes.staticDuration',
                        'routes.travelAdvisory.tollInfo.estimatedPrice',
                    ]),
                ])
                ->post(self::ROUTES_ENDPOINT, $this->payload($data));

            if (! $response->successful()) {
                Log::warning('Google Routes API request failed.', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return $this->fallbackSnapshot($data, 'request_failed');
            }

            $route = Arr::first($response->json('routes', []));

            if (! is_array($route)) {
                return $this->fallbackSnapshot($data, 'missing_route');
            }

            $distanceMeters = (int) ($route['distanceMeters'] ?? 0);
            $durationSeconds = $this->parseDurationSeconds($route['duration'] ?? null);
            $staticDurationSeconds = $this->parseDurationSeconds($route['staticDuration'] ?? null);
            $tollBreakdown = $this->parseTollInfo($route['travelAdvisory']['tollInfo']['estimatedPrice'] ?? []);
            $trafficDelaySeconds = max(0, $durationSeconds - $staticDurationSeconds);
            Log::info('Google Routes API computed route.', [
                'source' => 'google_routes',
                'distance_meters' => $distanceMeters,
                'distance_km' => round($distanceMeters / 1000, 2),
                'duration_seconds' => $durationSeconds,
                'duration_minutes' => round($durationSeconds / 60, 2),
                'static_duration_seconds' => $staticDurationSeconds,
                'traffic_delay_seconds' => $trafficDelaySeconds,
                'traffic_delay_minutes' => round($trafficDelaySeconds / 60, 2),
                'has_traffic' => $trafficDelaySeconds > 0,
                'has_tolls' => $tollBreakdown['amount'] > 0,
                'toll_amount' => $tollBreakdown['amount'],
                'toll_currency' => $tollBreakdown['currency'],
            ]);
            return [
                'source' => 'google_routes',
                'distance_meters' => $distanceMeters,
                'distance_km' => round($distanceMeters / 1000, 2),
                'duration_seconds' => $durationSeconds,
                'duration_minutes' => round($durationSeconds / 60, 2),
                'static_duration_seconds' => $staticDurationSeconds,
                'traffic_delay_seconds' => $trafficDelaySeconds,
                'traffic_delay_minutes' => round($trafficDelaySeconds / 60, 2),
                'has_traffic' => $trafficDelaySeconds > 0,
                'has_tolls' => $tollBreakdown['amount'] > 0,
                'toll_amount' => $tollBreakdown['amount'],
                'toll_currency' => $tollBreakdown['currency'],
            ];
        } catch (\Throwable $exception) {
            Log::warning('Google Routes API exception.', [
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackSnapshot($data, 'exception');
        }
    }

    private function payload(array $data): array
    {
        return [
            'origin' => $this->waypoint((float) $data['origin_lat'], (float) $data['origin_lng']),
            'destination' => $this->waypoint((float) $data['destination_lat'], (float) $data['destination_lng']),
            'intermediates' => array_map(
                fn (array $waypoint): array => $this->waypoint((float) $waypoint['lat'], (float) $waypoint['lng']),
                $data['waypoints'] ?? []
            ),
            'travelMode' => 'DRIVE',
            'routingPreference' => 'TRAFFIC_AWARE',
            'computeAlternativeRoutes' => false,
            'routeModifiers' => [
                'avoidTolls' => false,
            ],
            'extraComputations' => ['TOLLS'],
            'units' => 'METRIC',
            'departureTime' => Carbon::now()->addMinutes(1)->toIso8601String(),
        ];
    }

    private function waypoint(float $lat, float $lng): array
    {
        return [
            'location' => [
                'latLng' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ],
            ],
        ];
    }

    private function parseDurationSeconds(null|string $duration): int
    {
        if (! is_string($duration) || $duration === '') {
            return 0;
        }

        return (int) round((float) rtrim($duration, 's'));
    }

    private function parseTollInfo(array $estimatedPrices): array
    {
        $amount = 0.0;
        $currency = null;

        foreach ($estimatedPrices as $price) {
            if (! is_array($price)) {
                continue;
            }

            $units = (float) ($price['units'] ?? 0);
            $nanos = (float) ($price['nanos'] ?? 0) / 1000000000;
            $amount += $units + $nanos;
            $currency ??= $price['currencyCode'] ?? null;
        }

        return [
            'amount' => round($amount, 2),
            'currency' => $currency,
        ];
    }

    private function fallbackSnapshot(array $data, string $reason): array
    {
        $points = [];
        $points[] = ['lat' => (float) $data['origin_lat'], 'lng' => (float) $data['origin_lng']];

        foreach ($data['waypoints'] ?? [] as $waypoint) {
            $points[] = ['lat' => (float) $waypoint['lat'], 'lng' => (float) $waypoint['lng']];
        }

        $points[] = ['lat' => (float) $data['destination_lat'], 'lng' => (float) $data['destination_lng']];

        $distanceKm = 0.0;
        for ($index = 0; $index < count($points) - 1; $index++) {
            $distanceKm += GeoHash::distanceKm(
                $points[$index]['lat'],
                $points[$index]['lng'],
                $points[$index + 1]['lat'],
                $points[$index + 1]['lng']
            );
        }

        return [
            'source' => 'fallback_' . $reason,
            'distance_meters' => (int) round($distanceKm * 1000),
            'distance_km' => round($distanceKm, 2),
            'duration_seconds' => 0,
            'duration_minutes' => 0.0,
            'static_duration_seconds' => 0,
            'traffic_delay_seconds' => 0,
            'traffic_delay_minutes' => 0.0,
            'has_traffic' => false,
            'has_tolls' => false,
            'toll_amount' => 0.0,
            'toll_currency' => null,
        ];
    }
}