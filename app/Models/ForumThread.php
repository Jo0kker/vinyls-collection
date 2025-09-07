<?php

namespace App\Models;

use TeamTeaTime\Forum\Models\Thread as BaseThread;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ForumThread extends BaseThread
{
    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();
        
        // Quand on supprime un thread, supprimer tous ses posts
        static::deleting(function ($thread) {
            // Si c'est une soft delete
            if (!$thread->isForceDeleting()) {
                // Soft delete tous les posts associés
                $thread->posts()->delete();
            } else {
                // Force delete tous les posts associés
                $thread->posts()->forceDelete();
            }
        });
        
        // Quand on restaure un thread, restaurer tous ses posts
        static::restoring(function ($thread) {
            $thread->posts()->withTrashed()->restore();
        });
    }
    
    /**
     * Relationship: The last post in the thread (excluding soft-deleted posts).
     */
    public function lastPost(): HasOne
    {
        return $this->hasOne(ForumPost::class, 'id', 'last_post_id')->whereNull('deleted_at');
    }

    /**
     * Update the last post ID to the latest non-deleted post
     */
    public function updateLastPost(): void
    {
        $lastActivePost = $this->posts()
            ->orderBy('created_at', 'desc')
            ->first();

        $this->update([
            'last_post_id' => $lastActivePost ? $lastActivePost->id : null
        ]);
    }
}
