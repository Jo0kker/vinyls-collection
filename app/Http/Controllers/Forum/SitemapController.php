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
        $content .= '\n';
        $content .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $content .= '\n';
        
        foreach ($sitemaps as $sitemap) {
            $content .= '  <sitemap>';
            $content .= '\n';
            $content .= '    <loc>' . htmlspecialchars($sitemap, ENT_XML1, 'UTF-8') . '</loc>';
            $content .= '\n';
            $content .= '    <lastmod>' . now()->format('Y-m-d') . '</lastmod>';
            $content .= '\n';
            $content .= '  </sitemap>';
            $content .= '\n';
        }
        
        $content .= '</sitemapindex>';

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    public function forum(): Response
    {
        $categories = Category::where('is_private', false)
            ->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '\n';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $content .= '\n';
        
        // Page d'accueil du forum
        $content .= '  <url>';
        $content .= '\n';
        $content .= '    <loc>' . htmlspecialchars(route('forum.index'), ENT_XML1, 'UTF-8') . '</loc>';
        $content .= '\n';
        $content .= '    <lastmod>' . now()->format('Y-m-d') . '</lastmod>';
        $content .= '\n';
        $content .= '  </url>';
        $content .= '\n';
        
        // Pages importantes du forum
        $content .= '  <url>';
        $content .= '\n';
        $content .= '    <loc>' . htmlspecialchars(route('forum.recent'), ENT_XML1, 'UTF-8') . '</loc>';
        $content .= '\n';
        $content .= '    <lastmod>' . now()->format('Y-m-d') . '</lastmod>';
        $content .= '\n';
        $content .= '  </url>';
        $content .= '\n';
        
        // Catégories
        foreach ($categories as $category) {
            if ($category->accepts_threads) {
                $content .= '  <url>';
                $content .= '\n';
                $content .= '    <loc>' . htmlspecialchars(route('forum.category.show', $category->id), ENT_XML1, 'UTF-8') . '</loc>';
                $content .= '\n';
                $content .= '    <lastmod>' . $category->updated_at->format('Y-m-d') . '</lastmod>';
                $content .= '\n';
                $content .= '  </url>';
                $content .= '\n';
            }
        }
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
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
        $content .= '\n';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $content .= '\n';
        
        foreach ($threads as $thread) {
            $content .= '  <url>';
            $content .= '\n';
            $content .= '    <loc>' . htmlspecialchars(route('forum.thread.show', $thread->id), ENT_XML1, 'UTF-8') . '</loc>';
            $content .= '\n';
            $content .= '    <lastmod>' . $thread->updated_at->format('Y-m-d') . '</lastmod>';
            $content .= '\n';
            $content .= '  </url>';
            $content .= '\n';
        }
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    public function main(): Response
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '\n';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $content .= '\n';
        
        // Page d'accueil
        $content .= '  <url>';
        $content .= '\n';
        $content .= '    <loc>' . htmlspecialchars(config('app.url'), ENT_XML1, 'UTF-8') . '</loc>';
        $content .= '\n';
        $content .= '    <lastmod>' . now()->format('Y-m-d') . '</lastmod>';
        $content .= '\n';
        $content .= '  </url>';
        $content .= '\n';
        
        // Page des collectionneurs
        $content .= '  <url>';
        $content .= '\n';
        $content .= '    <loc>' . htmlspecialchars(route('collectors.index'), ENT_XML1, 'UTF-8') . '</loc>';
        $content .= '\n';
        $content .= '    <lastmod>' . now()->format('Y-m-d') . '</lastmod>';
        $content .= '\n';
        $content .= '  </url>';
        $content .= '\n';
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    public function profiles(): Response
    {
        $users = User::where('email_verified_at', '!=', null)
            ->latest()
            ->take(1000)
            ->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '\n';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $content .= '\n';
        
        foreach ($users as $user) {
            $content .= '  <url>';
            $content .= '\n';
            $content .= '    <loc>' . htmlspecialchars(route('collectors.show', $user->id), ENT_XML1, 'UTF-8') . '</loc>';
            $content .= '\n';
            $content .= '    <lastmod>' . $user->updated_at->format('Y-m-d') . '</lastmod>';
            $content .= '\n';
            $content .= '  </url>';
            $content .= '\n';
        }
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    public function vinyls(): Response
    {
        $vinyls = Vinyl::whereNotNull('vinyl_nom')
            ->latest()
            ->take(5000)
            ->get();

        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= '\n';
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $content .= '\n';
        
        foreach ($vinyls as $vinyl) {
            $content .= '  <url>';
            $content .= '\n';
            $content .= '    <loc>' . htmlspecialchars(route('vinyl.show', $vinyl->id), ENT_XML1, 'UTF-8') . '</loc>';
            $content .= '\n';
            $content .= '    <lastmod>' . $vinyl->updated_at->format('Y-m-d') . '</lastmod>';
            $content .= '\n';
            $content .= '  </url>';
            $content .= '\n';
        }
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}