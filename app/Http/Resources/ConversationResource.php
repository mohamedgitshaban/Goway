<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'status'         => $this->status,
            'trip_id'        => $this->trip_id,
            'user_id'        => $this->user_id,
            'user_name'      => $this->user?->name,
            'admin_id'       => $this->admin_id,
            'admin_name'     => $this->admin?->name,
            'closed_at'      => $this->closed_at?->toISOString(),
            'created_at'     => $this->created_at->toISOString(),
            'latest_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'messages'       => MessageResource::collection($this->whenLoaded('messages')),
            'unread_count'   => $this->when(
                isset($this->unread_count),
                $this->unread_count ?? 0
            ),
        ];
    }
}
