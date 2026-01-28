<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public string $action = 'updated' // updated, participant_added, participant_removed, etc.
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversation->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'type' => $this->conversation->type,
                'name' => $this->conversation->name,
                'avatar_url' => $this->conversation->avatar_url,
                'participants' => $this->conversation->participants->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'avatar' => $p->avatar,
                    'is_admin' => $p->pivot->is_admin,
                ])->toArray(),
            ],
            'action' => $this->action,
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.updated';
    }
}
