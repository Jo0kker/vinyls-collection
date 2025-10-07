<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CollectionVinyl;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

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

        $validation = [
            // Champs réellement présents dans la table collection_vinyls
            'prix_achat' => 'nullable|numeric|min:0',
            'annee_achat' => 'nullable|integer|min:1900|max:' . date('Y'),
            'provenance' => 'nullable|integer',
            'commentaires' => 'nullable|string',
            'note' => 'nullable|integer|min:1|max:10',
            'vente' => 'boolean',
            'exemplaire_id' => 'nullable|integer',
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
                'vinyl_format' => 'required|integer|min:1|max:9',
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
     */
    private function updateVinylImage($vinyl, Request $request)
    {
        if ($request->hasFile('pochette_file') || $request->filled('pochette_url')) {
            $vinylData = [];
            $this->handleVinylImage($vinyl, $request, $vinylData);

            if (!empty($vinylData)) {
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
            $this->deleteVinylImage($vinyl->pochette);

            // Upload de la nouvelle image
            $file = $request->file('pochette_file');
            $filename = 'vinyl-' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('vinyls', $filename, 's3');
            $vinylData['pochette'] = Storage::disk('s3')->url($path);
        } elseif ($request->filled('pochette_url')) {
            // Supprimer l'ancienne image si elle existe et si c'est pas une URL Discogs
            if (!str_contains($vinyl->pochette ?? '', 'discogs.com')) {
                $this->deleteVinylImage($vinyl->pochette);
            }

            $vinylData['pochette'] = $request->pochette_url;
        }
    }

    /**
     * Supprimer l'image du vinyle du stockage S3
     */
    private function deleteVinylImage($imageUrl)
    {
        if (!$imageUrl) return;

        try {
            // Vérifier si c'est une URL S3
            if (str_contains($imageUrl, 's3.amazonaws.com') || str_contains($imageUrl, 'digitaloceanspaces.com')) {
                // Extraire le chemin depuis l'URL
                $path = parse_url($imageUrl, PHP_URL_PATH);
                $path = ltrim($path, '/');

                // Supprimer le fichier de S3
                if (Storage::disk('s3')->exists($path)) {
                    Storage::disk('s3')->delete($path);
                    \Log::info('Image supprimée de S3 depuis CollectionVinylController', ['path' => $path]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de l\'image du vinyle: ' . $e->getMessage());
        }
    }
}