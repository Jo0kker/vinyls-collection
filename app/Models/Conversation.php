<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'avatar_url',
        'created_by',
        'support_status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['last_read_at', 'is_admin', 'notifications_enabled'])
            ->withTimestamps();
    }

    public function participantRecords(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function isDirectConversation(): bool
    {
        return $this->type === 'direct';
    }

    public function isGroupConversation(): bool
    {
        return $this->type === 'group';
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    public function getOtherParticipant(User $user): ?User
    {
        if (!$this->isDirectConversation()) {
            return null;
        }

        return $this->participants()->where('user_id', '!=', $user->id)->first();
    }

    public function getUnreadCountForUser(User $user): int
    {
        $participant = $this->participantRecords()->where('user_id', $user->id)->first();

        if (!$participant) {
            return 0;
        }

        $query = $this->messages()->where('sender_id', '!=', $user->id);

        if ($participant->last_read_at) {
            $query->where('created_at', '>', $participant->last_read_at);
        }

        return $query->count();
    }

    public function markAsReadForUser(User $user): void
    {
        $this->participantRecords()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    public static function findDirectConversation(User $user1, User $user2): ?self
    {
        return self::where('type', 'direct')
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user1->id))
            ->whereHas('participants', fn ($q) => $q->where('user_id', $user2->id))
            ->first();
    }

    public static function getOrCreateDirectConversation(User $user1, User $user2): self
    {
        $conversation = self::findDirectConversation($user1, $user2);

        if ($conversation) {
            return $conversation;
        }

        $conversation = self::create([
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);

        $conversation->participants()->attach([
            $user1->id => ['is_admin' => false],
            $user2->id => ['is_admin' => false],
        ]);

        return $conversation;
    }

    public function isSupportConversation(): bool
    {
        return $this->type === 'support';
    }

    public static function findUserSupportConversation(User $user): ?self
    {
        return self::where('type', 'support')
            ->where('created_by', $user->id)
            ->first();
    }

    public static function getOrCreateSupportConversation(User $user): self
    {
        $conversation = self::findUserSupportConversation($user);

        if ($conversation) {
            return $conversation;
        }

        $conversation = self::create([
            'type' => 'support',
            'name' => 'Support - ' . $user->name,
            'created_by' => $user->id,
            'support_status' => 'open',
        ]);

        // Add user as participant
        $conversation->participants()->attach($user->id, ['is_admin' => false]);

        // Add all admins as participants
        $admins = User::role('admin')->get();
        foreach ($admins as $admin) {
            $conversation->participants()->attach($admin->id, ['is_admin' => true]);
        }

        return $conversation;
    }

    public function updateSupportStatus(string $status): void
    {
        if ($this->isSupportConversation()) {
            $this->update(['support_status' => $status]);
        }
    }
}
