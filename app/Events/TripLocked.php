<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TripLocked implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public int $tripId, public ?int $driverId = null) {}

    public function broadcastOn()
    {
        return new Channel("trip.locked");
    }

    public function broadcastAs()
    {
        return 'trip_locked';
    }

    public function broadcastWith()
    {
        return [
            'trip_id' => $this->tripId,
            'driver_id' => $this->driverId,
        ];
    }
}
