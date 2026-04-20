<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id'       => $this->sender_id,
            'sender_name'     => $this->sender?->name,
            'sender_type'     => $this->sender?->usertype,
            'body'            => $this->body,
            'attachment'      => $this->attachment,
            'read_at'         => $this->read_at?->toISOString(),
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
