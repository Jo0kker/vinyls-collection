<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\ImageHelper;

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
        'visibility',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function collectionVinyls()
    {
        return $this->hasMany(CollectionVinyl::class);
    }
    
    protected static function booted()
    {
        static::deleting(function ($collection) {
            // Avant de supprimer la collection, on récupère tous les vinyles
            $vinylIds = $collection->collectionVinyls()->pluck('vinyl_id')->unique();
            
            // Supprimer tous les collection_vinyls de cette collection
            $collection->collectionVinyls()->delete();
            
            // Pour chaque vinyle, vérifier s'il est encore possédé par quelqu'un
            foreach ($vinylIds as $vinylId) {
                $remainingCount = CollectionVinyl::where('vinyl_id', $vinylId)->count();
                
                if ($remainingCount === 0) {
                    // Personne d'autre ne possède ce vinyle, on peut le supprimer
                    $vinyl = Vinyl::find($vinylId);

                    if ($vinyl) {
                        // Supprimer l'image du storage si c'est une image S3
                        ImageHelper::deleteVinylImage($vinyl->pochette);

                        // Supprimer le vinyle
                        $vinyl->delete();
                        \Log::info('Vinyle orphelin supprimé', ['vinyl_id' => $vinylId, 'collection_id' => $collection->id]);
                    }
                }
            }
        });
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