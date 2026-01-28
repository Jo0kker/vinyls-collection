<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketConfirmationNotification extends Notification implements ShouldQueue
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
            ->subject('Votre demande de support - ' . $this->ticket->reference)
            ->greeting('Bonjour !')
            ->line('Nous avons bien recu votre demande de support.')
            ->line('**Reference :** ' . $this->ticket->reference)
            ->line('**Sujet :** ' . $this->ticket->subject)
            ->line('Notre equipe vous repondra dans les plus brefs delais.')
            ->line('Conservez cette reference pour suivre votre demande.')
            ->salutation('L\'equipe ' . config('app.name'));
    }
}
