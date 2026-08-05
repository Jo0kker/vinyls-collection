<?php

use App\Models\ThreadSubscription;
use App\Models\User;
use App\Notifications\NewForumPostNotification;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Models\Thread;

function forumNotificationCategory(): Category
{
    return Category::create([
        'title' => 'Forum notifications',
        'description' => 'Discussions avec notifications',
        'accepts_threads' => true,
        'is_private' => false,
        'color_light_mode' => '#3b82f6',
    ]);
}

function forumNotificationUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'password' => Hash::make('password'),
    ], $attributes));
}

test('thread authors are automatically subscribed to email notifications', function () {
    config()->set('services.recaptcha.enabled', false);

    $author = forumNotificationUser();
    $category = forumNotificationCategory();

    $this->actingAs($author)
        ->post(route('forum.thread.store', $category->id), [
            'title' => 'Nouvelle discussion suivie',
            'content' => 'Premier message',
        ])
        ->assertRedirect();

    $thread = Thread::where('title', 'Nouvelle discussion suivie')->firstOrFail();

    $this->assertDatabaseHas('thread_subscriptions', [
        'user_id' => $author->id,
        'thread_id' => $thread->id,
        'email_notifications' => true,
    ]);
});

test('new forum replies notify subscribed users except the reply author and subscribe the reply author', function () {
    config()->set('services.recaptcha.enabled', false);
    Notification::fake();

    $threadAuthor = forumNotificationUser(['email' => 'thread-author@example.com']);
    $replyAuthor = forumNotificationUser(['email' => 'reply-author@example.com']);
    $manualSubscriber = forumNotificationUser(['email' => 'manual-subscriber@example.com']);
    $category = forumNotificationCategory();

    $thread = Thread::create([
        'category_id' => $category->id,
        'author_id' => $threadAuthor->id,
        'title' => 'Discussion notifiée',
        'pinned' => false,
        'locked' => false,
    ]);

    $firstPost = Post::create([
        'thread_id' => $thread->id,
        'author_id' => $threadAuthor->id,
        'content' => 'Message initial',
        'sequence' => 1,
    ]);

    $thread->update([
        'first_post_id' => $firstPost->id,
        'last_post_id' => $firstPost->id,
    ]);

    ThreadSubscription::create([
        'user_id' => $threadAuthor->id,
        'thread_id' => $thread->id,
        'email_notifications' => true,
    ]);

    ThreadSubscription::create([
        'user_id' => $manualSubscriber->id,
        'thread_id' => $thread->id,
        'email_notifications' => true,
    ]);

    $this->actingAs($replyAuthor)
        ->post(route('forum.post.store', $thread->id), [
            'content' => 'Réponse qui doit partir par mail rapidement',
        ])
        ->assertRedirect();

    $newPost = Post::where('thread_id', $thread->id)->where('sequence', 2)->firstOrFail();

    Notification::assertSentTo($threadAuthor, NewForumPostNotification::class, function ($notification) use ($threadAuthor, $thread, $newPost) {
        return $notification->toArray($threadAuthor)['thread_id'] === $thread->id
            && $notification->toArray($threadAuthor)['post_id'] === $newPost->id;
    });
    Notification::assertSentTo($manualSubscriber, NewForumPostNotification::class);
    Notification::assertNotSentTo($replyAuthor, NewForumPostNotification::class);

    $this->assertDatabaseHas('thread_subscriptions', [
        'user_id' => $replyAuthor->id,
        'thread_id' => $thread->id,
        'email_notifications' => true,
    ]);
});

test('a mail delivery failure does not turn a successfully stored forum reply into a 500 response', function () {
    config()->set('services.recaptcha.enabled', false);

    $threadAuthor = forumNotificationUser(['email' => 'thread-author@example.com']);
    $replyAuthor = forumNotificationUser(['email' => 'reply-author@example.com']);
    $category = forumNotificationCategory();

    $thread = Thread::create([
        'category_id' => $category->id,
        'author_id' => $threadAuthor->id,
        'title' => 'Discussion avec mail indisponible',
        'pinned' => false,
        'locked' => false,
    ]);

    $firstPost = Post::create([
        'thread_id' => $thread->id,
        'author_id' => $threadAuthor->id,
        'content' => 'Message initial',
        'sequence' => 1,
    ]);

    $thread->update([
        'first_post_id' => $firstPost->id,
        'last_post_id' => $firstPost->id,
    ]);

    ThreadSubscription::create([
        'user_id' => $threadAuthor->id,
        'thread_id' => $thread->id,
        'email_notifications' => true,
    ]);

    $mailChannel = Mockery::mock(MailChannel::class);
    $mailChannel->shouldReceive('send')
        ->once()
        ->andThrow(new RuntimeException('Service email indisponible'));
    app()->instance(MailChannel::class, $mailChannel);

    $this->actingAs($replyAuthor)
        ->post(route('forum.post.store', $thread->id), [
            'content' => 'Réponse conservée même si le mail échoue',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('forum_posts', [
        'thread_id' => $thread->id,
        'author_id' => $replyAuthor->id,
        'content' => 'Réponse conservée même si le mail échoue',
        'sequence' => 2,
    ]);
});
