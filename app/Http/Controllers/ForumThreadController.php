<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Thread;
use TeamTeaTime\Forum\Models\Post;
use App\Models\ThreadSubscription;
use App\Models\ForumThread;

class ForumThreadController extends Controller
{
    public function show($thread_id)
    {
        /**
         * @var Thread $thread
         */
        $thread = Thread::withTrashed()->with('category', 'author')->findOrFail($thread_id);
        
        // Les utilisateurs non connectés ne voient que les posts non supprimés
        // Les modérateurs/admins voient tous les posts (y compris supprimés)
        $postsQuery = Post::where('thread_id', $thread->id)
            ->with(['author.roles'])
            ->orderBy('created_at', 'asc');
            
        // Si l'utilisateur est modérateur/admin, inclure les posts supprimés
        if (auth()->check() && auth()->user()->roles()->whereIn('name', ['admin', 'moderator'])->exists()) {
            $postsQuery = $postsQuery->withTrashed();
        }
        
        $posts = $postsQuery->paginate(20);
        
        // Optimisation : récupérer les counts en une seule requête pour tous les auteurs
        $authorIds = $posts->getCollection()->pluck('author.id')->filter()->unique();
        $vinylCounts = \DB::table('collection_vinyls')
            ->join('collections', 'collection_vinyls.collection_id', '=', 'collections.id')
            ->whereIn('collections.user_id', $authorIds)
            ->select('collections.user_id', \DB::raw('count(*) as vinyl_count'))
            ->groupBy('collections.user_id')
            ->pluck('vinyl_count', 'user_id');
            
        // Transform posts data to include user information
        $posts->getCollection()->transform(function ($post) use ($vinylCounts) {
            // Add vinyl count for the author (from cache)
            if ($post->author) {
                $post->author->vinyl_count = $vinylCounts[$post->author->id] ?? 0;
            }
            
            return $post;
        });

        // Marquer le thread comme lu pour l'utilisateur connecté
        if (auth()->check()) {
            $thread->markAsRead(auth()->user());
        }

        // Get all categories for move operation
        $allCategories = Category::where('accepts_threads', true)
            ->where('id', '!=', $thread->category_id)
            ->get(['id', 'title']);
        
        // Check if user is subscribed
        $isSubscribed = false;
        if (auth()->check()) {
            $isSubscribed = ThreadSubscription::where('user_id', auth()->id())
                ->where('thread_id', $thread->id)
                ->exists();
        }

        $data = [
            'thread' => [
                'id' => $thread->id,
                'title' => $thread->title,
                'locked' => $thread->locked,
                'pinned' => $thread->pinned,
                'deleted_at' => $thread->deleted_at,
                'author' => [
                    'id' => $thread->author->id,
                    'name' => $thread->author->name,
                ],
                'category' => $thread->category ? [
                    'id' => $thread->category->id,
                    'title' => $thread->category->title,
                    'color_light_mode' => $thread->category->color_light_mode,
                ] : null,
                'created_at' => $thread->created_at,
                'reply_count' => $thread->reply_count,
                'is_subscribed' => $isSubscribed,
            ],
            'posts' => $posts,
            'categories' => $allCategories
        ];
        
        return Inertia::render('Forum/Thread/Show', $data)->with([
            'auth' => [
                'user' => auth()->user() ? [
                    'id' => auth()->user()->id,
                    'name' => auth()->user()->name,
                    'roles' => auth()->user()->roles->map(fn($role) => [
                        'name' => $role->name,
                        'color' => $role->color ?? '#3b82f6'
                    ])
                ] : null
            ]
        ]);
    }

    public function create($category_id)
    {
        $category = Category::findOrFail($category_id);

        return Inertia::render('Forum/Thread/Create', [
            'category' => [
                'id' => $category->id,
                'title' => $category->title,
                'color_light_mode' => $category->color_light_mode,
            ]
        ]);
    }

    public function store(Request $request, $category_id)
    {
        $category = Category::findOrFail($category_id);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string'
        ]);

        $thread = Thread::create([
            'category_id' => $category->id,
            'author_id' => auth()->id(),
            'title' => $request->title,
        ]);

        $post = Post::create([
            'thread_id' => $thread->id,
            'author_id' => auth()->id(),
            'content' => $request->get('content'),
            'sequence' => 1
        ]);

        // Update thread with first post id
        $thread->update([
            'first_post_id' => $post->id,
            'last_post_id' => $post->id,
        ]);

        return redirect()->route('forum.thread.show', $thread->id);
    }

    public function lock($thread_id)
    {
        $thread = Thread::findOrFail($thread_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canModerateThread($user, $thread)) {
            abort(403, 'Unauthorized');
        }

        $thread->update(['locked' => true]);

        return redirect()->back()->with('success', 'Thread verrouillé avec succès.');
    }

    public function unlock($thread_id)
    {
        $thread = Thread::findOrFail($thread_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canModerateThread($user, $thread)) {
            abort(403, 'Unauthorized');
        }

        $thread->update(['locked' => false]);

        return redirect()->back()->with('success', 'Thread déverrouillé avec succès.');
    }

    public function pin($thread_id)
    {
        $thread = Thread::findOrFail($thread_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canModerateThread($user, $thread)) {
            abort(403, 'Unauthorized');
        }

        $thread->update(['pinned' => true]);

        return redirect()->back()->with('success', 'Thread épinglé avec succès.');
    }

    public function unpin($thread_id)
    {
        $thread = Thread::findOrFail($thread_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canModerateThread($user, $thread)) {
            abort(403, 'Unauthorized');
        }

        $thread->update(['pinned' => false]);

        return redirect()->back()->with('success', 'Thread désépinglé avec succès.');
    }

    public function rename(Request $request, $thread_id)
    {
        $thread = Thread::findOrFail($thread_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canModerateThread($user, $thread)) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $thread->update(['title' => $request->title]);

        return redirect()->back()->with('success', 'Thread renommé avec succès.');
    }

    public function move(Request $request, $thread_id)
    {
        $thread = Thread::findOrFail($thread_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canModerateThread($user, $thread)) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'category_id' => 'required|integer|exists:forum_categories,id'
        ]);

        $thread->update(['category_id' => $request->category_id]);

        return redirect()->back()->with('success', 'Thread déplacé avec succès.');
    }

    public function destroy($thread_id)
    {
        $thread = Thread::findOrFail($thread_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canDeleteThread($user, $thread)) {
            abort(403, 'Unauthorized');
        }

        $thread->delete(); // Soft delete

        if ($thread->category_id) {
            return redirect()->route('forum.category.show', $thread->category_id)
                            ->with('success', 'Thread supprimé avec succès.');
        }
        
        return redirect()->route('forum.index')
                        ->with('success', 'Thread supprimé avec succès.');
    }

    public function restore($thread_id)
    {
        $thread = Thread::withTrashed()->findOrFail($thread_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canRestoreThread($user, $thread)) {
            abort(403, 'Unauthorized');
        }

        $thread->restore();

        return redirect()->back()->with('success', 'Thread restauré avec succès.');
    }

    public function forceDestroy($thread_id)
    {
        $thread = Thread::withTrashed()->findOrFail($thread_id);
        
        // Check permissions
        $user = auth()->user();
        if (!$user || !$this->canForceDeleteThread($user, $thread)) {
            abort(403, 'Unauthorized');
        }

        $thread->forceDelete();

        if ($thread->category_id) {
            return redirect()->route('forum.category.show', $thread->category_id)
                            ->with('success', 'Thread supprimé définitivement.');
        }
        
        return redirect()->route('forum.index')
                        ->with('success', 'Thread supprimé définitivement.');
    }

    private function canModerateThread($user, $thread)
    {
        // Moderators and admins can moderate threads
        return $user->roles()->whereIn('name', ['admin', 'moderator'])->exists();
    }

    private function canDeleteThread($user, $thread)
    {
        // Author can delete their own thread or moderators/admins can delete any thread
        return ($thread->author_id === $user->id) || 
               $user->roles()->whereIn('name', ['admin', 'moderator'])->exists();
    }

    private function canRestoreThread($user, $thread)
    {
        // Only moderators and admins can restore threads
        return $user->roles()->whereIn('name', ['admin', 'moderator'])->exists();
    }

    private function canForceDeleteThread($user, $thread)
    {
        // Only admins can force delete threads
        return $user->roles()->where('name', 'admin')->exists();
    }

    /**
     * Subscribe to a thread
     */
    public function subscribe($thread_id)
    {
        $thread = Thread::findOrFail($thread_id);
        $user = auth()->user();

        // Check if already subscribed
        $existingSubscription = ThreadSubscription::where('user_id', $user->id)
            ->where('thread_id', $thread->id)
            ->first();

        if ($existingSubscription) {
            return redirect()->back()->with('info', 'Vous suivez déjà cette discussion.');
        }

        ThreadSubscription::create([
            'user_id' => $user->id,
            'thread_id' => $thread->id,
            'email_notifications' => true,
        ]);

        return redirect()->back()->with('success', 'Vous suivez maintenant cette discussion.');
    }

    /**
     * Unsubscribe from a thread
     */
    public function unsubscribe($thread_id)
    {
        $thread = Thread::findOrFail($thread_id);
        $user = auth()->user();

        $subscription = ThreadSubscription::where('user_id', $user->id)
            ->where('thread_id', $thread->id)
            ->first();

        if (!$subscription) {
            return redirect()->back()->with('info', 'Vous ne suiviez pas cette discussion.');
        }

        $subscription->delete();

        return redirect()->back()->with('success', 'Vous ne suivez plus cette discussion.');
    }
}
