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
        'visibility',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function collectionVinyls()
    {
        return $this->hasMany(CollectionVinyl::class)->onDelete('cascade');
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
                        // Supprimer l'image de S3 si elle existe et si ce n'est pas un vinyle Discogs
                        if ($vinyl->pochette && !$vinyl->discogs_id) {
                            try {
                                // Vérifier si c'est une URL S3
                                if (str_contains($vinyl->pochette, 's3.amazonaws.com') || str_contains($vinyl->pochette, 'digitaloceanspaces.com')) {
                                    // Extraire le chemin depuis l'URL
                                    $path = parse_url($vinyl->pochette, PHP_URL_PATH);
                                    $path = ltrim($path, '/');
                                    
                                    // Supprimer le fichier de S3
                                    if (\Storage::disk('s3')->exists($path)) {
                                        \Storage::disk('s3')->delete($path);
                                        \Log::info('Image supprimée de S3', ['path' => $path, 'vinyl_id' => $vinylId]);
                                    }
                                }
                            } catch (\Exception $e) {
                                \Log::error('Erreur lors de la suppression de l\'image du vinyle: ' . $e->getMessage());
                            }
                        }
                        
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