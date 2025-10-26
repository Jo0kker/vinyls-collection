<?php

namespace App\Observers;

use App\Models\Collection;
use App\Models\User;

class CollectionObserver
{
    /**
     * Handle the Collection "created" event.
     */
    public function created(Collection $collection): void
    {
        // Incrémenter le compteur si la collection est publique
        if ($collection->visibility === 'public') {
            User::where('id', $collection->user_id)->increment('public_collections_count');
        }
    }

    /**
     * Handle the Collection "updated" event.
     */
    public function updated(Collection $collection): void
    {
        // Vérifier si la visibilité a changé
        if ($collection->isDirty('visibility')) {
            $oldVisibility = $collection->getOriginal('visibility');
            $newVisibility = $collection->visibility;

            if ($oldVisibility !== 'public' && $newVisibility === 'public') {
                // Devenue publique : incrémenter
                User::where('id', $collection->user_id)->increment('public_collections_count');
            } elseif ($oldVisibility === 'public' && $newVisibility !== 'public') {
                // N'est plus publique : décrémenter
                User::where('id', $collection->user_id)->decrement('public_collections_count');
            }
        }
    }

    /**
     * Handle the Collection "deleted" event.
     */
    public function deleted(Collection $collection): void
    {
        // Décrémenter le compteur si la collection était publique
        if ($collection->visibility === 'public') {
            User::where('id', $collection->user_id)->decrement('public_collections_count');
        }
    }

    /**
     * Handle the Collection "restored" event.
     */
    public function restored(Collection $collection): void
    {
        // Incrémenter le compteur si la collection restaurée est publique
        if ($collection->visibility === 'public') {
            User::where('id', $collection->user_id)->increment('public_collections_count');
        }
    }

    /**
     * Handle the Collection "force deleted" event.
     */
    public function forceDeleted(Collection $collection): void
    {
        // Décrémenter le compteur si la collection était publique
        if ($collection->visibility === 'public') {
            User::where('id', $collection->user_id)->decrement('public_collections_count');
        }
    }
}
