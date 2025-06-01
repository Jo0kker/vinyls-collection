<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User;
use TeamTeaTime\Forum\Models\Thread;

class ThreadPolicy
{
    public function view(User $user, Thread $thread): bool
    {
        return true;
    }

    public function rename(User $user, Thread $thread): bool
    {
        return $user->getKey() === $thread->author_id || $user->hasPermissionTo('manage threads');
    }

    public function reply(User $user, Thread $thread): bool
    {
        return !$thread->locked;
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
