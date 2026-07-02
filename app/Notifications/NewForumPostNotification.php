<?php

namespace App\Notifications;

use App\Models\ForumPost;
use App\Models\ForumThread;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewForumPostNotification extends Notification
{
    protected ForumThread $thread;
    protected ForumPost $post;

    /**
     * Create a new notification instance.
     */
    public function __construct(ForumThread $thread, ForumPost $post)
    {
        $this->thread = $thread;
        $this->post = $post;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $threadUrl = route('forum.thread.show', ['thread_id' => $this->thread->id]) . '#post-' . $this->post->sequence;
        $unsubscribeUrl = route('forum.thread.unsubscribe', ['thread_id' => $this->thread->id]);

        $authorName = $this->post->author?->name ?? 'Un utilisateur';

        return (new MailMessage)
            ->subject('Nouvelle réponse : ' . $this->thread->title)
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line('Une nouvelle réponse a été publiée dans une discussion que vous suivez.')
            ->line('**Discussion :** ' . $this->thread->title)
            ->line('**Auteur :** ' . $authorName)
            ->action('Voir la réponse', $threadUrl)
            ->line('---')
            ->line('Vous recevez cet email car vous suivez cette discussion.')
            ->salutation('L\'équipe Vinyls Collection');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'thread_id' => $this->thread->id,
            'thread_title' => $this->thread->title,
            'post_id' => $this->post->id,
            'author_id' => $this->post->author_id,
            'author_name' => $this->post->author?->name,
        ];
    }
}
