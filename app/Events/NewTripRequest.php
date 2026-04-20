<?php

namespace App\Events;

use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NewTripRequest implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public Trip $trip,
        public int $driverId
    ) {}

    public function broadcastOn()
    {
        return new Channel("driver.requests.{$this->driverId}");
    }

    public function broadcastAs()
    {
        return 'new_trip_request';
    }

    public function broadcastWith()
    {
        return new TripResource($this->trip);
    }
}
