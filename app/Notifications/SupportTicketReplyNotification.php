<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected SupportTicket $ticket,
        protected SupportTicketReply $reply
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reponse a votre demande - ' . $this->ticket->reference)
            ->greeting('Bonjour !')
            ->line('Une reponse a ete apportee a votre demande de support.')
            ->line('**Reference :** ' . $this->ticket->reference)
            ->line('**Sujet :** ' . $this->ticket->subject)
            ->line('---')
            ->line($this->reply->message)
            ->line('---')
            ->line('Si vous souhaitez repondre, vous pouvez utiliser notre formulaire de contact.')
            ->action('Nous contacter', route('contact.create'))
            ->salutation('L\'equipe ' . config('app.name'));
    }
}
