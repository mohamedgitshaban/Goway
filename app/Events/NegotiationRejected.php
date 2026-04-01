<?php

namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NegotiationRejected implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public Trip $trip) {}

    public function broadcastOn()
    {
        return new PrivateChannel("trip.{$this->trip->id}");
    }

    public function broadcastAs()
    {
        return 'negotiation_rejected';
    }

    public function broadcastWith()
    {
        return [
            'trip'       => $this->trip,
            'rejected_at'   => now()->toISOString(),
        ];
    }
}
