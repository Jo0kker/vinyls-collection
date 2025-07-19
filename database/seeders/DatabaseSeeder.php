<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🎵 Seeding Vinyls Collection Database...');

        // L'ordre est important à cause des dépendances
        $this->call([
            UserSeeder::class,      // Créer les utilisateurs en premier
            VinylSeeder::class,     // Créer les vinyles
            CollectionSeeder::class, // Créer les collections et les liens avec les vinyles
            ForumSeeder::class,     // Créer le forum avec catégories, threads et posts
        ]);

        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('🔐 Comptes de test créés :');
        $this->command->info('   Admin: admin@vinyls-collection.com / password');
        $this->command->info('   User:  user@vinyls-collection.com / password');
        $this->command->info('');
        $this->command->info('📊 Données générées :');
        $this->command->info('   - 32 utilisateurs');
        $this->command->info('   - 150 vinyles');
        $this->command->info('   - Collections complètes');
        $this->command->info('   - Forum avec catégories et discussions');
    }
}
