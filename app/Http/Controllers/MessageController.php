<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\NewConversationMessage;
use App\Events\UserTyping;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        $request->validate([
            'content' => 'required|string|max:10000',
        ]);

        // Create the message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => $request->input('content'),
        ]);

        // Load relationships for broadcasting
        $message->load(['sender:id,name,avatar', 'attachments']);

        // Broadcast to conversation channel
        broadcast(new MessageSent($message))->toOthers();

        // Notify other participants via their user channels
        foreach ($conversation->participants as $participant) {
            if ($participant->id !== $user->id) {
                broadcast(new NewConversationMessage($message, $participant->id));

                // Increment unread count
                $participant->increment('unread_messages_count');
            }
        }

        return response()->json([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                    'avatar' => $message->sender->avatar,
                ],
                'attachments' => $message->attachments->map(fn ($a) => [
                    'id' => $a->id,
                    'type' => $a->type,
                    'url' => $a->url,
                    'filename' => $a->filename,
                    'mime_type' => $a->mime_type,
                    'metadata' => $a->metadata,
                ])->toArray(),
            ],
        ]);
    }

    public function loadMore(Request $request, Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        $cursor = $request->input('cursor');

        $query = $conversation->messages()
            ->with(['sender:id,name,avatar', 'attachments'])
            ->orderByDesc('created_at');

        if ($cursor) {
            $messages = $query->cursorPaginate(50, ['*'], 'cursor', $cursor);
        } else {
            $messages = $query->cursorPaginate(50);
        }

        return response()->json([
            'data' => $messages->items(),
            'next_cursor' => $messages->nextCursor()?->encode(),
            'prev_cursor' => $messages->previousCursor()?->encode(),
        ]);
    }

    public function typing(Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        broadcast(new UserTyping($conversation, $user, true))->toOthers();

        return response()->json(['success' => true]);
    }

    public function markAsRead(Conversation $conversation): JsonResponse
    {
        $user = Auth::user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json(['error' => 'Accès non autorisé.'], 403);
        }

        // Get unread count before marking as read
        $unreadCount = $conversation->getUnreadCountForUser($user);

        $conversation->markAsReadForUser($user);

        // Decrement user's total unread count
        if ($unreadCount > 0) {
            $user->decrement('unread_messages_count', min($unreadCount, $user->unread_messages_count));
        }

        broadcast(new MessageRead($conversation, $user))->toOthers();

        return response()->json(['success' => true]);
    }
}
