<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        public int $driverId,
        public float $lat,
        public float $lng,
        public string $geohash,
        public string $eventType, // entered / left / moved
        public ?float $bearing = null,
        public ?float $speed = null,
        public ?int $tripId = null,
    ) {}

    public function broadcastOn(): Channel
    {
        if ($this->tripId !== null) {
            return new Channel("trip.{$this->tripId}.driver-location");
        }

        return new Channel("nearby.drivers.{$this->geohash}");
    }

    public function broadcastAs(): string
    {
        return 'driver_location_update';
    }

    public function broadcastWith()
    {
        return [
            'event'     => $this->eventType,
            'driver_id' => $this->driverId,
            'lat'       => $this->lat,
            'lng'       => $this->lng,
            'bearing'   => $this->bearing,
            'speed'     => $this->speed,
            'geohash'   => $this->geohash,
            'trip_id'   => $this->tripId,
        ];
    }
}
