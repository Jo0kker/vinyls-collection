<?php

namespace App\Http\Controllers;

use App\Events\ConversationUpdated;
use App\Events\NewConversation;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get the most recent conversation
        $latestConversation = $user->conversations()
            ->orderByDesc('updated_at')
            ->first();

        // If there's a conversation, redirect to it
        if ($latestConversation) {
            return redirect()->route('messages.show', $latestConversation);
        }

        // Otherwise show empty state
        return Inertia::render('Messages/Show', [
            'conversation' => null,
            'messages' => ['data' => [], 'next_cursor' => null],
            'conversations' => [],
        ]);
    }

    public function show(Conversation $conversation): Response
    {
        $user = Auth::user();

        // Check if user is a participant
        if (!$conversation->hasParticipant($user)) {
            abort(403, 'Vous n\'avez pas accès à cette conversation.');
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

        // Get messages with cursor pagination (latest first, then reverse for display)
        $messages = $conversation->messages()
            ->with(['sender:id,name,avatar', 'attachments'])
            ->orderByDesc('created_at')
            ->cursorPaginate(50);

        // Get all conversations for sidebar
        $conversations = $user->conversations()
            ->with(['participants' => fn ($q) => $q->select('users.id', 'name', 'avatar')])
            ->with(['latestMessage.sender:id,name'])
            ->with(['creator:id,name,avatar'])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)
                    ->whereHas('conversation.participantRecords', function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->where(function ($q2) {
                                $q2->whereNull('last_read_at')
                                    ->orWhereColumn('messages.created_at', '>', 'conversation_participants.last_read_at');
                            });
                    });
            }])
            ->get()
            ->map(fn ($c) => $this->formatConversation($c, $user));

        return Inertia::render('Messages/Show', [
            'conversation' => $this->formatConversation($conversation, $user),
            'messages' => [
                'data' => $messages->items(),
                'next_cursor' => $messages->nextCursor()?->encode(),
                'prev_cursor' => $messages->previousCursor()?->encode(),
            ],
            'conversations' => $conversations,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:direct,group',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'name' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();
        $type = $request->input('type');
        $participantIds = $request->input('participant_ids');

        // For direct messages, check if conversation already exists
        if ($type === 'direct' && count($participantIds) === 1) {
            $otherUser = User::findOrFail($participantIds[0]);
            $conversation = Conversation::getOrCreateDirectConversation($user, $otherUser);

            return redirect()->route('messages.show', $conversation);
        }

        // Create group conversation
        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $request->input('name'),
            'created_by' => $user->id,
        ]);

        // Add creator as admin
        $conversation->participants()->attach($user->id, ['is_admin' => true]);

        // Add other participants
        foreach ($participantIds as $participantId) {
            if ($participantId != $user->id) {
                $conversation->participants()->attach($participantId, ['is_admin' => false]);
            }
        }

        return redirect()->route('messages.show', $conversation);
    }

    public function addParticipants(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        if (!$conversation->isGroupConversation()) {
            return back()->with('error', 'Impossible d\'ajouter des participants à une conversation directe.');
        }

        $participant = $conversation->participantRecords()->where('user_id', $user->id)->first();
        if (!$participant || !$participant->is_admin) {
            return back()->with('error', 'Seuls les administrateurs peuvent ajouter des participants.');
        }

        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        foreach ($request->input('user_ids') as $userId) {
            if (!$conversation->hasParticipant(User::find($userId))) {
                $conversation->participants()->attach($userId, ['is_admin' => false]);
            }
        }

        broadcast(new ConversationUpdated($conversation, 'participant_added'))->toOthers();

        return back()->with('success', 'Participants ajoutés avec succès.');
    }

    public function removeParticipant(Request $request, Conversation $conversation, User $participant)
    {
        $user = Auth::user();

        if (!$conversation->isGroupConversation()) {
            return back()->with('error', 'Impossible de retirer des participants d\'une conversation directe.');
        }

        $currentParticipant = $conversation->participantRecords()->where('user_id', $user->id)->first();

        // Allow removal if user is admin or removing themselves
        if (!$currentParticipant || (!$currentParticipant->is_admin && $user->id !== $participant->id)) {
            return back()->with('error', 'Vous n\'avez pas la permission de retirer ce participant.');
        }

        $conversation->participants()->detach($participant->id);

        broadcast(new ConversationUpdated($conversation, 'participant_removed'))->toOthers();

        // If user removed themselves, redirect to messages index
        if ($user->id === $participant->id) {
            return redirect()->route('messages.index')->with('success', 'Vous avez quitté la conversation.');
        }

        return back()->with('success', 'Participant retiré avec succès.');
    }

    public function leave(Conversation $conversation)
    {
        $user = Auth::user();

        if (!$conversation->hasParticipant($user)) {
            return back()->with('error', 'Vous n\'êtes pas membre de cette conversation.');
        }

        $conversation->participants()->detach($user->id);

        broadcast(new ConversationUpdated($conversation, 'participant_left'))->toOthers();

        return redirect()->route('messages.index')->with('success', 'Vous avez quitté la conversation.');
    }

    public function updateGroup(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        if (!$conversation->isGroupConversation()) {
            return back()->with('error', 'Cette conversation n\'est pas un groupe.');
        }

        $participant = $conversation->participantRecords()->where('user_id', $user->id)->first();
        if (!$participant || !$participant->is_admin) {
            return back()->with('error', 'Seuls les administrateurs peuvent modifier le groupe.');
        }

        $request->validate([
            'name' => 'nullable|string|max:100',
        ]);

        $conversation->update([
            'name' => $request->input('name'),
        ]);

        broadcast(new ConversationUpdated($conversation, 'updated'))->toOthers();

        return back()->with('success', 'Groupe mis à jour avec succès.');
    }

    /**
     * API endpoint to get conversations list (for chat widget)
     */
    public function apiIndex()
    {
        $user = Auth::user();

        $conversations = $user->conversations()
            ->with(['participants' => fn ($q) => $q->select('users.id', 'name', 'avatar')])
            ->with(['latestMessage.sender:id,name'])
            ->with(['creator:id,name,avatar'])
            ->withCount(['messages as unread_count' => function ($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)
                    ->whereHas('conversation.participantRecords', function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->where(function ($q2) {
                                $q2->whereNull('last_read_at')
                                    ->orWhereColumn('messages.created_at', '>', 'conversation_participants.last_read_at');
                            });
                    });
            }])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($conversation) => $this->formatConversation($conversation, $user));

        return response()->json($conversations);
    }

    /**
     * API endpoint to create conversation (for chat widget)
     */
    public function apiStore(Request $request)
    {
        $request->validate([
            'type' => 'required|in:direct,group',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'exists:users,id',
            'name' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();
        $type = $request->input('type');
        $participantIds = $request->input('participant_ids');

        // For direct messages, check if conversation already exists
        if ($type === 'direct' && count($participantIds) === 1) {
            $otherUser = User::findOrFail($participantIds[0]);
            $wasRecentlyCreated = !Conversation::findDirectConversation($user, $otherUser);
            $conversation = Conversation::getOrCreateDirectConversation($user, $otherUser);

            $conversation->load(['participants' => fn ($q) => $q->select('users.id', 'name', 'avatar')]);

            // Notify other participant if conversation was just created
            if ($wasRecentlyCreated) {
                broadcast(new NewConversation($conversation, $otherUser->id, $user))->toOthers();
            }

            return response()->json([
                'id' => $conversation->id,
                'conversation' => $this->formatConversation($conversation, $user),
            ]);
        }

        // Create group conversation
        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $request->input('name'),
            'created_by' => $user->id,
        ]);

        // Add creator as admin
        $conversation->participants()->attach($user->id, ['is_admin' => true]);

        // Add other participants
        foreach ($participantIds as $participantId) {
            if ($participantId != $user->id) {
                $conversation->participants()->attach($participantId, ['is_admin' => false]);
            }
        }

        $conversation->load(['participants' => fn ($q) => $q->select('users.id', 'name', 'avatar')]);

        // Notify all other participants
        foreach ($participantIds as $participantId) {
            if ($participantId != $user->id) {
                broadcast(new NewConversation($conversation, $participantId, $user))->toOthers();
            }
        }

        return response()->json([
            'id' => $conversation->id,
            'conversation' => $this->formatConversation($conversation, $user),
        ]);
    }

    private function formatConversation(Conversation $conversation, User $currentUser): array
    {
        $otherParticipant = $conversation->isDirectConversation()
            ? $conversation->participants->firstWhere('id', '!=', $currentUser->id)
            : null;

        // Determine name based on conversation type
        if ($conversation->isSupportConversation()) {
            // For admins, show "Support - UserName" to distinguish conversations
            // For regular users, just show "Support"
            if ($currentUser->hasRole('admin') && $conversation->created_by !== $currentUser->id) {
                $creator = $conversation->creator ?? User::find($conversation->created_by);
                $name = 'Support - ' . ($creator?->name ?? 'Utilisateur');
            } else {
                $name = 'Support';
            }
        } elseif ($conversation->isGroupConversation()) {
            // Use group name or generate from participant names
            $name = $conversation->name ?? $conversation->participants
                ->where('id', '!=', $currentUser->id)
                ->pluck('name')
                ->take(3)
                ->implode(', ');
        } else {
            $name = $otherParticipant?->name ?? 'Utilisateur supprimé';
        }

        // Determine avatar based on conversation type
        if ($conversation->isSupportConversation()) {
            // For admins viewing support, show the creator's avatar
            if ($currentUser->hasRole('admin') && $conversation->created_by !== $currentUser->id) {
                $creator = $conversation->creator ?? User::find($conversation->created_by);
                $avatarUrl = $creator?->avatar;
            } else {
                $avatarUrl = null;
            }
        } elseif ($conversation->isGroupConversation()) {
            $avatarUrl = $conversation->avatar_url;
        } else {
            $avatarUrl = $otherParticipant?->avatar;
        }

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'name' => $name,
            'avatar_url' => $avatarUrl,
            'support_status' => $conversation->support_status,
            'participants' => $conversation->participants->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'avatar' => $p->avatar,
                'is_admin' => $p->pivot->is_admin ?? false,
            ])->toArray(),
            'latest_message' => $conversation->latestMessage ? [
                'content' => $conversation->latestMessage->content,
                'sender_name' => $conversation->latestMessage->sender?->name,
                'created_at' => $conversation->latestMessage->created_at->toISOString(),
            ] : null,
            'unread_count' => $conversation->unread_count ?? 0,
            'updated_at' => $conversation->updated_at->toISOString(),
        ];
    }
}
