<?php

namespace App\Events;

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
            'trip_id' => $this->trip->id,
            'client' => [
                'id' => $this->trip->client_id,
                'name' => $this->trip->client->name,
            ],
            'origin' => [
                'lat' => $this->trip->origin_lat,
                'lng' => $this->trip->origin_lng,
                'address' => $this->trip->origin_address,
            ],
            'destination' => [
                'lat' => $this->trip->destination_lat,
                'lng' => $this->trip->destination_lng,
                'address' => $this->trip->destination_address,
            ],
            'final_price' => $this->trip->final_price,
            'trip_type' => $this->trip->tripType->name_en,
        ];
    }
}
