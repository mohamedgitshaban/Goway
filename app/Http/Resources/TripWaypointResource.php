<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripWaypointResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            'lat' => $this->lat,
            'lng' => $this->lng,
            'address' => $this->address,

            'order' => $this->order, // ترتيب الـ waypoint لو موجود
        ];
    }
}
