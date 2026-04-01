<?php 
namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
class NewTripRequest implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        public \App\Models\Trip $trip,
        public int $driverId
    ) {}

    public function broadcastOn()
    {
        return new PrivateChannel("driver.requests.{$this->driverId}");
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
            ],
            'destination' => [
                'lat' => $this->trip->destination_lat,
                'lng' => $this->trip->destination_lng,
            ],
            'final_price' => $this->trip->final_price,
        ];
    }
}

