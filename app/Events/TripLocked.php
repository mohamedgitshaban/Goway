<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TripLocked implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public int $tripId, 
        public ?int $driverId = null,
        public array $driverIdsToNotify = []
    ) {}

    public function broadcastOn()
    {
        $channels = [new Channel("trip.locked")]; // Global fallback

        // Also broadcast directly to nearby drivers' request channels
        foreach ($this->driverIdsToNotify as $dId) {
            $channels[] = new Channel("driver.requests.{$dId}");
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'trip_locked';
    }

    public function broadcastWith()
    {
        return [
            'message' => 'Trip has been locked for assignment. No further drivers will receive requests for this trip.',
            'status' => 'locked',
            'trip_id' => $this->tripId,
            'driver_id' => $this->driverId,
        ];
    }
}
