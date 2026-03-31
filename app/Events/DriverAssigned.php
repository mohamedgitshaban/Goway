<?php
namespace App\Events;

use App\Models\Trip;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
class DriverAssigned implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public \App\Models\Trip $trip) {}

    public function broadcastOn()
    {
        return new PrivateChannel("trip.{$this->trip->id}");
    }

    public function broadcastAs()
    {
        return 'driver_assigned';
    }

    public function broadcastWith()
    {
        return [
            'driver' => [
                'id' => $this->trip->driver->id,
                'name' => $this->trip->driver->name,
                'phone' => $this->trip->driver->phone,
            ],
        ];
    }
}
