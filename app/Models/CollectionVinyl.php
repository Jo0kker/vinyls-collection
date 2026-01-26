<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionVinyl extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_id',
        'vinyl_id',
        'user_id',
        'exemplaire_id',
        'quantite',
        'annee_achat',
        'provenance',
        'prix_achat',
        'vente',
        'commentaires',
        'note',
        'date_ajout',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function vinyl()
    {
        return $this->belongsTo(Vinyl::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 