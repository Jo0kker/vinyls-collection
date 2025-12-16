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
            $vinylIds = $collection->collectionVinyls()->pluck('vinyl_id')->unique()->toArray();

            if (empty($vinylIds)) {
                return;
            }

            // Optimisation: récupérer le nombre de collections pour chaque vinyle en une seule requête
            $vinylCounts = CollectionVinyl::whereIn('vinyl_id', $vinylIds)
                ->groupBy('vinyl_id')
                ->selectRaw('vinyl_id, count(*) as count')
                ->pluck('count', 'vinyl_id');

            // Identifier les vinyles qui deviendront orphelins après suppression
            $orphanVinylIds = [];
            foreach ($vinylIds as $vinylId) {
                if (($vinylCounts[$vinylId] ?? 0) === 1) {
                    $orphanVinylIds[] = $vinylId;
                }
            }

            // Supprimer tous les collection_vinyls de cette collection
            $collection->collectionVinyls()->delete();

            // Supprimer les vinyles orphelins et leurs images
            if (!empty($orphanVinylIds)) {
                $orphanVinyls = Vinyl::whereIn('id', $orphanVinylIds)->get();
                foreach ($orphanVinyls as $vinyl) {
                    ImageHelper::deleteVinylImage($vinyl->pochette);
                }
                Vinyl::whereIn('id', $orphanVinylIds)->delete();
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