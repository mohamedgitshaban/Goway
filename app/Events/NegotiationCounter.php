<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NegotiationCounter implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public Trip $trip) {}

    public function broadcastOn()
    {
        return new PrivateChannel("trip.{$this->trip->id}");
    }

    public function broadcastAs()
    {
        return 'negotiation_counter';
    }

    public function broadcastWith()
    {
        return [
            'trip'       => $this->trip,
            'counter_at'    => now()->toISOString(),
        ];
    }
}
