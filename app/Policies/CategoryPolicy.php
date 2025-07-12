<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User;
use TeamTeaTime\Forum\Models\Category;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        // Si la catégorie n'est pas privée, tout le monde peut la voir
        if (!$category->is_private) {
            return true;
        }
        
        // Si la catégorie est privée, seuls les administrateurs peuvent la voir
        return $user->hasPermissionTo('manage categories');
    }

    public function edit(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('edit categories');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('delete categories');
    }

    public function createThreads(User $user, Category $category): bool
    {
        // Si la catégorie est privée, seuls les administrateurs peuvent créer des threads
        if ($category->is_private) {
            return $user->hasPermissionTo('manage categories');
        }
        
        // Pour les catégories publiques, vérifier si l'utilisateur a la permission de créer des threads
        return $user->hasPermissionTo('create threads');
    }

    public function manageThreads(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('manage threads');
    }

    public function deleteThreads(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('manage threads');
    }

    public function restoreThreads(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('restore threads');
    }

    public function moveThreadsFrom(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('move threads');
    }

    public function moveThreadsTo(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('move threads');
    }

    public function lockThreads(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('lock threads');
    }

    public function pinThreads(User $user, Category $category): bool
    {
        return $user->hasPermissionTo('pin threads');
    }

    public function markThreadsAsRead(User $user, Category $category): bool
    {
        return true;
    }
}
