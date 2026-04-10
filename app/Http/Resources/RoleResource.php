<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request)
    {
        // Use pre-computed payload when controller attached it
        $permissionPayload = $this->resource->permission_payload ?? null;

        return [
            'id' => $this->id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            // include other role fields if needed (image, status, etc.)
            'permissions' => is_array($permissionPayload) && array_key_exists('permission_rules', $permissionPayload)
                ? $permissionPayload['permission_rules']
                : $this->permissions->map(function ($perm) {
                    return [
                        'id' => $perm->id,
                        'name' => $perm->name,
                        'label' => $perm->label ?? null,
                    ];
                })->values(),
        ];
    }
}
