<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Models\Thread;

function adminUser(): User
{
    $adminRole = Role::firstOrCreate(['name' => 'admin']);

    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => Hash::make('password'),
    ]);
    $admin->assignRole($adminRole);

    return $admin;
}

function moderatedForumThread(User $author): Thread
{
    $category = Category::create([
        'title' => 'Général',
        'description' => 'Discussions générales',
        'accepts_threads' => true,
        'is_private' => false,
        'color_light_mode' => '#3b82f6',
    ]);

    $thread = Thread::create([
        'category_id' => $category->id,
        'author_id' => $author->id,
        'title' => 'Discussion à modérer',
        'pinned' => false,
        'locked' => false,
    ]);

    $post = Post::create([
        'thread_id' => $thread->id,
        'author_id' => $author->id,
        'content' => 'Message à modérer',
        'sequence' => 1,
    ]);

    $thread->update([
        'first_post_id' => $post->id,
        'last_post_id' => $post->id,
    ]);

    return $thread;
}

test('admin can list users', function () {
    $admin = adminUser();
    $user = User::factory()->create([
        'name' => 'Member Listed',
        'email' => 'member-listed@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Admin/Users/Index')
        ->has('users.data')
    );
    $response->assertSee('member-listed@example.com');
});

test('admin can ban and unban a user', function () {
    $admin = adminUser();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.ban', $user), [
            'banned_reason' => 'Spam forum',
        ])
        ->assertRedirect();

    expect($user->fresh())
        ->banned_at->not->toBeNull()
        ->banned_reason->toBe('Spam forum')
        ->banned_by->toBe($admin->id);

    $this->actingAs($admin)
        ->patch(route('admin.users.unban', $user))
        ->assertRedirect();

    expect($user->fresh())
        ->banned_at->toBeNull()
        ->banned_reason->toBeNull()
        ->banned_by->toBeNull();
});

test('banned users cannot login', function () {
    adminUser();
    $user = User::factory()->create([
        'email' => 'banned@example.com',
        'password' => Hash::make('password'),
        'banned_at' => now(),
    ]);

    config()->set('services.recaptcha.enabled', false);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('admin can delete a user while preserving forum messages under deleted user placeholder', function () {
    $admin = adminUser();
    $user = User::factory()->create([
        'email' => 'delete-keep-forum@example.com',
    ]);
    $thread = moderatedForumThread($user);
    $postId = $thread->posts()->first()->id;

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user), [
            'delete_forum_messages' => false,
        ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseMissing('users', ['email' => 'delete-keep-forum@example.com']);
    $placeholder = User::where('email', 'deleted-user@vinyls-collection.local')->first();
    expect($placeholder)->not->toBeNull();

    $this->assertDatabaseHas('forum_threads', [
        'id' => $thread->id,
        'author_id' => $placeholder->id,
    ]);
    $this->assertDatabaseHas('forum_posts', [
        'id' => $postId,
        'author_id' => $placeholder->id,
    ]);
});

test('admin can delete a user and their forum messages', function () {
    $admin = adminUser();
    $user = User::factory()->create([
        'email' => 'delete-with-forum@example.com',
    ]);
    $otherUser = User::factory()->create();
    $thread = moderatedForumThread($user);
    $postId = $thread->posts()->first()->id;
    $reply = Post::create([
        'thread_id' => $thread->id,
        'author_id' => $otherUser->id,
        'content' => 'Réponse dans un thread supprimé avec son auteur',
        'sequence' => 2,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user), [
            'delete_forum_messages' => true,
        ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseMissing('users', ['email' => 'delete-with-forum@example.com']);
    $this->assertDatabaseMissing('forum_threads', ['id' => $thread->id]);
    $this->assertDatabaseMissing('forum_posts', ['id' => $postId]);
    $this->assertDatabaseMissing('forum_posts', ['id' => $reply->id]);
});

test('admin cannot moderate their own account', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->patch(route('admin.users.ban', $admin), [
            'banned_reason' => 'Self ban',
        ])
        ->assertStatus(422);

    expect($admin->fresh()->banned_at)->toBeNull();
});
