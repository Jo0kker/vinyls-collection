<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SupportTicket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Support] Nouveau ticket - ' . $this->ticket->reference)
            ->greeting('Nouveau ticket de support')
            ->line('**Reference :** ' . $this->ticket->reference)
            ->line('**Email :** ' . $this->ticket->email)
            ->line('**Sujet :** ' . $this->ticket->subject)
            ->line('---')
            ->line($this->ticket->message)
            ->line('---')
            ->action('Voir le ticket', route('admin.support.tickets.show', $this->ticket));
    }
}
