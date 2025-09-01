<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Models\Collection;
use App\Models\User;
use App\Models\Vinyl;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Thread;
use Carbon\Carbon;

class SitemapController extends Controller
{
    /**
     * Generate and return the sitemap XML dynamically
     */
    public function index(): Response
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>';
        $content .= "\n";
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $content .= "\n";
        
        // Pages statiques principales
        $this->addStaticPages($content);
        
        // Pages du forum
        $this->addForumPages($content);
        
        // Profils des collectionneurs
        $this->addUserProfiles($content);
        
        // Collections
        $this->addCollections($content);
        
        // Vinyles
        $this->addVinyls($content);
        
        $content .= '</urlset>';

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Generate robots.txt dynamically
     */
    public function robots(): Response
    {
        $content = "User-agent: *\nDisallow:\n\nSitemap: " . config('app.url') . '/sitemap.xml';

        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }

    private function addUrl(&$content, $loc, $lastmod = null)
    {
        $content .= '  <url>';
        $content .= "\n";
        $content .= '    <loc>' . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . '</loc>';
        $content .= "\n";
        
        if ($lastmod) {
            $lastmodFormatted = $lastmod instanceof Carbon ? $lastmod->format('Y-m-d') : Carbon::parse($lastmod)->format('Y-m-d');
            $content .= '    <lastmod>' . $lastmodFormatted . '</lastmod>';
            $content .= "\n";
        }
        
        $content .= '  </url>';
        $content .= "\n";
    }

    private function addStaticPages(&$content)
    {
        // Page d'accueil
        $this->addUrl($content, config('app.url'), Carbon::now());
        
        // Dashboard
        $this->addUrl($content, config('app.url') . '/dashboard', Carbon::now()->subDays(7));
        
        // Page des collectionneurs
        $this->addUrl($content, config('app.url') . '/collectors', Carbon::now()->subDays(1));
        
        // Page principale du forum
        $this->addUrl($content, config('app.url') . '/forum', Carbon::now()->subHours(1));
    }

    private function addForumPages(&$content)
    {
        try {
            // Catégories du forum (seulement les publiques)
            $categories = Category::where('is_private', false)
                ->orderBy('updated_at', 'desc')
                ->get();
                
            foreach ($categories as $category) {
                if ($category->accepts_threads) {
                    $url = config('app.url') . '/forum/c/' . $category->slug . '-' . $category->id;
                    $this->addUrl($content, $url, $category->updated_at);
                }
            }
            
            // Threads du forum (limité aux 1000 plus récents, seulement les publics)
            $threads = Thread::whereNull('deleted_at')
                ->where('is_private', false)
                ->whereHas('category', function ($query) {
                    $query->where('is_private', false);
                })
                ->orderBy('updated_at', 'desc')
                ->limit(1000)
                ->get();
                
            foreach ($threads as $thread) {
                $url = config('app.url') . '/forum/t/' . $thread->slug . '-' . $thread->id;
                $this->addUrl($content, $url, $thread->updated_at);
            }
        } catch (\Exception $e) {
            // Si erreur avec le forum, on continue sans
        }
    }

    private function addUserProfiles(&$content)
    {
        try {
            // Profils des utilisateurs ayant des collections
            $users = User::has('collections')
                ->whereNotNull('email_verified_at')
                ->orderBy('updated_at', 'desc')
                ->limit(500)
                ->get();
                
            foreach ($users as $user) {
                $url = config('app.url') . '/collectors/' . $user->id;
                $this->addUrl($content, $url, $user->updated_at);
            }
        } catch (\Exception $e) {
            // Si erreur, on continue
        }
    }

    private function addCollections(&$content)
    {
        try {
            // Collections publiques (visibility = 'public')
            $collections = Collection::with('user')
                ->where('visibility', 'public')
                ->orderBy('collection_date_modif', 'desc')
                ->limit(1000)
                ->get();
                
            foreach ($collections as $collection) {
                if ($collection->user) {
                    $url = config('app.url') . '/collectors/' . $collection->user_id . '/collections/' . $collection->id;
                    $lastmod = $collection->collection_date_modif ? 
                        Carbon::parse($collection->collection_date_modif) : 
                        $collection->updated_at;
                    $this->addUrl($content, $url, $lastmod);
                }
            }
        } catch (\Exception $e) {
            // Si erreur, on continue
        }
    }

    private function addVinyls(&$content)
    {
        try {
            // Vinyles (limité aux 2000 plus récents avec un nom)
            $vinyls = Vinyl::whereNotNull('vinyl_nom')
                ->where('vinyl_nom', '!=', '')
                ->orderBy('updated_at', 'desc')
                ->limit(2000)
                ->get();
                
            foreach ($vinyls as $vinyl) {
                $url = config('app.url') . '/vinyles/' . $vinyl->id;
                $this->addUrl($content, $url, $vinyl->updated_at);
            }
        } catch (\Exception $e) {
            // Si erreur, on continue
        }
    }
}