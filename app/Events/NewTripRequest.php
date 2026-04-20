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
        return [
            'trip' => new TripResource($this->trip),
            'trip_id' => $this->trip->id,
            'driver_id' => $this->driverId,
        ];
    }
}
