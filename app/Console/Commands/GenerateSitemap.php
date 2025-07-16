<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Collection;
use App\Models\User;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Thread;
use TeamTeaTime\Forum\Models\Post;
use Carbon\Carbon;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate {--output=public/sitemap.xml : Output file path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate XML sitemap for SEO optimization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sitemap...');

        $sitemap = new \DOMDocument('1.0', 'UTF-8');
        $sitemap->formatOutput = true;
        
        $urlset = $sitemap->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $urlset->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
        $sitemap->appendChild($urlset);

        // Add homepage
        $this->addUrl($sitemap, $urlset, config('app.url'), now(), 'weekly', '1.0');
        
        // Add dashboard
        $this->addUrl($sitemap, $urlset, config('app.url') . '/dashboard', now(), 'weekly', '0.8');
        
        // Add forum pages
        $this->addForumUrls($sitemap, $urlset);
        
        // Add collection pages
        $this->addCollectionUrls($sitemap, $urlset);
        
        // Save sitemap
        $outputPath = $this->option('output');
        $content = $sitemap->saveXML();
        
        if (str_starts_with($outputPath, 'public/')) {
            file_put_contents(base_path($outputPath), $content);
        } else {
            Storage::disk('public')->put($outputPath, $content);
        }
        
        $this->info("Sitemap generated successfully at: {$outputPath}");
        $this->info('Total URLs: ' . $urlset->childNodes->length);
        
        return 0;
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
        $this->info('Adding forum URLs...');
        
        // Forum index
        $this->addUrl($sitemap, $urlset, config('app.url') . '/forum', now(), 'daily', '0.9');
        
        // Categories
        Category::whereNull('parent_id')->get()->each(function ($category) use ($sitemap, $urlset) {
            $url = str_starts_with($category->route, 'http') ? $category->route : config('app.url') . $category->route;
            $this->addUrl($sitemap, $urlset, $url, $category->updated_at, 'weekly', '0.7');
        });
        
        // Recent threads (limit to most recent 1000)
        Thread::whereNull('deleted_at')
            ->orderBy('updated_at', 'desc')
            ->limit(1000)
            ->get()
            ->each(function ($thread) use ($sitemap, $urlset) {
                $url = str_starts_with($thread->route, 'http') ? $thread->route : config('app.url') . $thread->route;
                $this->addUrl($sitemap, $urlset, $url, $thread->updated_at, 'weekly', '0.6');
            });
    }
    
    private function addCollectionUrls($sitemap, $urlset)
    {
        $this->info('Adding collection URLs...');
        
        // Collections (all for now, we'll add public filter later)
        Collection::with('user')
            ->orderBy('collection_date_modif', 'desc')
            ->limit(1000)
            ->get()
            ->each(function ($collection) use ($sitemap, $urlset) {
                if ($collection->user && $collection->user->name) {
                    $lastmod = $collection->collection_date_modif ? Carbon::parse($collection->collection_date_modif) : $collection->updated_at;
                    $url = config('app.url') . '/collections/' . $collection->id;
                    $this->addUrl($sitemap, $urlset, $url, $lastmod, 'monthly', '0.5');
                }
            });
    }
}
