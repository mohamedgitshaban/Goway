<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'                  => $this->id,
            'title'               => $this->title,
            'description'         => $this->description,
            'discount_type'       => $this->discount_type,
            'discount_value'      => $this->discount_value,
            'max_discount_amount' => $this->max_discount_amount,
            'starts_at'           => $this->starts_at,
            'ends_at'             => $this->ends_at,
            'is_active'           => $this->is_active,
            'tripType'            => $this->tripType,
        ];
    }
}
