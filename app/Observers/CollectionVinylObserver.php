<?php

namespace App\Observers;

use App\Models\CollectionVinyl;
use App\Models\User;

class CollectionVinylObserver
{
    /**
     * Handle the CollectionVinyl "created" event.
     */
    public function created(CollectionVinyl $collectionVinyl): void
    {
        // Incrémenter le compteur de vinyles de l'utilisateur
        User::where('id', $collectionVinyl->user_id)->increment('vinyl_count');
    }

    /**
     * Handle the CollectionVinyl "deleted" event.
     */
    public function deleted(CollectionVinyl $collectionVinyl): void
    {
        // Décrémenter le compteur de vinyles de l'utilisateur
        User::where('id', $collectionVinyl->user_id)->decrement('vinyl_count');
    }

    /**
     * Handle the CollectionVinyl "restored" event.
     */
    public function restored(CollectionVinyl $collectionVinyl): void
    {
        // Incrémenter le compteur si restauré
        User::where('id', $collectionVinyl->user_id)->increment('vinyl_count');
    }

    /**
     * Handle the CollectionVinyl "force deleted" event.
     */
    public function forceDeleted(CollectionVinyl $collectionVinyl): void
    {
        // Décrémenter le compteur si supprimé définitivement
        User::where('id', $collectionVinyl->user_id)->decrement('vinyl_count');
    }
}
