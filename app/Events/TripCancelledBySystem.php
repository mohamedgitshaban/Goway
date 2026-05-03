<?php

namespace App\Events;

use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class TripCancelledBySystem implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public Trip $trip) {}

    public function broadcastOn()
    {
        return new Channel("trip.{$this->trip->id}");
    }

    public function broadcastAs()
    {
        return 'trip_cancelled_by_system';
    }

    public function broadcastWith()
    {
        return [
            'trip' => new TripResource($this->trip),
            'cancelled_at' => now()->toISOString(),
            'message' => 'Trip has been automatically cancelled by the system due to unavailability of drivers.'
        ];
    }
}
