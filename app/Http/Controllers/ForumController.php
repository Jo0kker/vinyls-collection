<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Thread;

class ForumController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')
            ->withCount('threads')
            ->with(['children' => function($query) {
                $query->withCount('threads')->orderBy('_lft');
            }])
            ->orderBy('_lft')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'description' => $category->description,
                    'color_light_mode' => $category->color_light_mode,
                    'color_dark_mode' => $category->color_dark_mode,
                    'accepts_threads' => $category->accepts_threads,
                    'threads_count' => $category->threads_count,
                    'posts_count' => $category->post_count,
                    'children' => $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'title' => $child->title,
                            'description' => $child->description,
                            'color_light_mode' => $child->color_light_mode,
                            'color_dark_mode' => $child->color_dark_mode,
                            'threads_count' => $child->threads_count,
                            'posts_count' => $child->post_count,
                            'accepts_threads' => $child->accepts_threads,
                        ];
                    })
                ];
            });

        return Inertia::render('Forum/Index', [
            'categories' => $categories
        ]);
    }

    public function recent()
    {
        $threads = Thread::with(['author', 'category', 'lastPost.author'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        // Transform threads data similar to category controller
        $threads->getCollection()->transform(function ($thread) {
            return [
                'id' => $thread->id,
                'title' => $thread->title,
                'reply_count' => $thread->reply_count,
                'locked' => $thread->locked,
                'pinned' => $thread->pinned,
                'created_at' => $thread->created_at,
                'updated_at' => $thread->updated_at,
                'author' => [
                    'id' => $thread->author->id,
                    'name' => $thread->author->name,
                ],
                'category' => $thread->category ? [
                    'id' => $thread->category->id,
                    'title' => $thread->category->title,
                    'color_light_mode' => $thread->category->color_light_mode,
                ] : null,
                'lastPost' => $thread->lastPost ? [
                    'id' => $thread->lastPost->id,
                    'author' => [
                        'id' => $thread->lastPost->author->id,
                        'name' => $thread->lastPost->author->name,
                    ],
                    'created_at' => $thread->lastPost->created_at,
                ] : null,
            ];
        });

        return Inertia::render('Forum/Recent', [
            'threads' => $threads
        ]);
    }

    public function unread()
    {
        $user = auth()->user();
        
        // Récupérer les threads non lus avec une requête optimisée
        $threads = Thread::with(['author', 'category', 'lastPost.author'])
            ->leftJoin('forum_threads_read', function ($join) use ($user) {
                $join->on('forum_threads.id', '=', 'forum_threads_read.thread_id')
                     ->where('forum_threads_read.user_id', $user->id);
            })
            ->where(function ($query) {
                // Threads jamais lus
                $query->whereNull('forum_threads_read.thread_id')
                      // OU threads mis à jour après la dernière lecture
                      ->orWhereColumn('forum_threads.updated_at', '>', 'forum_threads_read.updated_at');
            })
            ->select('forum_threads.*')
            ->orderBy('forum_threads.updated_at', 'desc')
            ->paginate(20);

        // Transform threads data similar to category controller
        $threads->getCollection()->transform(function ($thread) {
            return [
                'id' => $thread->id,
                'title' => $thread->title,
                'reply_count' => $thread->reply_count,
                'locked' => $thread->locked,
                'pinned' => $thread->pinned,
                'created_at' => $thread->created_at,
                'updated_at' => $thread->updated_at,
                'author' => [
                    'id' => $thread->author->id,
                    'name' => $thread->author->name,
                ],
                'category' => $thread->category ? [
                    'id' => $thread->category->id,
                    'title' => $thread->category->title,
                    'color_light_mode' => $thread->category->color_light_mode,
                ] : null,
                'lastPost' => $thread->lastPost ? [
                    'id' => $thread->lastPost->id,
                    'author' => [
                        'id' => $thread->lastPost->author->id,
                        'name' => $thread->lastPost->author->name,
                    ],
                    'created_at' => $thread->lastPost->created_at,
                ] : null,
            ];
        });

        return Inertia::render('Forum/Unread', [
            'threads' => $threads
        ]);
    }

    public function markAllAsRead()
    {
        $user = auth()->user();
        
        // Récupérer tous les threads non lus
        $unreadThreads = Thread::leftJoin('forum_threads_read', function ($join) use ($user) {
                $join->on('forum_threads.id', '=', 'forum_threads_read.thread_id')
                     ->where('forum_threads_read.user_id', $user->id);
            })
            ->where(function ($query) {
                $query->whereNull('forum_threads_read.thread_id')
                      ->orWhereColumn('forum_threads.updated_at', '>', 'forum_threads_read.updated_at');
            })
            ->select('forum_threads.*')
            ->get();

        // Marquer tous les threads comme lus
        foreach ($unreadThreads as $thread) {
            $thread->markAsRead($user);
        }

        return redirect()->route('forum.unread')->with('success', 'Tous les messages ont été marqués comme lus.');
    }

    public function myThreads()
    {
        $threads = Thread::with(['author', 'category', 'lastPost.author'])
            ->where('author_id', auth()->id())
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        // Transform threads data similar to category controller
        $threads->getCollection()->transform(function ($thread) {
            return [
                'id' => $thread->id,
                'title' => $thread->title,
                'reply_count' => $thread->reply_count,
                'locked' => $thread->locked,
                'pinned' => $thread->pinned,
                'created_at' => $thread->created_at,
                'updated_at' => $thread->updated_at,
                'author' => [
                    'id' => $thread->author->id,
                    'name' => $thread->author->name,
                ],
                'category' => $thread->category ? [
                    'id' => $thread->category->id,
                    'title' => $thread->category->title,
                    'color_light_mode' => $thread->category->color_light_mode,
                ] : null,
                'lastPost' => $thread->lastPost ? [
                    'id' => $thread->lastPost->id,
                    'author' => [
                        'id' => $thread->lastPost->author->id,
                        'name' => $thread->lastPost->author->name,
                    ],
                    'created_at' => $thread->lastPost->created_at,
                ] : null,
            ];
        });

        return Inertia::render('Forum/MyThreads', [
            'threads' => $threads
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $threads = collect();

        if ($query) {
            $threads = Thread::with(['author', 'category', 'lastPost.author'])
                ->where('title', 'LIKE', "%{$query}%")
                ->orderBy('updated_at', 'desc')
                ->paginate(20);

            // Transform threads data similar to category controller
            $threads->getCollection()->transform(function ($thread) {
                return [
                    'id' => $thread->id,
                    'title' => $thread->title,
                    'reply_count' => $thread->reply_count,
                    'locked' => $thread->locked,
                    'pinned' => $thread->pinned,
                    'created_at' => $thread->created_at,
                    'updated_at' => $thread->updated_at,
                    'author' => [
                        'id' => $thread->author->id,
                        'name' => $thread->author->name,
                    ],
                    'category' => $thread->category ? [
                        'id' => $thread->category->id,
                        'title' => $thread->category->title,
                        'color_light_mode' => $thread->category->color_light_mode,
                    ] : null,
                    'lastPost' => $thread->lastPost ? [
                        'id' => $thread->lastPost->id,
                        'author' => [
                            'id' => $thread->lastPost->author->id,
                            'name' => $thread->lastPost->author->name,
                        ],
                        'created_at' => $thread->lastPost->created_at,
                    ] : null,
                ];
            });
        }

        return Inertia::render('Forum/Search', [
            'threads' => $threads,
            'query' => $query
        ]);
    }
}