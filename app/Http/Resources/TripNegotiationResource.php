<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripNegotiationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'driver_id' => $this->driver_id,
            'proposed_price' => (float) $this->proposed_price,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'trip' => $this->whenLoaded('trip', fn () => new TripResource($this->trip)),
            'driver' => $this->whenLoaded('driver', fn () => new DriverResource($this->driver)),
        ];
    }
}
