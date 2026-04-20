<?php

namespace App\Events;

use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NewChatMessage implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): Channel
    {
        return new Channel("chat.{$this->message->conversation_id}");
    }

    public function broadcastAs(): string
    {
        return 'new_message';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => new MessageResource($this->message->load('sender')),
        ];
    }
}
