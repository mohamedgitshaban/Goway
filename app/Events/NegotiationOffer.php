<?php

namespace App\Events;

use App\Http\Resources\DriverResource;
use App\Http\Resources\TripNegotiationResource;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
class NegotiationOffer implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public Trip $trip, public $negotiation = null) {}

    public function broadcastOn()
    {
        return new Channel("trip.{$this->trip->id}");
    }

    public function broadcastAs()
    {
        return 'negotiation_offer';
    }

    public function broadcastWith()
    {
        return [
            'trip' =>  new TripResource($this->trip),
            'negotiation' => new TripNegotiationResource($this->negotiation), 
            'driver' => new DriverResource($this->negotiation->driver),
            'created_at' => now()->toISOString(),
        ];
    }
}
