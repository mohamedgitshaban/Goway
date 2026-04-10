<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'full_name'  => $this->name? $this->name : $this->first_name . ' ' . $this->last_name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'usertype'   => $this->usertype,
            'personal_image'  => $this->personal_image,
            'wallet_balance' => $this->wallet ? $this->wallet->balance : 0,
            'status'     => $this->status,
            'role'       => $this->role ? [
                'id' => $this->role->id,
                'name_en' => $this->role->name_en,
                'name_ar' => $this->role->name_ar,
                'permissions' => $this->role->permissions->map(function($perm) {
                    return [
                        'id' => $perm->id,
                        'name' => $perm->name,
                        'label' => $perm->label ?? null,
                    ];
                })->values(),
            ] : null,
        ];
    }
}
