<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-password {email} {--password= : Nouveau mot de passe (auto-généré si non fourni)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Réinitialise le mot de passe d\'un utilisateur via son email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->option('password');

        // Trouver l'utilisateur
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Aucun utilisateur trouvé avec l'email : {$email}");
            return 1;
        }

        // Générer un mot de passe si non fourni
        if (!$password) {
            $password = Str::random(12);
            $this->info("Mot de passe auto-généré : {$password}");
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($password)
        ]);

        $this->info("Mot de passe mis à jour pour l'utilisateur : {$user->name} ({$user->email})");
        
        if ($this->option('password')) {
            $this->comment("Le mot de passe fourni a été appliqué.");
        } else {
            $this->comment("IMPORTANT: Notez le mot de passe auto-généré ci-dessus !");
        }

        return 0;
    }
}
