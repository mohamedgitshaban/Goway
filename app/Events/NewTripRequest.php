<?php

namespace App\Events;

use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Services\GoogleRouteService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class NewTripRequest implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public Trip $trip,
        public int $driverId,
        public string $type = 'new_trip_request'
    ) {}

    public function broadcastOn()
    {
        return new Channel("driver.requests.{$this->driverId}");
    }

    public function broadcastAs()
    {
        return $this->type;
    }

    public function broadcastWith()
    {
        $routeStats = $this->resolveRouteToOrigin();

        return [
            'trip' => new TripResource($this->trip),
            'trip_id' => $this->trip->id,
            'driver_id' => $this->driverId,
            'distance_to_origin_km' => $routeStats['distance_km'],
            'duration_to_origin_minutes' => $routeStats['duration_minutes'],
        ];
    }

    /**
     * Resolve route data from driver current location to trip origin.
     */
    private function resolveRouteToOrigin(): array
    {
        $driverLocationKey = "driver:{$this->driverId}:location";
        $driverLocation = Redis::hmget($driverLocationKey, ['lat', 'lng']);

        if (empty($driverLocation[0]) || empty($driverLocation[1])) {
            return [
                'distance_km' => null,
                'duration_minutes' => null,
            ];
        }

        $routeData = app(GoogleRouteService::class)->compute([
            'origin_lat' => (float) $driverLocation[0],
            'origin_lng' => (float) $driverLocation[1],
            'destination_lat' => (float) $this->trip->origin_lat,
            'destination_lng' => (float) $this->trip->origin_lng,
            'waypoints' => [],
        ]);

        return [
            'distance_km' => isset($routeData['distance_km']) ? round((float) $routeData['distance_km'], 2) : null,
            'duration_minutes' => isset($routeData['duration_minutes']) ? round((float) $routeData['duration_minutes'], 2) : null,
        ];
    }
}
