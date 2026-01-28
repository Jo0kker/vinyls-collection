<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for each conversation
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (!$conversation) {
        return false;
    }

    // For support conversations, allow admins even if not explicitly added as participants
    if ($conversation->isSupportConversation() && $user->hasRole('admin')) {
        // Auto-add admin as participant if not already
        if (!$conversation->hasParticipant($user)) {
            $conversation->participants()->attach($user->id, ['is_admin' => true]);
        }
        return true;
    }

    return $conversation->hasParticipant($user);
});

// Private channel for user-specific notifications (new messages, etc.)
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Presence channel for site-wide online/offline status
Broadcast::channel('presence.online', function ($user) {
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
        ];
    }
    return false;
});

// Admin support channel - for receiving notifications about new support messages
Broadcast::channel('support.admin', function ($user) {
    return $user->hasRole('admin');
});
