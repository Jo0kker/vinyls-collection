<?php

namespace App\Http\Controllers;

use App\Models\ForumPost;
use App\Models\ForumThread;
use App\Models\ThreadSubscription;
use App\Notifications\NewForumPostNotification;
use App\Services\RecaptchaVerifier;
use Illuminate\Http\Request;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Models\Thread;
use Throwable;

class ForumPostController extends Controller
{
    public function store(Request $request, RecaptchaVerifier $recaptcha, $thread_id)
    {
        $thread = Thread::findOrFail($thread_id);

        $request->validate([
            'content' => 'required|string',
            'recaptcha_token' => $recaptcha->enabled() ? ['required', 'string'] : ['nullable', 'string'],
        ]);

        if (! $recaptcha->verify($request->string('recaptcha_token')->toString(), 'forum_post_reply', $request->ip())) {
            return back()
                ->withErrors(['recaptcha_token' => 'La vérification anti-spam a échoué. Merci de réessayer.'])
                ->withInput($request->except('recaptcha_token'));
        }

        $lastPost = Post::where('thread_id', $thread->id)
            ->orderBy('sequence', 'desc')
            ->first();

        $newPost = Post::create([
            'thread_id' => $thread->id,
            'author_id' => auth()->id(),
            'content' => $request->content,
            'sequence' => $lastPost ? $lastPost->sequence + 1 : 1,
        ]);

        // Update thread's reply count and last post
        $thread->increment('reply_count');
        $thread->update([
            'last_post_id' => $newPost->id,
        ]);

        // Update thread's updated_at timestamp
        $thread->touch();

        // Notify subscribers (except the post author)
        $this->notifySubscribers($thread, $newPost);

        $this->subscribeUserToThreadNotifications(auth()->id(), $thread->id);

        return back();
    }

    /**
     * Notify all subscribers of a new post
     */
    private function notifySubscribers($thread, $post)
    {
        // Get all subscribers except the post author
        $subscriptions = ThreadSubscription::where('thread_id', $thread->id)
            ->where('user_id', '!=', $post->author_id)
            ->where('email_notifications', true)
            ->with('user')
            ->get();

        // Convert to ForumThread and ForumPost for the notification
        $forumThread = ForumThread::find($thread->id);
        $forumPost = ForumPost::find($post->id);

        if (! $forumThread || ! $forumPost) {
            return;
        }

        foreach ($subscriptions as $subscription) {
            if (! $subscription->user) {
                continue;
            }

            try {
                $subscription->user->notify(new NewForumPostNotification($forumThread, $forumPost));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function subscribeUserToThreadNotifications(?int $userId, int $threadId): void
    {
        if (! $userId) {
            return;
        }

        ThreadSubscription::firstOrCreate([
            'user_id' => $userId,
            'thread_id' => $threadId,
        ], [
            'email_notifications' => true,
        ]);
    }

    public function edit($post_id)
    {
        $post = Post::findOrFail($post_id);

        // Check permissions
        $user = auth()->user();
        if (! $user || ! $this->canEditPost($user, $post)) {
            abort(403, 'Unauthorized');
        }

        return response()->json([
            'id' => $post->id,
            'content' => $post->content,
        ]);
    }

    public function update(Request $request, $post_id)
    {
        $post = Post::findOrFail($post_id);

        // Check permissions
        $user = auth()->user();
        if (! $user || ! $this->canEditPost($user, $post)) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'content' => 'required|string',
        ]);

        $post->update([
            'content' => $request->content,
        ]);

        return redirect()->back()->with('success', 'Post modifié avec succès.');
    }

    public function destroy($post_id)
    {
        $post = Post::findOrFail($post_id);

        // Check permissions
        $user = auth()->user();
        if (! $user || (! $this->canDeletePost($user, $post))) {
            abort(403, 'Unauthorized');
        }

        $thread = $post->thread;

        // Vérifier si c'est le premier post du thread (sequence = 1)
        $isFirstPost = $post->sequence === 1;

        if ($isFirstPost) {
            // Si c'est le premier post, supprimer tout le thread
            // Cela supprimera en cascade tous les posts du thread
            $thread->delete();

            // Rediriger vers la catégorie du thread
            return redirect()->route('forum.category.show', $thread->category_id)
                ->with('success', 'Discussion supprimée avec succès.');
        }

        // Sinon, supprimer seulement le post
        $post->delete(); // Soft delete

        // Mettre à jour le last_post_id du thread si nécessaire
        if ($thread && $thread->last_post_id == $post_id) {
            $lastActivePost = Post::where('thread_id', $thread->id)
                ->whereNull('deleted_at')  // Exclure les posts supprimés
                ->orderBy('created_at', 'desc')
                ->first();

            $thread->update([
                'last_post_id' => $lastActivePost ? $lastActivePost->id : null,
            ]);
        }

        // Décrémenter le compteur de réponses
        $thread->decrement('reply_count');

        return redirect()->back()->with('success', 'Post supprimé avec succès.');
    }

    public function restore($post_id)
    {
        $post = Post::withTrashed()->findOrFail($post_id);

        // Check permissions
        $user = auth()->user();
        if (! $user || ! $this->canRestorePost($user, $post)) {
            abort(403, 'Unauthorized');
        }

        $thread = $post->thread;
        $post->restore();

        // Mettre à jour le last_post_id du thread si ce post est plus récent
        if ($thread) {
            $lastActivePost = Post::where('thread_id', $thread->id)
                ->whereNull('deleted_at')  // Exclure les posts supprimés
                ->orderBy('created_at', 'desc')
                ->first();

            if ($lastActivePost && $lastActivePost->id == $post->id) {
                $thread->update([
                    'last_post_id' => $post->id,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Post restauré avec succès.');
    }

    public function forceDestroy($post_id)
    {
        $post = Post::withTrashed()->findOrFail($post_id);

        // Check permissions
        $user = auth()->user();
        if (! $user || ! $this->canForceDeletePost($user, $post)) {
            abort(403, 'Unauthorized');
        }

        $post->forceDelete();

        return redirect()->back()->with('success', 'Post supprimé définitivement.');
    }

    public function bulk(Request $request)
    {
        $request->validate([
            'posts' => 'required|array',
            'posts.*' => 'integer|exists:forum_posts,id',
            'action' => 'required|in:delete,restore',
            'force' => 'sometimes|boolean',
        ]);

        $user = auth()->user();
        if (! $user) {
            abort(403, 'Unauthorized');
        }

        $posts = Post::withTrashed()->whereIn('id', $request->posts)->get();

        foreach ($posts as $post) {
            if ($request->action === 'delete') {
                if ($this->canDeletePost($user, $post)) {
                    if ($request->boolean('force') && $this->canForceDeletePost($user, $post)) {
                        $post->forceDelete();
                    } else {
                        $post->delete();
                    }
                }
            } elseif ($request->action === 'restore') {
                if ($this->canRestorePost($user, $post)) {
                    $post->restore();
                }
            }
        }

        $message = $request->action === 'delete'
            ? ($request->boolean('force') ? 'Posts supprimés définitivement.' : 'Posts supprimés.')
            : 'Posts restaurés.';

        return redirect()->back()->with('success', $message);
    }

    private function canDeletePost($user, $post)
    {
        // Author can delete their own post
        if ($post->author_id === $user->id) {
            return true;
        }

        // Moderators and admins can delete any post
        return $user->roles()->whereIn('name', ['admin', 'moderator'])->exists();
    }

    private function canRestorePost($user, $post)
    {
        // Only moderators and admins can restore posts
        return $user->roles()->whereIn('name', ['admin', 'moderator'])->exists();
    }

    private function canEditPost($user, $post)
    {
        // Author can edit their own post within 24 hours
        if ($post->author_id === $user->id && $post->created_at->diffInHours(now()) <= 24) {
            return true;
        }

        // Moderators and admins can edit any post
        return $user->roles()->whereIn('name', ['admin', 'moderator'])->exists();
    }

    private function canForceDeletePost($user, $post)
    {
        // Only admins can force delete posts
        return $user->roles()->where('name', 'admin')->exists();
    }
}
