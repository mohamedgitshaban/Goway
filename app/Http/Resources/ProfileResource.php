<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'first_name'     => $this->first_name,
            'last_name'      => $this->last_name,
            'full_name'      => $this->name,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'usertype'       => $this->usertype,
            'personal_image' => $this->personal_image,
            'status'         => $this->status,
            'is_online'      => $this->is_online,
            'wallet'         => $this->wallet ? [
                'id' => $this->wallet->id,
                'balance' => (float) $this->wallet->balance,
            ] : null,
        ];
    }
}