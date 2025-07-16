<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Vinyl;
use App\Models\Collection;
use App\Models\CollectionVinyl;

class ImportOrphanedVinylsCommand extends Command
{
    protected $signature = 'import:orphaned-vinyls';
    protected $description = 'Importe les vinyles orphelins (avec membre_id = 0) en récupérant le membre depuis la table collection';

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
        $this->info('Début de l\'import des vinyles orphelins (membre_id = 0)...');
        $startTime = microtime(true);

        try {
            // Précharger les utilisateurs actifs
            $this->preloadActiveUsers();

            // Précharger le cache des vinyles existants
            $this->info('Chargement du cache des vinyles existants...');
            $this->vinylCache = Vinyl::whereNotNull('old_id')->pluck('id', 'old_id')->toArray();

            // Précharger le cache des collections existantes
            $this->info('Chargement du cache des collections existantes...');
            $this->collectionCache = Collection::whereNotNull('old_id')->pluck('id', 'old_id')->toArray();

            // Récupérer les vinyles orphelins en joignant avec la table collection pour récupérer le vrai membre_id
            $this->info('Récupération des vinyles orphelins...');
            $orphanedVinyls = DB::connection('mysql_old')
                ->table('collection_vinyl')
                ->leftJoin('vinyl', 'collection_vinyl.vinyl_id', '=', 'vinyl.vinyl_id')
                ->leftJoin('collection', 'collection_vinyl.collection_id', '=', 'collection.collection_id')
                ->where('collection_vinyl.membre_id', 0)
                ->whereIn('collection.membre_id', array_keys($this->activeUserIds))
                ->select([
                    'collection_vinyl.collection_id',
                    'collection_vinyl.vinyl_id',
                    'collection_vinyl.membre_id as cv_membre_id',
                    'collection.membre_id as real_membre_id',
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
                    'collection_vinyl.pochette',
                    'collection_vinyl.visuels',
                    'collection_vinyl.pays',
                    'collection_vinyl.tracks',
                    'collection_vinyl.specificite',
                    'collection_vinyl.refMatrice',
                    'collection_vinyl.distribution',
                    'collection_vinyl.edition',
                    'collection_vinyl.anneeOriginal',
                    'vinyl.vinyl_titre',
                    'vinyl.vinyl_nom',
                    'vinyl.vinyl_format',
                    'vinyl.vinyl_nbcollect',
                    'vinyl.vinyl_alias'
                ])
                ->get();

            $this->info(sprintf('Trouvé %d vinyles orphelins pour des comptes actifs', count($orphanedVinyls)));

            if (count($orphanedVinyls) === 0) {
                $this->info('Aucun vinyle orphelin trouvé');
                return 0;
            }

            $bar = $this->output->createProgressBar(count($orphanedVinyls));
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% écoulé | %estimated:-6s% estimé | %memory:6s% | %message%');
            $bar->start();

            // Grouper les vinyles par vinyl_id pour éviter les doublons
            $uniqueVinyls = [];
            $vinylAssociations = [];

            foreach ($orphanedVinyls as $cv) {
                $vinylKey = $cv->vinyl_id;

                // Collecter les données de vinyle unique
                if (!isset($uniqueVinyls[$vinylKey])) {
                    $uniqueVinyls[$vinylKey] = [
                        'old_id' => $cv->vinyl_id,
                        'vinyl_titre' => $this->convertToUtf8($cv->titre ?: $cv->vinyl_titre),
                        'vinyl_nom' => $this->convertToUtf8($cv->titre ?: $cv->vinyl_nom),
                        'artiste' => $this->convertToUtf8($cv->artiste),
                        'label' => $this->convertToUtf8($cv->label),
                        'reference' => $this->convertToUtf8($cv->reference),
                        'annee' => $cv->annee,
                        'pays' => $cv->pays,
                        'tracks' => $this->convertToUtf8($cv->tracks),
                        'specificite' => $this->convertToUtf8($cv->specificite),
                        'refMatrice' => $this->convertToUtf8($cv->refMatrice),
                        'distribution' => $this->convertToUtf8($cv->distribution),
                        'edition' => $cv->edition,
                        'anneeOriginal' => $cv->anneeOriginal,
                        'vinyl_format' => $this->getFormatId($cv->format ?: $cv->vinyl_format),
                        'vinyl_nbcollect' => 0,
                        'vinyl_alias' => 0,
                        'visuels' => $cv->visuels,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                // Collecter les associations avec le vrai membre_id
                $vinylAssociations[] = $cv;
                $bar->advance();
            }

            $bar->finish();
            $this->output->writeln('');

            // Insérer les vinyles uniques qui n'existent pas encore
            $this->info('Vérification et insertion des nouveaux vinyles...');
            $vinylsToInsert = [];
            $newVinyls = 0;

            foreach ($uniqueVinyls as $vinylId => $vinylData) {
                if (!isset($this->vinylCache[$vinylId])) {
                    $vinylsToInsert[] = $vinylData;
                    $newVinyls++;
                }
            }

            if (!empty($vinylsToInsert)) {
                $this->info(sprintf('Insertion de %d nouveaux vinyles...', $newVinyls));
                $chunks = array_chunk($vinylsToInsert, 1000);
                foreach ($chunks as $chunk) {
                    Vinyl::insert($chunk);
                }

                // Recharger le cache des vinyles
                $this->info('Rechargement du cache des vinyles...');
                $this->vinylCache = Vinyl::whereNotNull('old_id')->pluck('id', 'old_id')->toArray();
            }

            // Créer les associations avec le vrai membre_id
            $this->info('Création des associations collections-vinyles...');
            $bar = $this->output->createProgressBar(count($vinylAssociations));
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %elapsed:6s% écoulé | %estimated:-6s% estimé | %memory:6s% | %message%');
            $bar->start();

            $associationsToInsert = [];
            $importedAssociations = 0;

            foreach ($vinylAssociations as $cv) {
                $collectionId = $this->getCollectionId($cv->collection_id);
                $vinylId = $this->getVinylId($cv->vinyl_id);
                $userId = $this->getUserId($cv->real_membre_id); // Utiliser le vrai membre_id

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
                }
                $bar->advance();
            }

            if (!empty($associationsToInsert)) {
                CollectionVinyl::insert($associationsToInsert);
            }

            $bar->finish();
            $this->output->writeln('');

            $totalTime = microtime(true) - $startTime;
            $this->info(sprintf("\nImport terminé en %.2f secondes", $totalTime));
            $this->info(sprintf("Vinyles orphelins traités : %d", count($orphanedVinyls)));
            $this->info(sprintf("Nouveaux vinyles créés : %d", $newVinyls));
            $this->info(sprintf("Associations créées : %d", $importedAssociations));

        } catch (\Exception $e) {
            $this->error('Erreur lors de l\'import : ' . $e->getMessage());
            $this->error('Trace : ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}