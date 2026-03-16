<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripTypeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'name_en'    => $this->name_en,
            'name_ar'    => $this->name_ar,
            'image'      => $this->image,
            'price_per_km' => $this->price_per_km,
            'profit_margin' => $this->profit_margin,
            'max_distance' => $this->max_distance,
            'status'     => $this->status,
        ];
    }
}
