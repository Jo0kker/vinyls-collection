<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User;
use TeamTeaTime\Forum\Models\Post;

class PostPolicy
{
    public function view(?User $user, Post $post): bool
    {
        // Les posts supprimés ne sont visibles que par les modérateurs/admins
        if ($post->deleted_at && (!$user || !$user->roles()->whereIn('name', ['admin', 'moderator'])->exists())) {
            return false;
        }
        
        // Sinon tout le monde peut voir les posts (même non connectés)
        return true;
    }

    public function edit(User $user, Post $post): bool
    {
        return $user->getKey() === $post->author_id || $user->hasPermissionTo('manage posts');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->getKey() === $post->author_id || $user->hasPermissionTo('manage posts');
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->hasPermissionTo('restore posts');
    }
}
