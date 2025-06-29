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
    ];

    public function collectionVinyls()
    {
        return $this->hasMany(CollectionVinyl::class);
    }
} 