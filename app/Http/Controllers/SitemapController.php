<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Response;
use App\Models\Collection;
use App\Models\User;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Thread;
use Carbon\Carbon;

class SitemapController extends Controller
{
    /**
     * Generate and return the sitemap XML dynamically
     */
    public function index()
    {
        // Cache the sitemap for 1 hour for performance
        return cache()->remember('sitemap_xml', 3600, function () {
            return $this->generateSitemap();
        });
    }

    /**
     * Generate robots.txt dynamically
     */
    public function robots()
    {
        $content = "User-agent: *\nDisallow:\n\nSitemap: " . url('/sitemap.xml');
        
        return response($content, 200, [
            'Content-Type' => 'text/plain'
        ]);
    }

    private function generateSitemap()
    {
        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $sitemap->formatOutput = true;
        
        $urlset = $sitemap->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $urlset->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
        $sitemap->appendChild($urlset);

        // Add homepage
        $this->addUrl($sitemap, $urlset, url('/'), now(), 'weekly', '1.0');
        
        // Add dashboard
        $this->addUrl($sitemap, $urlset, route('dashboard'), now(), 'weekly', '0.8');
        
        // Add forum pages
        $this->addForumUrls($sitemap, $urlset);
        
        // Add collection pages
        $this->addCollectionUrls($sitemap, $urlset);
        
        $content = $sitemap->saveXML();
        
        return response($content, 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
    
    private function addUrl($sitemap, $urlset, $loc, $lastmod = null, $changefreq = 'monthly', $priority = '0.5')
    {
        $url = $sitemap->createElement('url');
        
        $locElement = $sitemap->createElement('loc', htmlspecialchars($loc));
        $url->appendChild($locElement);
        
        if ($lastmod) {
            $lastmodElement = $sitemap->createElement('lastmod', $lastmod->toISOString());
            $url->appendChild($lastmodElement);
        }
        
        $changefreqElement = $sitemap->createElement('changefreq', $changefreq);
        $url->appendChild($changefreqElement);
        
        $priorityElement = $sitemap->createElement('priority', $priority);
        $url->appendChild($priorityElement);
        
        $urlset->appendChild($url);
    }
    
    private function addForumUrls($sitemap, $urlset)
    {
        // Forum index
        $this->addUrl($sitemap, $urlset, route('forum.index'), now(), 'daily', '0.9');
        
        // Categories
        Category::whereNull('parent_id')->get()->each(function ($category) use ($sitemap, $urlset) {
            $this->addUrl($sitemap, $urlset, $category->route, $category->updated_at, 'weekly', '0.7');
        });
        
        // Recent threads (limit to most recent 1000)
        Thread::whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->limit(1000)
            ->get()
            ->each(function ($thread) use ($sitemap, $urlset) {
                $this->addUrl($sitemap, $urlset, $thread->route, $thread->updated_at, 'weekly', '0.6');
            });
    }
    
    private function addCollectionUrls($sitemap, $urlset)
    {
        // Collections (all for now, we'll add public filter later)
        Collection::with('user')
            ->orderBy('collection_date_modif', 'desc')
            ->limit(1000)
            ->get()
            ->each(function ($collection) use ($sitemap, $urlset) {
                if ($collection->user && $collection->user->name) {
                    $lastmod = $collection->collection_date_modif ? Carbon::parse($collection->collection_date_modif) : $collection->updated_at;
                    $this->addUrl($sitemap, $urlset, route('collections.show', $collection), $lastmod, 'monthly', '0.5');
                }
            });
    }
}