<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscogsImport extends Model
{
    protected $fillable = [
        'user_id',
        'collection_id',
        'discogs_folder_id',
        'status',
        'total_items',
        'processed_items',
        'imported_items',
        'skipped_items',
        'removed_items',
        'error_message',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Check if import is still running
     */
    public function isRunning(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_items === 0) {
            return 0;
        }
        return (int) round(($this->processed_items / $this->total_items) * 100);
    }

    /**
     * Mark import as started
     */
    public function markAsStarted(int $totalItems): void
    {
        $this->update([
            'status' => 'processing',
            'total_items' => $totalItems,
            'started_at' => now(),
        ]);
    }

    /**
     * Increment progress
     */
    public function incrementProgress(bool $imported = true): void
    {
        $this->increment('processed_items');
        if ($imported) {
            $this->increment('imported_items');
        } else {
            $this->increment('skipped_items');
        }
    }

    /**
     * Add error to the errors list
     */
    public function addError(string $itemName, string $error): void
    {
        $errors = $this->errors ?? [];
        $errors[] = [
            'item' => $itemName,
            'error' => $error,
            'at' => now()->toIso8601String(),
        ];
        $this->update(['errors' => $errors]);
    }

    /**
     * Mark import as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark import as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }
}
