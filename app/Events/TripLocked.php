<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TripLocked implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public int $tripId) {}

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
            'trip_id' => $this->tripId
        ];
    }
}
