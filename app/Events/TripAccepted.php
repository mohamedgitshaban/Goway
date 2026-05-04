<?php

namespace App\Events;

use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class TripAccepted implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public Trip $trip) {}

    public function broadcastOn()
    {
        return new Channel("trip.{$this->trip->id}");
    }

    public function broadcastAs()
    {
        return 'trip_accepted';
    }

    public function broadcastWith()
    {
        $driverLocation = $this->resolveDriverLocation();

        return [
            'trip' => new TripResource($this->trip),
            'driver_location' => $driverLocation,
            'accepted_at' => now()->toISOString(),
        ];
    }

    private function resolveDriverLocation(): ?array
    {
        if (! $this->trip->driver_id) {
            return null;
        }

        $state = Redis::hmget("driver:{$this->trip->driver_id}:location", ['lat', 'lng', 'geohash']);

        if (($state[0] ?? null) === null || ($state[1] ?? null) === null || ($state[2] ?? null) === null) {
            return null;
        }

        return [
            'event' => 'driver_entered',
            'driver_id' => (int) $this->trip->driver_id,
            'lat' => (float) $state[0],
            'lng' => (float) $state[1],
            'bearing' => null,
            'speed' => null,
            'geohash' => (string) $state[2],
        ];
    }
}
