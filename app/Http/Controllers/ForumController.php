<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        // Transform threads data similar to category controller
        $threads->getCollection()->transform(function ($thread) {
            return [
                'id' => $thread->id,
                'title' => $thread->title,
                'reply_count' => $thread->reply_count,
                'visible_posts_count' => $this->getVisiblePostsCount($thread),
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
            ->whereNull('forum_threads.deleted_at')
            ->select('forum_threads.*')
            ->orderBy('forum_threads.updated_at', 'desc')
            ->paginate(20);

        // Transform threads data similar to category controller
        $threads->getCollection()->transform(function ($thread) {
            return [
                'id' => $thread->id,
                'title' => $thread->title,
                'reply_count' => $thread->reply_count,
                'visible_posts_count' => $this->getVisiblePostsCount($thread),
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
            ->whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        // Transform threads data similar to category controller
        $threads->getCollection()->transform(function ($thread) {
            return [
                'id' => $thread->id,
                'title' => $thread->title,
                'reply_count' => $thread->reply_count,
                'visible_posts_count' => $this->getVisiblePostsCount($thread),
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
            // Sanitize and prepare query for PostgreSQL full-text search
            $searchTerms = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
            $searchTerms = preg_split('/\s+/', trim($searchTerms), -1, PREG_SPLIT_NO_EMPTY);

            if (empty($searchTerms)) {
                return Inertia::render('Forum/Search', [
                    'threads' => $threads,
                    'query' => $query
                ]);
            }

            // Build tsquery with prefix matching for partial words
            $tsQuery = implode(' & ', array_map(fn($term) => $term . ':*', $searchTerms));

            // Search in thread titles and post content using full-text search
            // Priority: title matches rank higher than content matches
            $threads = Thread::with(['author', 'category', 'lastPost.author'])
                ->select('forum_threads.*')
                ->selectRaw("
                    GREATEST(
                        ts_rank(forum_threads.search_vector, plainto_tsquery('french', ?)) * 2,
                        COALESCE((
                            SELECT MAX(ts_rank(fp.search_vector, plainto_tsquery('french', ?)))
                            FROM forum_posts fp
                            WHERE fp.thread_id = forum_threads.id
                            AND fp.deleted_at IS NULL
                        ), 0)
                    ) as search_rank
                ", [$query, $query])
                ->selectRaw("
                    (
                        SELECT fp.content
                        FROM forum_posts fp
                        WHERE fp.thread_id = forum_threads.id
                        AND fp.deleted_at IS NULL
                        AND fp.search_vector @@ to_tsquery('french', ?)
                        ORDER BY ts_rank(fp.search_vector, to_tsquery('french', ?)) DESC
                        LIMIT 1
                    ) as matched_content
                ", [$tsQuery, $tsQuery])
                ->selectRaw("
                    (
                        SELECT fp.id
                        FROM forum_posts fp
                        WHERE fp.thread_id = forum_threads.id
                        AND fp.deleted_at IS NULL
                        AND fp.search_vector @@ to_tsquery('french', ?)
                        ORDER BY ts_rank(fp.search_vector, to_tsquery('french', ?)) DESC
                        LIMIT 1
                    ) as matched_post_id
                ", [$tsQuery, $tsQuery])
                ->selectRaw("
                    (
                        SELECT fp.sequence
                        FROM forum_posts fp
                        WHERE fp.thread_id = forum_threads.id
                        AND fp.deleted_at IS NULL
                        AND fp.search_vector @@ to_tsquery('french', ?)
                        ORDER BY ts_rank(fp.search_vector, to_tsquery('french', ?)) DESC
                        LIMIT 1
                    ) as matched_post_sequence
                ", [$tsQuery, $tsQuery])
                ->where(function ($q) use ($tsQuery) {
                    $q->whereRaw("forum_threads.search_vector @@ to_tsquery('french', ?)", [$tsQuery])
                      ->orWhereExists(function ($subQuery) use ($tsQuery) {
                          $subQuery->select(\DB::raw(1))
                              ->from('forum_posts')
                              ->whereColumn('forum_posts.thread_id', 'forum_threads.id')
                              ->whereNull('forum_posts.deleted_at')
                              ->whereRaw("forum_posts.search_vector @@ to_tsquery('french', ?)", [$tsQuery]);
                      });
                })
                ->whereNull('forum_threads.deleted_at')
                ->orderByDesc('search_rank')
                ->orderByDesc('forum_threads.updated_at')
                ->paginate(20);

            // Transform threads data
            $threads->getCollection()->transform(function ($thread) use ($query) {
                $excerpt = null;
                $matchedPost = null;

                if ($thread->matched_content) {
                    // Create excerpt around matched term
                    $content = strip_tags($thread->matched_content);
                    $excerpt = $this->createSearchExcerpt($content, $query, 150);
                }

                if ($thread->matched_post_id) {
                    $matchedPost = [
                        'id' => $thread->matched_post_id,
                        'sequence' => $thread->matched_post_sequence,
                        'page' => $this->getPostPage($thread->id, $thread->matched_post_id),
                    ];
                }

                return [
                    'id' => $thread->id,
                    'title' => $thread->title,
                    'reply_count' => $thread->reply_count,
                    'visible_posts_count' => $this->getVisiblePostsCount($thread),
                    'locked' => $thread->locked,
                    'pinned' => $thread->pinned,
                    'created_at' => $thread->created_at,
                    'updated_at' => $thread->updated_at,
                    'excerpt' => $excerpt,
                    'matched_post' => $matchedPost,
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

    /**
     * Check if current user can see trashed posts (admin/moderator)
     */
    private function canSeeTrashedPosts(): bool
    {
        return auth()->check() && auth()->user()->roles()->whereIn('name', ['admin', 'moderator'])->exists();
    }

    /**
     * Get the count of visible posts for a thread based on user permissions
     */
    private function getVisiblePostsCount($thread): int
    {
        $query = DB::table('forum_posts')->where('thread_id', $thread->id);

        if (!$this->canSeeTrashedPosts()) {
            $query->whereNull('deleted_at');
        }

        return $query->count();
    }

    /**
     * Calculate the page number for a post based on user permissions
     */
    private function getPostPage(int $threadId, int $postId, int $perPage = 20): int
    {
        $query = DB::table('forum_posts')
            ->where('thread_id', $threadId)
            ->where('id', '<=', $postId);

        if (!$this->canSeeTrashedPosts()) {
            $query->whereNull('deleted_at');
        }

        $position = $query->count();

        return (int) ceil($position / $perPage);
    }

    /**
     * Create an excerpt from content around the search terms
     */
    private function createSearchExcerpt(string $content, string $query, int $length = 150): string
    {
        $terms = preg_split('/\s+/', trim($query), -1, PREG_SPLIT_NO_EMPTY);
        $content = preg_replace('/\s+/', ' ', trim($content));

        // Find the first occurrence of any search term
        $position = 0;
        foreach ($terms as $term) {
            $pos = mb_stripos($content, $term);
            if ($pos !== false) {
                $position = $pos;
                break;
            }
        }

        // Calculate start position (center the match in the excerpt)
        $start = max(0, $position - ($length / 2));

        // Adjust to not cut words
        if ($start > 0) {
            $spacePos = mb_strpos($content, ' ', $start);
            if ($spacePos !== false && $spacePos < $start + 20) {
                $start = $spacePos + 1;
            }
        }

        $excerpt = mb_substr($content, $start, $length);

        // Clean up excerpt edges
        if ($start > 0) {
            $excerpt = '...' . $excerpt;
        }
        if (mb_strlen($content) > $start + $length) {
            // Try to end at a word boundary
            $lastSpace = mb_strrpos($excerpt, ' ');
            if ($lastSpace !== false && $lastSpace > $length - 30) {
                $excerpt = mb_substr($excerpt, 0, $lastSpace);
            }
            $excerpt .= '...';
        }

        return $excerpt;
    }
}