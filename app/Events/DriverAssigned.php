<?php
namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
class DriverAssigned implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public \App\Models\Trip $trip) {}

    public function broadcastOn()
    {
        return new Channel("trip.{$this->trip->id}");
    }

    public function broadcastAs()
    {
        return 'driver_assigned';
    }

    public function broadcastWith()
    {
        return [
            'trip' => $this->trip,
            'assigned_at' => now()->toISOString(),
        ];
    }
}

