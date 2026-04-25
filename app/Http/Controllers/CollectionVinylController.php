<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CollectionVinyl;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Helpers\ImageHelper;

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

        // Vérifier si on change de collection
        $newCollectionId = $request->input('collection_id');
        if ($newCollectionId && $newCollectionId != $collectionVinyl->collection_id) {
            // Vérifier que le vinyle n'existe pas déjà dans la collection de destination
            $vinylExistsInNewCollection = CollectionVinyl::where('collection_id', $newCollectionId)
                ->where('vinyl_id', $collectionVinyl->vinyl_id)
                ->where('user_id', Auth::id())
                ->exists();
            
            if ($vinylExistsInNewCollection) {
                return back()->withErrors([
                    'collection_id' => 'Ce vinyle existe déjà dans la collection de destination. Vous ne pouvez pas avoir le même vinyle deux fois dans une collection.'
                ])->withInput();
            }
        }

        $validation = [
            // Champs réellement présents dans la table collection_vinyls
            'prix_achat' => 'nullable|numeric|min:0',
            'annee_achat' => 'nullable|integer|min:1900|max:' . date('Y'),
            'provenance' => 'nullable|integer',
            'commentaires' => 'nullable|string',
            'note' => 'nullable|integer|min:1|max:10',
            'vente' => 'boolean',
            'exemplaire_id' => 'nullable|integer',
            'quantite' => 'required|integer|min:1|max:999',
            'collection_id' => 'required|exists:collections,id',
            // Image fields
            'pochette_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'pochette_url' => 'nullable|url',
        ];

        $vinyl = $collectionVinyl->vinyl;
        $canEditVinyl = $this->canEditVinyl($vinyl);

        // Si on peut éditer le vinyle, ajouter les champs du vinyle à la validation
        if ($canEditVinyl) {
            $validation = array_merge($validation, [
                'vinyl_nom' => 'required|string|max:255',
                'vinyl_titre' => 'nullable|string|max:255',
                'vinyl_format' => 'required|integer|exists:vinyl_formats,id',
                'artiste' => 'required|string|max:255',
                'label' => 'nullable|string|max:255',
                'reference' => 'nullable|string|max:255',
                'annee' => 'nullable|integer|min:1900|max:' . date('Y'),
                'pays' => 'nullable|string|max:255',
                'tracks' => 'nullable|string',
                'specificite' => 'nullable|string',
                'refMatrice' => 'nullable|string|max:255',
                'distribution' => 'nullable|string|max:255',
                'edition' => 'nullable|integer|min:1|max:999',
                'anneeOriginal' => 'nullable|integer|min:1900|max:' . date('Y'),
            ]);
        }

        $request->validate($validation);

        // Mettre à jour les champs de l'exemplaire
        $collectionVinyl->update($request->only([
            'prix_achat',
            'annee_achat',
            'provenance',
            'commentaires',
            'note',
            'vente',
            'exemplaire_id',
            'quantite',
            'collection_id',
        ]));

        // Gérer la mise à jour du vinyle si autorisé
        if ($canEditVinyl) {
            $this->updateVinylData($vinyl, $request);
        } else {
            // Même si on ne peut pas éditer le vinyle, on peut changer l'image si elle n'est pas accessible
            $this->updateVinylImage($vinyl, $request);
        }

        return back()->with('success', 'Informations mises à jour avec succès.');
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
        // Si c'est un vinyle Discogs, PERSONNE ne peut éditer les infos (données protégées de Discogs)
        if ($vinyl->discogs_id && $vinyl->discogs_type !== 'manual') {
            return false;
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
            'quantite',
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

    /**
     * Mettre à jour toutes les données du vinyle (quand on a les permissions)
     */
    private function updateVinylData($vinyl, Request $request)
    {
        $vinylData = $request->only([
            'vinyl_nom',
            'vinyl_titre',
            'vinyl_format',
            'artiste',
            'label',
            'reference',
            'annee',
            'pays',
            'tracks',
            'specificite',
            'refMatrice',
            'distribution',
            'edition',
            'anneeOriginal',
        ]);

        // Gérer l'image
        $this->handleVinylImage($vinyl, $request, $vinylData);

        $vinyl->update($vinylData);
    }

    /**
     * Mettre à jour seulement l'image du vinyle (même sans permissions sur le vinyle)
     * Permet de remplacer une image inaccessible
     */
    private function updateVinylImage($vinyl, Request $request)
    {
        // Vérifier si une image est fournie
        if (!$request->hasFile('pochette_file') && !$request->filled('pochette_url')) {
            return;
        }

        // Pour un vinyle Discogs, on ne peut JAMAIS remplacer l'image
        if ($vinyl->discogs_id && $vinyl->discogs_type !== 'manual') {
            return;
        }

        // Pour un vinyle manuel, vérifier si l'image est accessible
        $imageIsAccessible = ImageHelper::checkImageAccessibility($vinyl->pochette);

        // Si l'image est inaccessible OU si c'est le créateur, permettre le remplacement
        if (!$imageIsAccessible || $vinyl->created_by === Auth::id()) {
            $vinylData = [];
            $this->handleVinylImage($vinyl, $request, $vinylData);

            if (!empty($vinylData)) {
                // Si pas de créateur, définir l'utilisateur actuel comme créateur
                if (!$vinyl->created_by) {
                    $vinylData['created_by'] = Auth::id();
                }

                $vinyl->update($vinylData);
            }
        }
    }

    /**
     * Gérer l'upload ou l'URL de l'image
     */
    private function handleVinylImage($vinyl, Request $request, &$vinylData)
    {
        if ($request->hasFile('pochette_file')) {
            // Supprimer l'ancienne image si elle existe
            ImageHelper::deleteVinylImage($vinyl->pochette);

            // Upload de la nouvelle image via ImageHelper (compression incluse)
            $vinylData['pochette'] = ImageHelper::uploadVinylImage($request->file('pochette_file'));
        } elseif ($request->filled('pochette_url')) {
            // Supprimer l'ancienne image si elle existe et si c'est pas une URL Discogs
            if (!str_contains($vinyl->pochette ?? '', 'discogs.com')) {
                ImageHelper::deleteVinylImage($vinyl->pochette);
            }

            $vinylData['pochette'] = $request->pochette_url;
        }
    }
}