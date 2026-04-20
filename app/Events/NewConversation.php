<?php

namespace App\Events;

use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NewConversation implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public int $recipientId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("user.{$this->recipientId}.chat");
    }

    public function broadcastAs(): string
    {
        return 'new_conversation';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation' => new ConversationResource(
                $this->conversation->load(['user', 'latestMessage', 'trip'])
            ),
            'chat_channel' => "chat.{$this->conversation->id}",
        ];
    }
}
