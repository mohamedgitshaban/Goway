<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Trip;
use Illuminate\Support\Facades\Redis;

class DriverDestinationPreferenceService
{
    public function matchesTrip(Driver $driver, Trip $trip, float $maxDetourKm = 20.0): bool
    {
        if (! $driver->destinationPreference) {
            return true;
        }

        $driverLocation = Redis::hmget("driver:{$driver->id}:location", ['lat', 'lng']);

        if (empty($driverLocation[0]) || empty($driverLocation[1])) {
            return false;
        }

        $originLat = (float) $driverLocation[0];
        $originLng = (float) $driverLocation[1];
        $preferredLat = (float) $driver->destinationPreference->lat;
        $preferredLng = (float) $driver->destinationPreference->lng;

        $directDistanceKm = $this->routeDistanceKm([
            'origin_lat' => $originLat,
            'origin_lng' => $originLng,
            'destination_lat' => $preferredLat,
            'destination_lng' => $preferredLng,
            'waypoints' => [],
        ]);

        $distanceViaTripKm = $this->routeDistanceKm([
            'origin_lat' => $originLat,
            'origin_lng' => $originLng,
            'destination_lat' => $preferredLat,
            'destination_lng' => $preferredLng,
            'waypoints' => [
                ['lat' => (float) $trip->origin_lat, 'lng' => (float) $trip->origin_lng],
                ['lat' => (float) $trip->destination_lat, 'lng' => (float) $trip->destination_lng],
            ],
        ]);

        if ($directDistanceKm === null || $distanceViaTripKm === null) {
            return false;
        }

        return round($distanceViaTripKm - $directDistanceKm, 2) <= $maxDetourKm;
    }

    private function routeDistanceKm(array $routeData): ?float
    {
        $computedRoute = app(GoogleRouteService::class)->compute($routeData);

        if (($computedRoute['source'] ?? null) === 'google_routes' && isset($computedRoute['distance_km'])) {
            return (float) $computedRoute['distance_km'];
        }

        return null;
    }
}