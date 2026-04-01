<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TripStarted implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public Trip $trip) {}

    public function broadcastOn()
    {
        return new PrivateChannel("trip.{$this->trip->id}");
    }

    public function broadcastAs()
    {
        return 'trip_started';
    }

    public function broadcastWith()
    {
        return [
            'trip' => $this->trip,
            'started_at' => now()->toISOString(),
        ];
    }
}
