<?php

namespace App\Http\Controllers;

use App\Models\Vinyl;
use App\Models\CollectionVinyl;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\User;
use App\Models\Collection;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // Récupérer les derniers vinyles ajoutés (avec collection publique)
        $latestVinyls = Cache::remember('home_latest_vinyls', 300, function () {
            return CollectionVinyl::with(['vinyl', 'collection.user'])
                ->whereHas('collection', function ($query) {
                    $query->where('visibility', 'public');
                })
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get()
                ->map(function ($cv) {
                    return [
                        'id' => $cv->vinyl->id,
                        'title' => $cv->vinyl->vinyl_titre,
                        'artist' => $cv->vinyl->artiste,
                        'cover_image' => $cv->vinyl->pochette,
                        'year' => $cv->vinyl->annee,
                        'added_by' => [
                            'id' => $cv->collection->user->id,
                            'name' => $cv->collection->user->name,
                        ],
                        'added_at' => $cv->created_at,
                    ];
                });
        });

        // Récupérer les derniers threads du forum
        $latestThreads = Cache::remember('home_latest_threads', 300, function () {
            return ForumThread::with(['author', 'category'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($thread) {
                    return [
                        'id' => $thread->id,
                        'title' => $thread->title,
                        'author' => [
                            'id' => $thread->author->id,
                            'name' => $thread->author->name,
                        ],
                        'category' => $thread->category ? [
                            'id' => $thread->category->id,
                            'title' => $thread->category->title,
                            'color_light_mode' => $thread->category->color_light_mode,
                        ] : null,
                        'reply_count' => $thread->reply_count,
                        'created_at' => $thread->created_at,
                    ];
                });
        });

        // Récupérer les derniers posts du forum (réponses récentes)
        $latestPosts = Cache::remember('home_latest_posts', 300, function () {
            return ForumPost::with(['author', 'thread.category'])
                ->where('sequence', '>', 1) // Exclure les premiers posts (création de thread)
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get()
                ->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'sequence' => $post->sequence,
                        'thread' => [
                            'id' => $post->thread->id,
                            'title' => $post->thread->title,
                        ],
                        'author' => [
                            'id' => $post->author->id,
                            'name' => $post->author->name,
                        ],
                        'content' => \Str::limit(strip_tags(html_entity_decode($post->content)), 120),
                        'created_at' => $post->created_at,
                    ];
                });
        });

        // Stats globales
        $stats = Cache::remember('home_stats', 3600, function () {
            return [
                'totalVinyls' => Vinyl::count(),
                'totalUsers' => User::count(),
                'totalCollections' => Collection::where('visibility', 'public')->count(),
                'totalThreads' => ForumThread::count(),
            ];
        });

        return Inertia::render('Welcome', [
            'latestVinyls' => $latestVinyls,
            'latestThreads' => $latestThreads,
            'latestPosts' => $latestPosts,
            'stats' => $stats,
        ]);
    }
}
