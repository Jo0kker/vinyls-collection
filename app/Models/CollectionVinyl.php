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
        'exemplaire_id',
        'annee_achat',
        'provenance',
        'prix_achat',
        'etat_vinyl',
        'etat_pochette',
        'vente',
        'commentaires',
        'annee',
        'reference',
        'pays',
        'tracks',
        'specificite',
        'pochette',
        'origine',
        'date_ajout',
        'visuels',
        'note',
        'visibilite',
        'label',
        'refMatrice',
        'anneeOriginal',
        'distribution',
        'edition',
        'etat_vinyl2',
        'etat_vinyl3',
        'etat_vinyl4',
        'etat_vinyl5',
        'etat_vinyl6',
        'titre',
        'artiste',
        'format',
        'user_id',
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