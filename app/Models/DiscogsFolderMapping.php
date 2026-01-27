<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscogsFolderMapping extends Model
{
    protected $fillable = [
        'user_id',
        'collection_id',
        'discogs_folder_id',
        'discogs_folder_name',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
