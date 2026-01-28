<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class SupportAdminController extends Controller
{
    /**
     * Dashboard showing all support items
     */
    public function index(): Response
    {
        $user = Auth::user();

        // Support conversations (logged-in users)
        $conversations = Conversation::where('type', 'support')
            ->with(['creator:id,name,email,avatar', 'latestMessage.sender:id,name'])
            ->with(['participants' => fn ($q) => $q->select('users.id', 'name', 'avatar')])
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
            ->map(fn ($c) => [
                'id' => $c->id,
                'type' => 'support',
                'name' => $c->name,
                'support_status' => $c->support_status,
                'creator' => $c->creator ? [
                    'id' => $c->creator->id,
                    'name' => $c->creator->name,
                    'email' => $c->creator->email,
                    'avatar' => $c->creator->avatar,
                ] : null,
                'latest_message' => $c->latestMessage ? [
                    'content' => $c->latestMessage->content,
                    'sender_name' => $c->latestMessage->sender?->name,
                    'created_at' => $c->latestMessage->created_at->toISOString(),
                ] : null,
                'unread_count' => $c->unread_count ?? 0,
                'updated_at' => $c->updated_at->toISOString(),
            ]);

        // Support tickets (non-logged-in users)
        $tickets = SupportTicket::with(['user:id,name', 'assignedAdmin:id,name'])
            ->withCount('replies')
            ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'pending' THEN 2 WHEN 'resolved' THEN 3 WHEN 'closed' THEN 4 ELSE 5 END")
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Admin/Support/Index', [
            'conversations' => $conversations,
            'tickets' => $tickets,
        ]);
    }

    /**
     * Show a specific support ticket
     */
    public function showTicket(SupportTicket $ticket): Response
    {
        $ticket->load(['user', 'assignedAdmin', 'replies.admin']);

        // Get list of admins for assignment
        $admins = User::role('admin')->select('id', 'name')->get();

        return Inertia::render('Admin/Support/ShowTicket', [
            'ticket' => $ticket,
            'admins' => $admins,
        ]);
    }

    /**
     * Reply to a support ticket
     */
    public function replyToTicket(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:10000',
            'is_internal_note' => 'boolean',
            'send_email' => 'boolean',
        ]);

        $reply = $ticket->replies()->create([
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'is_internal_note' => $validated['is_internal_note'] ?? false,
        ]);

        // Send email notification if requested and not internal note
        $shouldSendEmail = ($validated['send_email'] ?? true) && !$reply->is_internal_note;

        if ($shouldSendEmail) {
            Notification::route('mail', $ticket->email)
                ->notify(new SupportTicketReplyNotification($ticket, $reply));

            $reply->update(['email_sent' => true]);
        }

        // Update ticket status to pending if it was open
        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'pending']);
        }

        return back()->with('success', 'Reponse envoyee avec succes.');
    }

    /**
     * Update ticket status
     */
    public function updateTicketStatus(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,pending,resolved,closed',
        ]);

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'resolved') {
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);

        return back()->with('success', 'Statut mis a jour.');
    }

    /**
     * Assign ticket to admin
     */
    public function assignTicket(Request $request, SupportTicket $ticket)
    {
        $validated = $request->validate([
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $ticket->update(['assigned_to' => $validated['assigned_to']]);

        return back()->with('success', 'Ticket assigne.');
    }

    /**
     * Show a support conversation (redirects to support.show)
     */
    public function showConversation(Conversation $conversation)
    {
        if (!$conversation->isSupportConversation()) {
            abort(404);
        }

        return redirect()->route('support.show', $conversation);
    }

    /**
     * Update support conversation status
     */
    public function updateConversationStatus(Request $request, Conversation $conversation)
    {
        if (!$conversation->isSupportConversation()) {
            abort(404);
        }

        $validated = $request->validate([
            'status' => 'required|in:open,pending,resolved,closed',
        ]);

        $conversation->updateSupportStatus($validated['status']);

        return back()->with('success', 'Statut mis a jour.');
    }
}
