<?php

namespace App\Traits;

use Inertia\Inertia;

trait SeoMetaTags
{
    protected function shareMetaTags(array $meta = []): void
    {
        $defaultMeta = [
            'title' => 'Vinyls Collection - Gérez votre collection',
            'description' => 'Plateforme communautaire pour gérer, partager et discuter de votre collection de vinyles. Forum actif et outils complets.',
            'keywords' => 'vinyles, collection, musique, forum, partage, disques, discographie',
            'canonical' => request()->url(),
            'image' => asset('images/og-default.jpg'),
            'type' => 'website',
        ];
        
        $meta = array_merge($defaultMeta, $meta);
        
        Inertia::share([
            'meta' => $meta,
            'seo' => [
                'title' => $meta['title'],
                'description' => $meta['description'],
                'keywords' => $meta['keywords'],
                'canonical' => $meta['canonical'],
                'og:title' => $meta['title'],
                'og:description' => $meta['description'],
                'og:image' => $meta['image'],
                'og:url' => $meta['canonical'],
                'og:type' => $meta['type'],
                'og:site_name' => config('app.name'),
                'twitter:card' => 'summary_large_image',
                'twitter:title' => $meta['title'],
                'twitter:description' => $meta['description'],
                'twitter:image' => $meta['image'],
            ]
        ]);
    }
    
    protected function getCollectionMetaTags($collection): array
    {
        $vinylCount = $collection->vinylCollections->count() ?? 0;
        $description = $vinylCount > 0 ? 
            "Collection de {$collection->user->name} : {$vinylCount} vinyles à découvrir." :
            "Collection de vinyles de {$collection->user->name}.";
            
        return [
            'title' => "Collection {$collection->user->name} - Vinyls Collection",
            'description' => substr($description, 0, 150),
            'canonical' => route('collections.show', $collection),
            'type' => 'profile',
        ];
    }
    
    protected function getThreadMetaTags($thread): array
    {
        $description = strip_tags($thread->first_post->content ?? '');
        $description = substr($description, 0, 140);
        
        return [
            'title' => substr($thread->title, 0, 45) . ' - Forum Vinyls',
            'description' => $description ?: substr("Discussion : {$thread->title}", 0, 140),
            'canonical' => route('forum.thread.show', $thread),
            'type' => 'article',
        ];
    }
    
    protected function getCategoryMetaTags($category): array
    {
        $description = $category->description ?: "Discussions {$category->title} - Forum Vinyls Collection";
        
        return [
            'title' => substr($category->title, 0, 40) . ' - Forum Vinyls',
            'description' => substr($description, 0, 140),
            'canonical' => route('forum.category.show', $category),
            'type' => 'website',
        ];
    }
}