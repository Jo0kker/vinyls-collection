<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class ImportLegacyUsersCommand extends Command
{
    protected $signature = 'import:legacy-users';
    protected $description = 'Importe les utilisateurs depuis l\'ancienne base de données';

    private function convertToUtf8($string)
    {
        return empty($string) ? null : mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    }

    private function processEmail($email, array &$seenEmails)
    {
        if (empty($email)) {
            return null;
        }

        if (isset($seenEmails[$email])) {
            $parts = explode('@', $email);
            if (count($parts) === 2) {
                $email = $parts[0] . '+' . $parts[1];
            }
        }

        $seenEmails[$email] = true;
        return $email;
    }

    private function resolveAvatarUrl($pseudo)
    {
        static $defaultImageBase64 = null;

        // Charger l'image par défaut une seule fois
        if ($defaultImageBase64 === null) {
            try {
                $defaultImagePath = storage_path('app/public/default_user.jpg');
                if (file_exists($defaultImagePath)) {
                    $defaultImageBase64 = base64_encode(file_get_contents($defaultImagePath));
                } else {
                    $this->warn('Image par défaut default_user.jpg non trouvée dans storage/app/public/');
                    $defaultImageBase64 = ''; // Fallback
                }
            } catch (\Exception $e) {
                $this->warn('Erreur lors du chargement de l\'image par défaut : ' . $e->getMessage());
                $defaultImageBase64 = '';
            }
        }

        $url = "https://vinyls-collection.com/images/users/{$pseudo}.jpg";

        try {
            $response = Http::timeout(5)->get($url);
            if (!$response->successful()) {
                return null;
            }

            $imageData = $response->body();
            $base64 = base64_encode($imageData);

            // Comparer avec l'image par défaut
            if ($base64 === $defaultImageBase64) {
                return null;
            }

            $avatarPath = 'avatars/' . Str::uuid() . '.jpg';
            Storage::disk('s3')->put($avatarPath, $imageData);

            return Storage::disk('s3')->url($avatarPath);

        } catch (\Exception $e) {
            $this->warn("Erreur avatar pour {$pseudo} : " . $e->getMessage());
            return null;
        }
    }

    public function handle()
    {
        $this->info('Début de l\'import des utilisateurs...');
        $startTime = microtime(true);

        $commonPassword = Str::random(32);
        $hashedPassword = Hash::make($commonPassword);
        $this->info("Mot de passe commun généré : $commonPassword");

        try {
            $users = DB::connection('mysql_old')
                ->table('membres')
                ->join('membres_infos_perso', 'membres.id_membre', '=', 'membres_infos_perso.id_membre')
                ->select([
                    'membres.id_membre',
                    'membres.pseudo',
                    'membres.email',
                    'membres.date_inscription',
                    'membres.date_derniere_connexion',
                    'membres.recevoir_nl',
                    'membres.etat_compte',
                    'membres.statut_membre',
                    'membres.idSecurite',
                    'membres_infos_perso.nom',
                    'membres_infos_perso.prenom',
                    'membres_infos_perso.adresse1',
                    'membres_infos_perso.adresse2',
                    'membres_infos_perso.code_postal',
                    'membres_infos_perso.ville',
                    'membres_infos_perso.pays',
                    'membres_infos_perso.numero_telephone',
                    'membres_infos_perso.nb_vinyls',
                    'membres_infos_perso.nb_collections',
                    'membres_infos_perso.date_naissance',
                    'membres_infos_perso.phrase',
                    'membres_infos_perso.nb_messages_forum',
                    'membres_infos_perso.equipAudio',
                    'membres_infos_perso.influences',
                    'membres_infos_perso.site',
                    'membres_infos_perso.presentation'
                ])
                ->where('etat_compte', '=', 1)
                ->get();

            $totalUsers = count($users);
            $this->info(sprintf('Trouvé %d utilisateurs avec des comptes actifs', $totalUsers));

            $bar = $this->output->createProgressBar($totalUsers);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% écoulé | %estimated:-6s% estimé | %memory:6s% | %message%');
            $bar->start();

            $usersToInsert = [];
            $seenEmails = [];
            $imported = 0;

            foreach ($users as $user) {
                $usersToInsert[] = [
                    'old_id' => $user->id_membre,
                    'name' => $this->convertToUtf8($user->pseudo),
                    'email' => $this->processEmail($user->email, $seenEmails),
                    'password' => $hashedPassword,
                    'created_at' => $user->date_inscription ?: now(),
                    'updated_at' => $user->date_derniere_connexion ?: now(),
                    'first_name' => $this->convertToUtf8($user->prenom),
                    'last_name' => $this->convertToUtf8($user->nom),
                    'bio' => $this->convertToUtf8($user->presentation),
                    'tagline' => $this->convertToUtf8($user->phrase),
                    'avatar' => $this->resolveAvatarUrl($user->pseudo),
                ];

                if (count($usersToInsert) >= 1000) {
                    DB::table('users')->insert($usersToInsert);
                    $usersToInsert = [];
                }

                $imported++;
                if ($imported % 1000 === 0 || $imported === $totalUsers) {
                    $elapsedTime = microtime(true) - $startTime;
                    $speed = $imported / $elapsedTime;
                    $remaining = $totalUsers - $imported;
                    $eta = $remaining / ($speed ?: 1);
                    $bar->setMessage(sprintf("Vitesse : %.1f/s | Restant : %s", $speed, gmdate("H:i:s", (int)$eta)));
                }
                $bar->advance();
            }

            if (!empty($usersToInsert)) {
                DB::table('users')->insert($usersToInsert);
            }

            $bar->finish();
            $this->line('');
            $this->info("\nImport terminé avec succès !");
            $this->info(sprintf("Temps total : %.2f secondes", microtime(true) - $startTime));
            $this->info(sprintf("Utilisateurs importés : %d", $imported));
        } catch (\Exception $e) {
            $this->error("Erreur lors de l'import : " . $e->getMessage());
            $this->error('Trace : ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
