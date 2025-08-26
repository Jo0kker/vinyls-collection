<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TeamTeaTime\Forum\Models\Thread;
use TeamTeaTime\Forum\Models\Post;

class ForumPostController extends Controller
{
    public function store(Request $request, $thread_id)
    {
        $thread = Thread::findOrFail($thread_id);
        
        $request->validate([
            'content' => 'required|string'
        ]);

        $lastPost = Post::where('thread_id', $thread->id)
            ->orderBy('sequence', 'desc')
            ->first();

        $newPost = Post::create([
            'thread_id' => $thread->id,
            'author_id' => auth()->id(),
            'content' => $request->content,
            'sequence' => $lastPost ? $lastPost->sequence + 1 : 1
        ]);

        // Update thread's reply count and last post
        $thread->increment('reply_count');
        $thread->update([
            'last_post_id' => $newPost->id,
        ]);
        
        // Update thread's updated_at timestamp
        $thread->touch();

        return back();
    }

    public function edit($post_id)
    {
        $post = Post::findOrFail($post_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canEditPost($user, $post)) {
            abort(403, 'Unauthorized');
        }

        return response()->json([
            'id' => $post->id,
            'content' => $post->content
        ]);
    }

    public function update(Request $request, $post_id)
    {
        $post = Post::findOrFail($post_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canEditPost($user, $post)) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'content' => 'required|string'
        ]);

        $post->update([
            'content' => $request->content
        ]);

        return redirect()->back()->with('success', 'Post modifié avec succès.');
    }

    public function destroy($post_id)
    {
        $post = Post::findOrFail($post_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || (!$this->canDeletePost($user, $post))) {
            abort(403, 'Unauthorized');
        }

        $thread = $post->thread;
        $post->delete(); // Soft delete

        // Mettre à jour le last_post_id du thread si nécessaire
        if ($thread && $thread->last_post_id == $post_id) {
            $lastActivePost = Post::where('thread_id', $thread->id)
                ->orderBy('created_at', 'desc')
                ->first();
            
            $thread->update([
                'last_post_id' => $lastActivePost ? $lastActivePost->id : null
            ]);
        }

        return redirect()->back()->with('success', 'Post supprimé avec succès.');
    }

    public function restore($post_id)
    {
        $post = Post::withTrashed()->findOrFail($post_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canRestorePost($user, $post)) {
            abort(403, 'Unauthorized');
        }

        $thread = $post->thread;
        $post->restore();

        // Mettre à jour le last_post_id du thread si ce post est plus récent
        if ($thread) {
            $lastActivePost = Post::where('thread_id', $thread->id)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($lastActivePost && $lastActivePost->id == $post->id) {
                $thread->update([
                    'last_post_id' => $post->id
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
        if (!$user || !$this->canForceDeletePost($user, $post)) {
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
            'force' => 'sometimes|boolean'
        ]);

        $user = auth()->user();
        if (!$user) {
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