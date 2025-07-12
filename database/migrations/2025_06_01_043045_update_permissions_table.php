<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Permissions de base pour tous les utilisateurs
        $permissions = [
            // Threads
            'create threads',
            'edit own threads',
            'delete own threads',
            'reply to threads',
            
            // Posts
            'create posts',
            'edit own posts',
            'delete own posts',
            
            // Permissions modérateur
            'manage threads', // Gérer tous les threads
            'manage posts', // Gérer tous les posts
            'lock threads',
            'pin threads',
            'move threads',
            'restore threads',
            'restore posts',
            
            // Permissions admin
            'manage categories', // Gérer les catégories
            'create categories',
            'edit categories',
            'delete categories',
            'move categories',
        ];

        // Création des permissions
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Création du rôle modérateur
        $moderator = Role::create(['name' => 'moderator']);
        $moderator->givePermissionTo([
            'manage threads',
            'manage posts',
            'lock threads',
            'pin threads',
            'move threads',
            'restore threads',
            'restore posts',
        ]);

        // Création du rôle admin
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // Création du rôle utilisateur normal
        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            'create threads',
            'edit own threads',
            'delete own threads',
            'reply to threads',
            'create posts',
            'edit own posts',
            'delete own posts',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Suppression des rôles
        Role::whereIn('name', ['moderator', 'admin', 'user'])->delete();
        
        // Suppression des permissions
        Permission::whereIn('name', [
            'create threads',
            'edit own threads',
            'delete own threads',
            'reply to threads',
            'create posts',
            'edit own posts',
            'delete own posts',
            'manage threads',
            'manage posts',
            'lock threads',
            'pin threads',
            'move threads',
            'restore threads',
            'restore posts',
            'manage categories',
            'create categories',
            'edit categories',
            'delete categories',
            'move categories',
        ])->delete();
    }
};
