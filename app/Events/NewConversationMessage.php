<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewConversationMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message,
        public int $recipientId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->recipientId),
        ];
    }

    public function broadcastWith(): array
    {
        $conversation = $this->message->conversation;
        $sender = $this->message->sender;

        return [
            'conversation_id' => $conversation->id,
            'conversation_type' => $conversation->type,
            'conversation_name' => $conversation->isGroupConversation()
                ? $conversation->name
                : $sender->name,
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'has_attachments' => $this->message->attachments()->exists(),
                'created_at' => $this->message->created_at->toISOString(),
            ],
            'sender' => [
                'id' => $sender->id,
                'name' => $sender->name,
                'avatar' => $sender->avatar,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.message';
    }
}
