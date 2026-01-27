<?php

namespace App\Jobs;

use App\Models\Collection;
use App\Models\CollectionVinyl;
use App\Models\DiscogsImport;
use App\Models\DiscogsFolderMapping;
use App\Models\User;
use App\Models\Vinyl;
use App\Services\DiscogsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportDiscogsCollectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1; // Don't retry automatically

    protected int $userId;
    protected int $importId;
    protected int $folderId;
    protected int $collectionId;

    public function __construct(int $userId, int $importId, int $folderId, int $collectionId)
    {
        $this->userId = $userId;
        $this->importId = $importId;
        $this->folderId = $folderId;
        $this->collectionId = $collectionId;
    }

    public function handle(DiscogsService $discogsService): void
    {
        $import = DiscogsImport::find($this->importId);
        if (!$import) {
            Log::error('Discogs import not found', ['import_id' => $this->importId]);
            return;
        }

        $user = User::find($this->userId);
        if (!$user || !$user->discogs_oauth_token) {
            $import->markAsFailed('User not found or Discogs not connected');
            return;
        }

        $collection = Collection::find($this->collectionId);
        if (!$collection) {
            $import->markAsFailed('Collection not found');
            return;
        }

        try {
            Log::info('Starting Discogs import', [
                'user_id' => $this->userId,
                'import_id' => $this->importId,
                'folder_id' => $this->folderId,
            ]);

            // First, get the total count
            $firstPage = $discogsService->getFolderItems(
                $user->discogs_username,
                $this->folderId,
                $user->discogs_oauth_token,
                $user->discogs_oauth_token_secret,
                1,
                1
            );

            $totalItems = $firstPage['pagination']['items'] ?? 0;
            $import->markAsStarted($totalItems);

            if ($totalItems === 0) {
                // No items in Discogs folder, remove all items from this folder
                $this->removeOrphanedItems($collection, []);
                $import->markAsCompleted();
                return;
            }

            // Track all Discogs vinyl IDs we encounter (for orphan cleanup)
            $syncedVinylIds = [];

            // Process all items
            $generator = $discogsService->getAllFolderItems(
                $user->discogs_username,
                $this->folderId,
                $user->discogs_oauth_token,
                $user->discogs_oauth_token_secret,
                function ($page, $totalPages, $itemsInPage) use ($import) {
                    Log::info("Discogs import progress: page {$page}/{$totalPages}");
                }
            );

            foreach ($generator as $release) {
                try {
                    $result = $this->importRelease($release, $collection, $user, $discogsService);
                    $import->incrementProgress($result['imported']);

                    // Track the vinyl_id for orphan cleanup
                    if ($result['vinyl_id']) {
                        $syncedVinylIds[] = $result['vinyl_id'];
                    }
                } catch (\Exception $e) {
                    $releaseName = $release['basic_information']['title'] ?? 'Unknown';
                    $import->addError($releaseName, $e->getMessage());
                    $import->incrementProgress(false);
                    Log::error('Failed to import release', [
                        'release' => $releaseName,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Remove items that are no longer in Discogs folder
            $removedCount = $this->removeOrphanedItems($collection, $syncedVinylIds);
            $import->update(['removed_items' => $removedCount]);

            if ($removedCount > 0) {
                Log::info("Removed {$removedCount} orphaned items from collection", [
                    'collection_id' => $collection->id,
                    'folder_id' => $this->folderId,
                ]);
            }

            // Update folder mapping sync timestamp
            DiscogsFolderMapping::updateOrCreate(
                [
                    'user_id' => $this->userId,
                    'discogs_folder_id' => $this->folderId,
                ],
                [
                    'collection_id' => $this->collectionId,
                    'discogs_folder_name' => $firstPage['releases'][0]['folder_id'] ?? 'Folder ' . $this->folderId,
                    'last_synced_at' => now(),
                ]
            );

            // Update collection modification date
            $collection->update(['collection_date_modif' => now()]);

            $import->markAsCompleted();

            Log::info('Discogs import completed', [
                'import_id' => $this->importId,
                'imported' => $import->imported_items,
                'skipped' => $import->skipped_items,
                'removed' => $removedCount ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error('Discogs import failed', [
                'import_id' => $this->importId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $import->markAsFailed($e->getMessage());
        }
    }

    /**
     * Remove items from collection that were imported from this Discogs folder
     * but are no longer present in Discogs
     */
    protected function removeOrphanedItems(Collection $collection, array $syncedVinylIds): int
    {
        $query = CollectionVinyl::where('collection_id', $collection->id)
            ->where('discogs_folder_id', $this->folderId);

        if (!empty($syncedVinylIds)) {
            $query->whereNotIn('vinyl_id', $syncedVinylIds);
        }

        return $query->delete();
    }

    /**
     * Import a single release from Discogs
     * Returns ['imported' => bool, 'vinyl_id' => int|null]
     */
    protected function importRelease(array $release, Collection $collection, User $user, DiscogsService $discogsService): array
    {
        $basicInfo = $release['basic_information'] ?? [];
        $releaseId = $basicInfo['id'] ?? null;

        if (!$releaseId) {
            throw new \Exception('Release has no ID');
        }

        // Check if this release already exists as a vinyl
        $existingVinyl = Vinyl::where('discogs_id', (string) $releaseId)
            ->where('discogs_type', 'release')
            ->first();

        if ($existingVinyl) {
            // Check if already in this collection with this folder
            $existingCollectionVinyl = CollectionVinyl::where('collection_id', $collection->id)
                ->where('vinyl_id', $existingVinyl->id)
                ->first();

            if ($existingCollectionVinyl) {
                // Update discogs_folder_id if not set (for migrating old imports)
                if ($existingCollectionVinyl->discogs_folder_id === null) {
                    $existingCollectionVinyl->update(['discogs_folder_id' => $this->folderId]);
                }
                return ['imported' => false, 'vinyl_id' => $existingVinyl->id]; // Skip, already imported
            }

            // Add existing vinyl to collection
            $this->addVinylToCollection($existingVinyl, $collection, $user, $release);
            return ['imported' => true, 'vinyl_id' => $existingVinyl->id];
        }

        // Create new vinyl from Discogs data
        $vinylData = $this->convertBasicInfoToVinylData($basicInfo);
        $vinylData['discogs_id'] = (string) $releaseId;
        $vinylData['discogs_type'] = 'release';
        $vinylData['discogs_updated_at'] = now();

        $vinyl = Vinyl::create($vinylData);
        $this->addVinylToCollection($vinyl, $collection, $user, $release);

        return ['imported' => true, 'vinyl_id' => $vinyl->id];
    }

    /**
     * Add a vinyl to the collection
     */
    protected function addVinylToCollection(Vinyl $vinyl, Collection $collection, User $user, array $release): void
    {
        // Extract date_added from Discogs if available
        $dateAdded = isset($release['date_added'])
            ? \Carbon\Carbon::parse($release['date_added'])
            : now();

        CollectionVinyl::create([
            'collection_id' => $collection->id,
            'vinyl_id' => $vinyl->id,
            'user_id' => $user->id,
            'date_ajout' => $dateAdded,
            'quantite' => 1,
            'discogs_folder_id' => $this->folderId,
        ]);
    }

    /**
     * Convert Discogs basic_information to vinyl data
     */
    protected function convertBasicInfoToVinylData(array $basicInfo): array
    {
        $artists = [];
        if (isset($basicInfo['artists']) && !empty($basicInfo['artists'])) {
            $artists = collect($basicInfo['artists'])->pluck('name')->toArray();
        }

        $labels = [];
        $catno = null;
        if (isset($basicInfo['labels']) && !empty($basicInfo['labels'])) {
            $labels = collect($basicInfo['labels'])->pluck('name')->toArray();
            $catno = collect($basicInfo['labels'])->pluck('catno')->filter(fn($c) => $c && $c !== 'none')->first();
        }

        // Get cover image
        $pochette = $basicInfo['cover_image'] ?? $basicInfo['thumb'] ?? null;

        return [
            'vinyl_nom' => $basicInfo['title'] ?? 'Titre inconnu',
            'vinyl_titre' => $basicInfo['title'] ?? 'Titre inconnu',
            'vinyl_format' => 1,
            'vinyl_nbcollect' => 1,
            'vinyl_alias' => 0,
            'artiste' => !empty($artists) ? implode(', ', $artists) : 'Artiste inconnu',
            'label' => !empty($labels) ? implode(', ', $labels) : null,
            'reference' => $catno,
            'annee' => isset($basicInfo['year']) && $basicInfo['year'] > 0 ? $basicInfo['year'] : null,
            'pochette' => $pochette,
        ];
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $import = DiscogsImport::find($this->importId);
        if ($import) {
            $import->markAsFailed('Job failed: ' . $exception->getMessage());
        }

        Log::error('ImportDiscogsCollectionJob failed', [
            'import_id' => $this->importId,
            'error' => $exception->getMessage()
        ]);
    }
}
