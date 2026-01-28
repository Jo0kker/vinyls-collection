<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketConfirmationNotification;
use App\Notifications\SupportTicketCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Show contact form
     */
    public function create(): Response
    {
        return Inertia::render('Contact/Create');
    }

    /**
     * Store new support ticket
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:10000',
        ]);

        $ticket = SupportTicket::create($validated);

        // Send confirmation email to the user
        Notification::route('mail', $ticket->email)
            ->notify(new SupportTicketConfirmationNotification($ticket));

        // Notify admins
        $admins = User::role('admin')->get();
        Notification::send($admins, new SupportTicketCreatedNotification($ticket));

        return redirect()->route('contact.success')
            ->with('ticket_reference', $ticket->reference);
    }

    /**
     * Show success page
     */
    public function success(): Response
    {
        return Inertia::render('Contact/Success', [
            'reference' => session('ticket_reference'),
        ]);
    }
}
