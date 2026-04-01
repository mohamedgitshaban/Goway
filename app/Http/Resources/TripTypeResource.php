<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripTypeResource extends JsonResource
{
    public function toArray($request)
    {
        $user = auth()->user();

        // If authenticated user is a driver → return limited fields
        if ($user && $user->isDriver()) {
            return [
                'id'           => $this->id,
                'name_en'      => $this->name_en,
                'name_ar'      => $this->name_ar,
                'need_licence' => $this->need_licence,
                'image'         => $this->image,
            ];
        }

        // Default (admin, client, or no auth)
        return [
            'id'            => $this->id,
            'name_en'       => $this->name_en,
            'name_ar'       => $this->name_ar,
            'image'         => $this->image,
            'price_per_km'  => $this->price_per_km,
            'profit_margin' => $this->profit_margin,
            'max_distance'  => $this->max_distance,
            'need_licence'  => $this->need_licence,
            'status'        => $this->status,
        ];
    }
}
