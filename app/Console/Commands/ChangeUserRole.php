<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class ChangeUserRole extends Command
{
    protected $signature = 'user:change-role {email} {role}';
    protected $description = 'Change le rôle d\'un utilisateur (moderator ou admin)';

    public function handle()
    {
        $email = $this->argument('email');
        $roleName = strtolower($this->argument('role'));

        // Vérifier si le rôle existe
        if (!in_array($roleName, ['moderator', 'admin'])) {
            $this->error('Le rôle doit être "moderator" ou "admin"');
            return 1;
        }

        // Trouver l'utilisateur
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("Aucun utilisateur trouvé avec l'email: {$email}");
            return 1;
        }

        // Trouver le rôle
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            $this->error("Le rôle {$roleName} n'existe pas");
            return 1;
        }

        // Supprimer tous les rôles existants
        $user->roles()->detach();

        // Attribuer le nouveau rôle
        $user->assignRole($role);

        $this->info("Le rôle {$roleName} a été attribué à {$email} avec succès");
        return 0;
    }
} 