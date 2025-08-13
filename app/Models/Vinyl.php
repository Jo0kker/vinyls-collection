<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vinyl extends Model
{
    use HasFactory;

    protected $fillable = [
        'old_id',
        'vinyl_nom',
        'vinyl_titre',
        'vinyl_format',
        'vinyl_nbcollect',
        'vinyl_alias',
        'reference',
        'artiste',
        'label',
        'annee',
        'pays',
        'tracks',
        'specificite',
        'pochette',
        'visuels',
        'refMatrice',
        'distribution',
        'edition',
        'anneeOriginal',
        'discogs_id',
        'discogs_type',
        'created_by',
        'discogs_updated_at'
    ];

    public function collectionVinyls()
    {
        return $this->hasMany(CollectionVinyl::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
