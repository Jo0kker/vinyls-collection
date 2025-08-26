<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CollectionVinyl;
use Illuminate\Support\Facades\Auth;

class CollectionVinylController extends Controller
{
    /**
     * Afficher le formulaire d'édition d'un exemplaire
     */
    public function edit(CollectionVinyl $collectionVinyl)
    {
        // Vérifier que l'utilisateur possède cet exemplaire
        if ($collectionVinyl->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cet exemplaire.');
        }

        // Charger les relations nécessaires
        $collectionVinyl->load(['vinyl', 'collection']);

        return Inertia::render('CollectionVinyl/Edit', [
            'collectionVinyl' => $collectionVinyl
        ]);
    }

    /**
     * Mettre à jour les informations spécifiques à l'exemplaire
     */
    public function update(Request $request, CollectionVinyl $collectionVinyl)
    {
        // Vérifier que l'utilisateur possède cet exemplaire
        if ($collectionVinyl->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cet exemplaire.');
        }

        $request->validate([
            // Champs réellement présents dans la table collection_vinyls
            'prix_achat' => 'nullable|numeric|min:0',
            'annee_achat' => 'nullable|integer|min:1900|max:' . date('Y'),
            'provenance' => 'nullable|integer',
            'commentaires' => 'nullable|string',
            'note' => 'nullable|integer|min:1|max:10',
            'vente' => 'boolean',
            'exemplaire_id' => 'nullable|integer',
        ]);

        // Mettre à jour uniquement les champs de l'exemplaire présents dans la BDD
        $collectionVinyl->update($request->only([
            'prix_achat',
            'annee_achat', 
            'provenance',
            'commentaires',
            'note',
            'vente',
            'exemplaire_id',
        ]));

        return back()->with('success', 'Informations de votre exemplaire mises à jour avec succès.');
    }

    /**
     * API pour récupérer les informations modifiables de l'exemplaire
     */
    public function getEditableFields(CollectionVinyl $collectionVinyl)
    {
        // Vérifier que l'utilisateur possède cet exemplaire
        if ($collectionVinyl->user_id !== Auth::id()) {
            abort(403);
        }

        $vinyl = $collectionVinyl->vinyl;
        
        // Déterminer si l'utilisateur peut éditer les infos générales du vinyle
        $canEditVinyl = $this->canEditVinyl($vinyl);

        return response()->json([
            'can_edit_vinyl' => $canEditVinyl,
            'can_edit_instance' => true, // L'utilisateur peut toujours éditer son exemplaire
            'vinyl_creator' => $canEditVinyl ? null : $vinyl->creator?->name,
            'editable_fields' => $this->getEditableFieldsList($canEditVinyl)
        ]);
    }

    /**
     * Déterminer si l'utilisateur peut éditer les informations générales du vinyle
     */
    private function canEditVinyl($vinyl): bool
    {
        // Si c'est un vinyle Discogs, tout le monde peut éditer
        if ($vinyl->discogs_id && $vinyl->discogs_type !== 'manual') {
            return true;
        }

        // Si c'est un vinyle manuel, seul le créateur peut éditer
        return $vinyl->created_by === Auth::id();
    }

    /**
     * Obtenir la liste des champs éditables selon les permissions
     */
    private function getEditableFieldsList(bool $canEditVinyl): array
    {
        // Champs toujours éditables pour l'exemplaire (présents dans collection_vinyls)
        $instanceFields = [
            'prix_achat',
            'annee_achat',
            'provenance', 
            'commentaires',
            'note',
            'vente',
            'exemplaire_id',
        ];

        // Champs du vinyle (dans la table vinyls) éditables si on a les permissions
        $vinylFields = [
            'vinyl_nom',
            'vinyl_titre',
            'vinyl_format',
            'artiste',
            'label',
            'annee',
            'pays',
            'reference',
            'tracks',
            'specificite',
            'refMatrice',
            'distribution',
            'edition',
            'anneeOriginal',
        ];

        return [
            'always_editable' => $instanceFields,  // Champs de l'exemplaire toujours éditables
            'vinyl_editable' => $canEditVinyl ? $vinylFields : [],  // Champs du vinyle si permissions
            'vinyl_read_only' => !$canEditVinyl ? $vinylFields : [],  // Champs du vinyle en lecture seule
        ];
    }
}