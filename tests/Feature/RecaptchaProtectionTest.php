<?php

use App\Models\User;
use App\Notifications\SupportTicketConfirmationNotification;
use App\Notifications\SupportTicketCreatedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Models\Thread;

beforeEach(function () {
    config()->set('services.recaptcha.enabled', true);
    config()->set('services.recaptcha.site_key', 'site-key');
    config()->set('services.recaptcha.secret_key', 'secret-key');
    config()->set('services.recaptcha.minimum_score', 0.5);
});

function validRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'recaptcha_token' => 'valid-token',
    ], $overrides);
}

function fakeSuccessfulRecaptcha(string $action = 'register', float $score = 0.9): void
{
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
            'score' => $score,
            'action' => $action,
        ]),
    ]);
}

function recaptchaUser(array $attributes = []): User
{
    Role::firstOrCreate(['name' => 'user']);

    return User::factory()->create(array_merge([
        'email' => 'member@example.com',
        'password' => Hash::make('password'),
    ], $attributes));
}

function forumCategory(): Category
{
    return Category::create([
        'title' => 'Général',
        'description' => 'Discussions générales',
        'accepts_threads' => true,
        'is_private' => false,
        'color_light_mode' => '#3b82f6',
    ]);
}

function forumThreadWithPost(?User $author = null): Thread
{
    $author ??= recaptchaUser();
    $category = forumCategory();

    $thread = Thread::create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Discussion existante',
        'pinned' => false,
        'locked' => false,
    ]);

    $post = Post::create([
        'thread_id' => $thread->id,
        'author_id' => $author->id,
        'content' => 'Premier message',
        'sequence' => 1,
    ]);

    $thread->update([
        'first_post_id' => $post->id,
        'last_post_id' => $post->id,
    ]);

    return $thread;
}

test('registration requires a recaptcha token when recaptcha is enabled', function () {
    Role::firstOrCreate(['name' => 'user']);

    $response = $this->from('/register')->post('/register', validRegistrationPayload([
        'recaptcha_token' => '',
    ]));

    $response->assertRedirect('/register');
    $response->assertSessionHasErrors('recaptcha_token');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});

test('registration rejects a recaptcha score below the configured threshold', function () {
    Role::firstOrCreate(['name' => 'user']);

    fakeSuccessfulRecaptcha(score: 0.2);

    $response = $this->from('/register')->post('/register', validRegistrationPayload());

    $response->assertRedirect('/register');
    $response->assertSessionHasErrors('recaptcha_token');
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});

test('registration accepts a valid recaptcha token', function () {
    Role::firstOrCreate(['name' => 'user']);

    fakeSuccessfulRecaptcha();

    $response = $this->post('/register', validRegistrationPayload());

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
});

test('login requires a recaptcha token when recaptcha is enabled', function () {
    recaptchaUser();

    $response = $this->from('/login')->post('/login', [
        'email' => 'member@example.com',
        'password' => 'password',
        'recaptcha_token' => '',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('recaptcha_token');
    $this->assertGuest();
});

test('login accepts a valid recaptcha token', function () {
    recaptchaUser();
    fakeSuccessfulRecaptcha(action: 'login');

    $response = $this->post('/login', [
        'email' => 'member@example.com',
        'password' => 'password',
        'recaptcha_token' => 'valid-token',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();
});

test('forgot password requires a recaptcha token when recaptcha is enabled', function () {
    $response = $this->from('/forgot-password')->post('/forgot-password', [
        'email' => 'member@example.com',
        'recaptcha_token' => '',
    ]);

    $response->assertRedirect('/forgot-password');
    $response->assertSessionHasErrors('recaptcha_token');
});

test('reset password requires a recaptcha token when recaptcha is enabled', function () {
    $response = $this->from('/reset-password/token')->post('/reset-password', [
        'token' => 'token',
        'email' => 'member@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'recaptcha_token' => '',
    ]);

    $response->assertRedirect('/reset-password/token');
    $response->assertSessionHasErrors('recaptcha_token');
});

test('public contact form requires recaptcha for guests', function () {
    Notification::fake();

    $response = $this->from('/contact')->post('/contact', [
        'email' => 'visitor@example.com',
        'subject' => 'Question',
        'message' => 'Bonjour, je veux vous contacter.',
        'recaptcha_token' => '',
    ]);

    $response->assertRedirect('/contact');
    $response->assertSessionHasErrors('recaptcha_token');
    $this->assertDatabaseMissing('support_tickets', ['email' => 'visitor@example.com']);
});

test('public contact form accepts a valid recaptcha token for guests', function () {
    Notification::fake();
    User::factory()->create()->assignRole(Role::firstOrCreate(['name' => 'admin']));

    fakeSuccessfulRecaptcha(action: 'contact');

    $response = $this->post('/contact', [
        'email' => 'visitor@example.com',
        'subject' => 'Question',
        'message' => 'Bonjour, je veux vous contacter.',
        'recaptcha_token' => 'valid-token',
    ]);

    $response->assertRedirect(route('contact.success'));
    $this->assertDatabaseHas('support_tickets', ['email' => 'visitor@example.com']);
    Notification::assertSentOnDemand(SupportTicketConfirmationNotification::class);
    Notification::assertSentTo(User::role('admin')->first(), SupportTicketCreatedNotification::class);
});

test('forum thread creation requires a recaptcha token when recaptcha is enabled', function () {
    $user = recaptchaUser();
    $category = forumCategory();

    $response = $this->actingAs($user)
        ->from(route('forum.thread.create', ['category_id' => $category->id]))
        ->post(route('forum.thread.store', ['category_id' => $category->id]), [
            'title' => 'Nouvelle discussion',
            'content' => 'Premier message',
            'recaptcha_token' => '',
        ]);

    $response->assertRedirect(route('forum.thread.create', ['category_id' => $category->id]));
    $response->assertSessionHasErrors('recaptcha_token');
    $this->assertDatabaseMissing('forum_threads', ['title' => 'Nouvelle discussion']);
});

test('forum thread creation accepts a valid recaptcha token', function () {
    $user = recaptchaUser();
    $category = forumCategory();
    fakeSuccessfulRecaptcha(action: 'forum_thread_create');

    $response = $this->actingAs($user)
        ->post(route('forum.thread.store', ['category_id' => $category->id]), [
            'title' => 'Nouvelle discussion',
            'content' => 'Premier message',
            'recaptcha_token' => 'valid-token',
        ]);

    $thread = Thread::where('title', 'Nouvelle discussion')->first();

    $this->assertNotNull($thread);
    $response->assertRedirect(route('forum.thread.show', $thread->id));
    $this->assertDatabaseHas('forum_posts', [
        'thread_id' => $thread->id,
        'content' => 'Premier message',
    ]);
});

test('forum reply requires a recaptcha token when recaptcha is enabled', function () {
    $user = recaptchaUser();
    $thread = forumThreadWithPost($user);

    $response = $this->actingAs($user)
        ->from(route('forum.thread.show', $thread->id))
        ->post(route('forum.post.store', ['thread_id' => $thread->id]), [
            'content' => 'Nouvelle réponse',
            'recaptcha_token' => '',
        ]);

    $response->assertRedirect(route('forum.thread.show', $thread->id));
    $response->assertSessionHasErrors('recaptcha_token');
    $this->assertDatabaseMissing('forum_posts', ['content' => 'Nouvelle réponse']);
});

test('forum reply accepts a valid recaptcha token', function () {
    $user = recaptchaUser();
    $thread = forumThreadWithPost($user);
    fakeSuccessfulRecaptcha(action: 'forum_post_reply');

    $response = $this->actingAs($user)
        ->post(route('forum.post.store', ['thread_id' => $thread->id]), [
            'content' => 'Nouvelle réponse',
            'recaptcha_token' => 'valid-token',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('forum_posts', [
        'thread_id' => $thread->id,
        'content' => 'Nouvelle réponse',
    ]);
});
