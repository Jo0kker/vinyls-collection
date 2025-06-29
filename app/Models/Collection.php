<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = [
        'collection_nom',
        'collection_date_crea',
        'collection_date_modif',
        'collection_commentaires',
        'user_id',
        'ordre',
        'old_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function collectionVinyls()
    {
        return $this->hasMany(CollectionVinyl::class);
    }

    public function vinyls()
    {
        // Relation many-to-many avec la table pivot collection_vinyls (ou CollectionVinyl)
        return $this->belongsToMany(Vinyl::class, 'collection_vinyls', 'collection_id', 'vinyl_id')
            ->withPivot([
                'user_id',
                'prix_achat',
                'provenance',
                'annee_achat',
                'vente',
                'commentaires',
                'note',
                'date_ajout',
                'created_at',
                'updated_at',
            ]);
    }
} 