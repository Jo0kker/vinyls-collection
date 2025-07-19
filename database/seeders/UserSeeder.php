<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Créer ou mettre à jour l'utilisateur admin de test
        $admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'first_name' => 'John',
                'last_name' => 'Doe',
                'bio' => 'Administrateur du site, passionné de vinyles depuis plus de 20 ans.',
                'tagline' => 'Collectionneur depuis toujours',
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        // Créer ou mettre à jour l'utilisateur de test normal
        $user = User::updateOrCreate(
            ['email' => 'user@test.com'],
            [
                'name' => 'VinylLover',
                'password' => Hash::make('password'),
                'first_name' => 'Alice',
                'last_name' => 'Martin',
                'bio' => 'Amoureuse de la musique vintage et des sons analogiques.',
                'tagline' => 'La musique avant tout',
                'email_verified_at' => now(),
            ]
        );
        $user->syncRoles(['user']);

        // Créer des utilisateurs aléatoires avec des profils variés
        $musicProfiles = [
            [
                'bio' => 'Collectionneur de jazz et de blues, à la recherche des perles rares.',
                'tagline' => 'Le jazz, c\'est la vie'
            ],
            [
                'bio' => 'Fan de rock progressif et de musique psychédélique des années 70.',
                'tagline' => 'Progressive rock forever'
            ],
            [
                'bio' => 'Passionné de punk et de new wave, toujours en quête de nouveautés.',
                'tagline' => 'No future, no surrender'
            ],
            [
                'bio' => 'Amateur de soul et de funk, collectionneur d\'imports américains.',
                'tagline' => 'Keep on groovin\''
            ],
            [
                'bio' => 'Mélomane éclectique, de la musique classique à l\'électronique.',
                'tagline' => 'Toutes les musiques sont bonnes'
            ],
            [
                'bio' => 'Spécialiste des musiques du monde, collector d\'éditions rares.',
                'tagline' => 'World music explorer'
            ],
            [
                'bio' => 'Fan de heavy metal et de hard rock, chasseur de bootlegs.',
                'tagline' => 'Rock \'n\' roll will never die'
            ],
            [
                'bio' => 'Collectionneur de reggae et de dub, passionné de culture jamaïcaine.',
                'tagline' => 'One love, one heart'
            ],
            [
                'bio' => 'Amateur de musique française, des variétés aux chansonniers.',
                'tagline' => 'La chanson française éternelle'
            ],
            [
                'bio' => 'Chasseur de vinyles rares en brocantes et vide-greniers.',
                'tagline' => 'Toujours à l\'affût'
            ]
        ];

        // Assigner des rôles aux utilisateurs existants qui n'en ont pas
        $usersWithoutRoles = User::doesntHave('roles')->get();

        if ($usersWithoutRoles->count() > 0) {
            $this->command->info("Attribution des rôles à {$usersWithoutRoles->count()} utilisateurs...");

            // Assigner quelques modérateurs (les 3 premiers)
            $moderators = $usersWithoutRoles->take(3);
            foreach ($moderators as $moderator) {
                $moderator->assignRole('moderator');
            }

            // Le reste devient des utilisateurs normaux
            $normalUsers = $usersWithoutRoles->skip(3);
            foreach ($normalUsers as $normalUser) {
                $normalUser->assignRole('user');
            }
        }

        // Créer quelques utilisateurs supplémentaires si on en a moins de 30 au total
        $currentUserCount = User::count();
        $targetCount = 32; // admin + user + 30 autres

        if ($currentUserCount < $targetCount) {
            $toCreate = $targetCount - $currentUserCount;
            $this->command->info("Création de {$toCreate} utilisateurs supplémentaires...");

            $newUsers = User::factory($toCreate)->create([
                'email_verified_at' => now(),
            ]);

            foreach ($newUsers as $newUser) {
                $newUser->assignRole('user');
            }
        }

        $adminCount = User::role('admin')->count();
        $moderatorCount = User::role('moderator')->count();
        $userCount = User::role('user')->count();

        $this->command->info('Utilisateurs créés avec succès !');
        $this->command->info('- Admin: admin@vinyls-collection.com / password');
        $this->command->info('- User: user@vinyls-collection.com / password');
        $this->command->info("- {$adminCount} administrateur(s)");
        $this->command->info("- {$moderatorCount} modérateur(s)");
        $this->command->info("- {$userCount} utilisateur(s) normaux");
    }
}
