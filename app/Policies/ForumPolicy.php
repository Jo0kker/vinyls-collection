<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User;

class ForumPolicy
{
    public function createCategories(User $user): bool
    {
        return $user->hasPermissionTo('create categories');
    }

    public function moveCategories(User $user): bool
    {
        return $user->hasPermissionTo('move categories');
    }

    public function editCategories(User $user): bool
    {
        return $user->hasPermissionTo('edit categories');
    }

    public function deleteCategories(User $user): bool
    {
        return $user->hasPermissionTo('delete categories');
    }

    public function markThreadsAsRead(User $user): bool
    {
        return true;
    }

    public function viewTrashedThreads(User $user): bool
    {
        return $user->hasPermissionTo('manage threads');
    }

    public function viewTrashedPosts(User $user): bool
    {
        return $user->hasPermissionTo('manage posts');
    }
}
