<?php

namespace App\Http\Controllers\Forum;

use App\Http\Controllers\Controller;
use App\Models\Forum\Thread;
use App\Models\Forum\Category;
use App\Models\User;
use App\Models\Vinyl;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $sitemaps = [
            route('sitemap.main'),
            route('sitemap.forum'),
            route('sitemap.threads'),
            route('sitemap.profiles'),
            route('sitemap.vinyls'),
        ];

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        foreach ($sitemaps as $sitemap) {
            $content .= '<sitemap>';
            $content .= '<loc>' . $sitemap . '</loc>';
            $content .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
            $content .= '</sitemap>';
        }
        
        $content .= '</sitemapindex>';

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }

    public function forum(): Response
    {
        $categories = Category::where('is_private', false)
            ->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        // Page d'accueil du forum
        $content .= '<url>';
        $content .= '<loc>' . route('forum.index') . '</loc>';
        $content .= '<changefreq>daily</changefreq>';
        $content .= '<priority>1.0</priority>';
        $content .= '</url>';
        
        // Pages importantes du forum
        $content .= '<url>';
        $content .= '<loc>' . route('forum.recent') . '</loc>';
        $content .= '<changefreq>daily</changefreq>';
        $content .= '<priority>0.9</priority>';
        $content .= '</url>';
        
        // Catégories
        foreach ($categories as $category) {
            if ($category->accepts_threads) {
                $content .= '<url>';
                $content .= '<loc>' . route('forum.category.show', $category->id) . '</loc>';
                $content .= '<lastmod>' . $category->updated_at->toAtomString() . '</lastmod>';
                $content .= '<changefreq>weekly</changefreq>';
                $content .= '<priority>0.8</priority>';
                $content .= '</url>';
            }
        }
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }

    public function threads(): Response
    {
        $threads = Thread::whereNull('deleted_at')
            ->where('is_private', false)
            ->whereHas('category', function ($query) {
                $query->where('is_private', false);
            })
            ->latest()
            ->take(5000) // Limiter pour éviter des sitemaps trop lourds
            ->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        foreach ($threads as $thread) {
            $content .= '<url>';
            $content .= '<loc>' . route('forum.thread.show', $thread->id) . '</loc>';
            $content .= '<lastmod>' . $thread->updated_at->toAtomString() . '</lastmod>';
            $content .= '<changefreq>weekly</changefreq>';
            $content .= '<priority>0.6</priority>';
            $content .= '</url>';
        }
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }

    public function main(): Response
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        // Page d'accueil
        $content .= '<url>';
        $content .= '<loc>' . route('welcome') . '</loc>';
        $content .= '<changefreq>daily</changefreq>';
        $content .= '<priority>1.0</priority>';
        $content .= '<lastmod>' . now()->toAtomString() . '</lastmod>';
        $content .= '</url>';
        
        // Page des collectionneurs
        $content .= '<url>';
        $content .= '<loc>' . route('profiles.index') . '</loc>';
        $content .= '<changefreq>weekly</changefreq>';
        $content .= '<priority>0.8</priority>';
        $content .= '</url>';
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }

    public function profiles(): Response
    {
        $users = User::where('email_verified_at', '!=', null)
            ->latest()
            ->take(1000)
            ->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        foreach ($users as $user) {
            $content .= '<url>';
            $content .= '<loc>' . route('profiles.show', $user->id) . '</loc>';
            $content .= '<lastmod>' . $user->updated_at->toAtomString() . '</lastmod>';
            $content .= '<changefreq>monthly</changefreq>';
            $content .= '<priority>0.5</priority>';
            $content .= '</url>';
        }
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }

    public function vinyls(): Response
    {
        $vinyls = Vinyl::whereNotNull('vinyl_nom')
            ->latest()
            ->take(5000)
            ->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        foreach ($vinyls as $vinyl) {
            $content .= '<url>';
            $content .= '<loc>' . route('vinyl.show', $vinyl->id) . '</loc>';
            $content .= '<lastmod>' . $vinyl->updated_at->toAtomString() . '</lastmod>';
            $content .= '<changefreq>monthly</changefreq>';
            $content .= '<priority>0.6</priority>';
            $content .= '</url>';
        }
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }
}