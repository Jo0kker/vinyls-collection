<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Vinyl;
use App\Models\Collection;
use App\Models\CollectionVinyl;

class ImportLegacyCollectionsVinylsCommand extends Command
{
    protected $signature = 'import:legacy-collections-vinyls';
    protected $description = 'Importe les vinyles, collections et liaisons depuis l\'ancienne base de données (sans images)';

    private $activeUserIds = [];
    private $userCache = [];
    private $vinylCache = [];
    private $collectionCache = [];

    private function convertToUtf8($string)
    {
        if (empty($string)) {
            return $string;
        }
        return mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    }

    private function getFormatId($format)
    {
        // Mapping des formats
        $formats = [
            'LP' => 3,
            '45T' => 1,
            'Maxi 45T' => 2,
            'EP' => 15,
            '2LP' => 4,
            '3LP' => 5,
            '4LP' => 6,
            '5LP' => 7,
            '6LP' => 8,
            '78T' => 9,
            'LP 25cm' => 17,
            'Maxi 33T' => 16,
            'CD' => 10,
            'K7' => 11,
            'DVD' => 12,
            'VHS' => 13,
            'Autre' => 14
        ];

        // Si c'est déjà un ID numérique, on le retourne
        if (is_numeric($format)) {
            return (int)$format;
        }

        // Sinon on cherche dans le mapping
        return $formats[$format] ?? 14; // 14 = Autre par défaut
    }

    private function preloadActiveUsers()
    {
        $this->info('Chargement des utilisateurs actifs...');

        // Récupérer tous les IDs des utilisateurs avec des comptes actifs
        $activeUsers = DB::connection('mysql_old')
            ->table('membres')
            ->where('etat_compte', 1)
            ->pluck('id_membre')
            ->toArray();

        $this->activeUserIds = array_flip($activeUsers);
        $this->info(sprintf('Trouvé %d utilisateurs actifs', count($this->activeUserIds)));

        // Précharger le cache des utilisateurs importés
        $importedUsers = User::whereNotNull('old_id')->pluck('id', 'old_id')->toArray();
        $this->userCache = $importedUsers;
        $this->info(sprintf('Trouvé %d utilisateurs déjà importés', count($this->userCache)));
    }

    private function getUserId($oldMembreId)
    {
        return $this->userCache[$oldMembreId] ?? null;
    }

    private function getVinylId($oldVinylId)
    {
        if (!isset($this->vinylCache[$oldVinylId])) {
            $vinyl = Vinyl::where('old_id', $oldVinylId)->first();
            $this->vinylCache[$oldVinylId] = $vinyl ? $vinyl->id : null;
        }

        return $this->vinylCache[$oldVinylId];
    }

    private function getCollectionId($oldCollectionId)
    {
        if (!isset($this->collectionCache[$oldCollectionId])) {
            $collection = Collection::where('old_id', $oldCollectionId)->first();
            $this->collectionCache[$oldCollectionId] = $collection ? $collection->id : null;
        }

        return $this->collectionCache[$oldCollectionId];
    }

    private function fixDate($date)
    {
        return (empty($date) || $date === '0000-00-00 00:00:00') ? '1970-01-01 00:00:00' : $date;
    }

    public function handle()
    {
        $this->info('Début de l\'import des collections et vinyles...');
        $startTime = microtime(true);

        try {
            // Précharger les utilisateurs actifs
            $this->preloadActiveUsers();

            // 1. Import des collections (SEULEMENT pour les comptes actifs)
            $this->info('Import des collections...');
            $collections = DB::connection('mysql_old')
                ->table('collection')
                ->whereIn('membre_id', array_keys($this->activeUserIds))
                ->select([
                    'collection_id',
                    'membre_id',
                    'collection_nom',
                    'collection_commentaires',
                    'collection_date_crea',
                    'collection_date_modif',
                    'ordre'
                ])
                ->get();

            $totalCollections = count($collections);
            $this->info(sprintf('Trouvé %d collections pour des comptes actifs', $totalCollections));

            $bar = $this->output->createProgressBar($totalCollections);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% écoulé | %estimated:-6s% estimé | %memory:6s% | %message%');
            $bar->start();

            $collectionsToInsert = [];
            $imported = 0;

            foreach ($collections as $collection) {
                $userId = $this->getUserId($collection->membre_id);
                if ($userId === null) {
                    $bar->advance();
                    continue;
                }

                $collectionsToInsert[] = [
                    'user_id' => $userId,
                    'collection_nom' => $this->convertToUtf8($collection->collection_nom),
                    'collection_commentaires' => $this->convertToUtf8($collection->collection_commentaires),
                    'collection_date_crea' => $this->fixDate($collection->collection_date_crea),
                    'collection_date_modif' => $this->fixDate($collection->collection_date_modif),
                    'ordre' => $collection->ordre,
                    'old_id' => $collection->collection_id
                ];
                $imported++;

                if (count($collectionsToInsert) >= 1000) {
                    Collection::insert($collectionsToInsert);
                    $collectionsToInsert = [];
                }
                $bar->advance();
            }

            if (!empty($collectionsToInsert)) {
                Collection::insert($collectionsToInsert);
            }

            $bar->finish();
            $this->output->writeln('');
            $this->info("\nImport des collections terminé !");

            // 2. Import des vinyles SEULEMENT pour les comptes actifs
            $this->info('Import des vinyles et associations...');

            // Récupérer les vinyles SEULEMENT des comptes actifs
            $vinyls = DB::connection('mysql_old')
                ->table('collection_vinyl')
                ->leftJoin('vinyl', 'collection_vinyl.vinyl_id', '=', 'vinyl.vinyl_id')
                ->whereIn('collection_vinyl.membre_id', array_keys($this->activeUserIds))
                ->select([
                    'collection_vinyl.collection_id',
                    'collection_vinyl.vinyl_id',
                    'collection_vinyl.membre_id',
                    'collection_vinyl.prix_achat',
                    'collection_vinyl.provenance',
                    'collection_vinyl.annee_achat',
                    'collection_vinyl.vente',
                    'collection_vinyl.commentaires',
                    'collection_vinyl.note',
                    'collection_vinyl.date_ajout',
                    'collection_vinyl.titre',
                    'collection_vinyl.artiste',
                    'collection_vinyl.label',
                    'collection_vinyl.annee',
                    'collection_vinyl.format',
                    'collection_vinyl.reference',
                    'vinyl.vinyl_titre',
                    'vinyl.vinyl_nom',
                    'vinyl.vinyl_format',
                    'vinyl.vinyl_nbcollect',
                    'vinyl.vinyl_alias'
                ])
                ->get();

            $this->info(sprintf('Trouvé %d vinyles pour des comptes actifs', count($vinyls)));

            $bar = $this->output->createProgressBar(count($vinyls));
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% écoulé | %estimated:-6s% estimé | %memory:6s% | %message%');
            $bar->start();

            // Grouper les vinyles par vinyl_id pour éviter les doublons
            $uniqueVinyls = [];
            $vinylAssociations = [];

            foreach ($vinyls as $cv) {
                $vinylKey = $cv->vinyl_id;

                // Collecter les données de vinyle unique
                if (!isset($uniqueVinyls[$vinylKey])) {
                    $uniqueVinyls[$vinylKey] = [
                        'old_id' => $cv->vinyl_id,
                        'vinyl_titre' => $this->convertToUtf8($cv->titre ?: $cv->vinyl_titre),
                        'artiste' => $this->convertToUtf8($cv->artiste),
                        'label' => $this->convertToUtf8($cv->label),
                        'annee' => $cv->annee,
                        'vinyl_format' => $this->getFormatId($cv->format ?: $cv->vinyl_format),
                        'reference' => $this->convertToUtf8($cv->reference),
                        'vinyl_nom' => $this->convertToUtf8($cv->titre ?: $cv->vinyl_nom),
                        'vinyl_nbcollect' => 0,
                        'vinyl_alias' => 0,
                        'pochette' => null, // Les images seront traitées par la commande séparée
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                // Collecter les associations
                $vinylAssociations[] = $cv;
                $bar->advance();
            }

            $bar->finish();
            $this->output->writeln('');

            // Insérer les vinyles uniques par batch
            $this->info(sprintf('Insertion de %d vinyles uniques...', count($uniqueVinyls)));
            $vinylsToInsert = array_values($uniqueVinyls);
            $chunks = array_chunk($vinylsToInsert, 1000);

            foreach ($chunks as $chunk) {
                Vinyl::insert($chunk);
            }

            // Précharger le cache des vinyles après insertion
            $this->info('Rechargement du cache des vinyles...');
            $this->vinylCache = Vinyl::whereNotNull('old_id')->pluck('id', 'old_id')->toArray();

            // Précharger le cache des collections après insertion
            $this->info('Rechargement du cache des collections...');
            $this->collectionCache = Collection::whereNotNull('old_id')->pluck('id', 'old_id')->toArray();

            // Maintenant, créer les associations en batch
            $this->info('Création des associations collections-vinyles...');
            $bar = $this->output->createProgressBar(count($vinylAssociations));
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% écoulé | %estimated:-6s% estimé | %memory:6s% | %message%');
            $bar->start();

            $associationsToInsert = [];
            $importedAssociations = 0;
            $startTimeAssociations = microtime(true);

            foreach ($vinylAssociations as $cv) {
                $collectionId = $this->getCollectionId($cv->collection_id);
                $vinylId = $this->getVinylId($cv->vinyl_id);
                $userId = $this->getUserId($cv->membre_id);

                if ($collectionId && $vinylId && $userId) {
                    $associationsToInsert[] = [
                        'collection_id' => $collectionId,
                        'vinyl_id' => $vinylId,
                        'user_id' => $userId,
                        'prix_achat' => $cv->prix_achat,
                        'provenance' => $this->convertToUtf8($cv->provenance),
                        'annee_achat' => $cv->annee_achat,
                        'vente' => $cv->vente,
                        'commentaires' => $this->convertToUtf8($cv->commentaires),
                        'note' => $cv->note,
                        'date_ajout' => $cv->date_ajout ?: now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    if (count($associationsToInsert) >= 1000) {
                        CollectionVinyl::insert($associationsToInsert);
                        $associationsToInsert = [];
                    }

                    $importedAssociations++;

                    if ($importedAssociations % 1000 === 0) {
                        $elapsedTime = microtime(true) - $startTimeAssociations;
                        $speed = $importedAssociations / $elapsedTime;
                        $remainingItems = count($vinylAssociations) - $importedAssociations;
                        $estimatedTimeRemaining = $remainingItems / ($speed > 0 ? $speed : 1);
                        $bar->setMessage(sprintf(
                            "Associations importées : %d | Vitesse : %.1f/s | Restant : %s",
                            $importedAssociations,
                            $speed,
                            gmdate("H:i:s", (int)$estimatedTimeRemaining)
                        ));
                    }
                }
                $bar->advance();
            }

            if (!empty($associationsToInsert)) {
                CollectionVinyl::insert($associationsToInsert);
            }

            $bar->finish();
            $this->output->writeln('');
            $this->info("\nImport des associations terminé !");

            // Optionnel : lancer l'import des images
            if ($this->confirm('Voulez-vous lancer l\'import des images maintenant ? (peut être long)')) {
                $this->info('Lancement de l\'import des images...');
                $this->call('import:vinyl-images');
            } else {
                $this->info('Vous pouvez lancer l\'import des images plus tard avec : php artisan import:vinyl-images');
            }

            $totalTime = microtime(true) - $startTime;
            $this->info(sprintf("\nImport terminé en %.2f secondes", $totalTime));
            $this->info(sprintf("Collections importées : %d", count($collections)));
            $this->info(sprintf("Vinyles uniques importés : %d", count($uniqueVinyls)));
            $this->info(sprintf("Associations importées : %d", $importedAssociations));

        } catch (\Exception $e) {
            $this->error('Erreur lors de l\'import : ' . $e->getMessage());
            $this->error('Trace : ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
