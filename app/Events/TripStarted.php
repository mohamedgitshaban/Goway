<?php

namespace App\Events;

use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TripStarted implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public Trip $trip) {}

    public function broadcastOn()
    {
        return new Channel("trip.{$this->trip->id}");
    }

    public function broadcastAs()
    {
        return 'trip_started';
    }

    public function broadcastWith()
    {
        return [
            'trip' => new TripResource($this->trip),
            'started_at' => now()->toISOString(),
        ];
    }
}
