<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewConversation implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public int $recipientId,
        public User $creator
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->recipientId),
        ];
    }

    public function broadcastWith(): array
    {
        $otherParticipants = $this->conversation->participants
            ->where('id', '!=', $this->recipientId);

        // For direct conversations, show the other person's name
        // For groups, show group name or participant names
        if ($this->conversation->isDirectConversation()) {
            $name = $this->creator->name;
            $avatarUrl = $this->creator->avatar;
        } else {
            $name = $this->conversation->name ?? $otherParticipants->pluck('name')->take(3)->implode(', ');
            $avatarUrl = $this->conversation->avatar_url;
        }

        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'type' => $this->conversation->type,
                'name' => $name,
                'avatar_url' => $avatarUrl,
                'participants' => $this->conversation->participants->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'avatar' => $p->avatar,
                    'is_admin' => $p->pivot->is_admin ?? false,
                ])->toArray(),
                'latest_message' => null,
                'unread_count' => 0,
                'updated_at' => $this->conversation->updated_at->toISOString(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'new.conversation';
    }
}
