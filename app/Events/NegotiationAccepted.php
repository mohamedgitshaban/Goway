<?php

namespace App\Events;

use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NegotiationAccepted implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public Trip $trip, public $negotiation = null) {}

    public function broadcastOn()
    {
        $channels = [new Channel("trip.{$this->trip->id}")];
        if ($this->negotiation) {
            $channels[] = new Channel("driver.requests.{$this->negotiation->driver_id}");
        }
        return $channels;
    }

    public function broadcastAs()
    {
        return 'negotiation_accepted';
    }

    public function broadcastWith()
    {
        return [
            'trip'       => new TripResource($this->trip),
            'negotiation' => $this->negotiation,
            'accepted_at'   => now()->toISOString(),
        ];
    }
}
