<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BanarResource extends JsonResource
{
    public function toArray($request)
    {

        return [
            'id' => $this->id,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'image' => $this->image,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'usertype' => $this->usertype,
            'status' => $this->status,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}