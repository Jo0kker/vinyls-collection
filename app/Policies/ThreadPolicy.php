<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User;
use TeamTeaTime\Forum\Models\Thread;

class ThreadPolicy
{
    public function view(User $user, Thread $thread): bool
    {
        // Charger la catégorie si elle n'est pas déjà chargée
        if (!$thread->relationLoaded('category')) {
            $thread->load('category');
        }
        
        // Si la catégorie est privée, seuls les administrateurs peuvent voir le thread
        if ($thread->category && $thread->category->is_private) {
            return $user->hasPermissionTo('manage categories');
        }
        
        // Pour les catégories publiques, tout le monde peut voir
        return true;
    }

    public function rename(User $user, Thread $thread): bool
    {
        return $user->getKey() === $thread->author_id || $user->hasPermissionTo('manage threads');
    }

    public function reply(User $user, Thread $thread): bool
    {
        // Si le thread est verrouillé, personne ne peut répondre
        if ($thread->locked) {
            return false;
        }
        
        // Charger la catégorie si elle n'est pas déjà chargée
        if (!$thread->relationLoaded('category')) {
            $thread->load('category');
        }
        
        // Si la catégorie est privée, seuls les administrateurs peuvent répondre
        if ($thread->category && $thread->category->is_private) {
            return $user->hasPermissionTo('manage categories');
        }
        
        // Pour les catégories publiques, vérifier les permissions de base
        return $user->hasPermissionTo('reply to threads');
    }

    public function delete(User $user, Thread $thread): bool
    {
        return $user->getKey() === $thread->author_id || $user->hasPermissionTo('manage threads');
    }

    public function restore(User $user, Thread $thread): bool
    {
        return $user->hasPermissionTo('restore threads');
    }

    public function deletePosts(User $user, Thread $thread): bool
    {
        return $user->hasPermissionTo('manage posts');
    }

    public function restorePosts(User $user, Thread $thread): bool
    {
        return $user->hasPermissionTo('restore posts');
    }
}
