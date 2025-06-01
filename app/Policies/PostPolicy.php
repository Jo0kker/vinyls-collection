<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User;
use TeamTeaTime\Forum\Models\Post;

class PostPolicy
{
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
