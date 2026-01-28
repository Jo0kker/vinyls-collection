<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SupportController extends Controller
{
    /**
     * Show support page or redirect to existing support conversation
     */
    public function index(): Response
    {
        $user = Auth::user();
        $conversation = Conversation::findUserSupportConversation($user);

        if ($conversation) {
            return redirect()->route('support.show', $conversation);
        }

        return Inertia::render('Support/Index');
    }

    /**
     * Create or get existing support conversation
     */
    public function create()
    {
        $user = Auth::user();
        $conversation = Conversation::getOrCreateSupportConversation($user);

        return redirect()->route('support.show', $conversation);
    }

    /**
     * Show support conversation
     */
    public function show(Conversation $conversation): Response
    {
        $user = Auth::user();

        // Verify this is a support conversation
        if (!$conversation->isSupportConversation()) {
            abort(404);
        }

        // Verify user has access
        if (!$conversation->hasParticipant($user)) {
            abort(403);
        }

        // Get unread count before marking as read
        $unreadCount = $conversation->getUnreadCountForUser($user);

        // Mark as read
        $conversation->markAsReadForUser($user);

        // Decrement user's total unread count
        if ($unreadCount > 0) {
            $user->decrement('unread_messages_count', min($unreadCount, $user->unread_messages_count));
        }

        // Load conversation with participants and creator
        $conversation->load([
            'participants' => fn ($q) => $q->select('users.id', 'name', 'avatar'),
            'creator:id,name,avatar',
        ]);

        // Get messages with cursor pagination
        $messages = $conversation->messages()
            ->with(['sender:id,name,avatar', 'attachments'])
            ->orderByDesc('created_at')
            ->cursorPaginate(50);

        return Inertia::render('Support/Show', [
            'conversation' => $this->formatSupportConversation($conversation, $user),
            'messages' => [
                'data' => $messages->items(),
                'next_cursor' => $messages->nextCursor()?->encode(),
            ],
        ]);
    }

    /**
     * API: Create or get existing support conversation
     */
    public function apiCreate()
    {
        $user = Auth::user();
        $conversation = Conversation::getOrCreateSupportConversation($user);

        $conversation->load([
            'participants' => fn ($q) => $q->select('users.id', 'name', 'avatar'),
            'creator:id,name,avatar',
        ]);

        return response()->json([
            'conversation' => $this->formatSupportConversation($conversation, $user),
        ]);
    }

    private function formatSupportConversation(Conversation $conversation, $user): array
    {
        // For admins, show "Support - UserName" to distinguish conversations
        if ($user->hasRole('admin') && $conversation->created_by !== $user->id) {
            $creator = $conversation->creator;
            $name = 'Support - ' . ($creator?->name ?? 'Utilisateur');
            $avatarUrl = $creator?->avatar;
        } else {
            $name = 'Support';
            $avatarUrl = null;
        }

        return [
            'id' => $conversation->id,
            'type' => 'support',
            'name' => $name,
            'avatar_url' => $avatarUrl,
            'support_status' => $conversation->support_status,
            'participants' => $conversation->participants->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'avatar' => $p->avatar,
                'is_admin' => $p->pivot->is_admin ?? false,
            ])->toArray(),
            'updated_at' => $conversation->updated_at->toISOString(),
        ];
    }
}
