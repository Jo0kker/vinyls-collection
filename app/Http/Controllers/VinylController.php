<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Collection;
use App\Models\Vinyl;
use App\Models\CollectionVinyl;
use App\Services\DiscogsService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VinylController extends Controller
{
    /**
     * Display a listing of the user's vinyls
     */
    public function index()
    {
        $user = Auth::user();

        $vinyls = $user->vinylCollections()
            ->with(['vinyl', 'collection'])
            ->orderBy('date_ajout', 'desc')
            ->get();

        // Ajouter l'information canEdit pour chaque vinyl et charger le créateur
        $vinyls->transform(function ($collectionVinyl) use ($user) {
            $vinyl = $collectionVinyl->vinyl;
            $canEdit = true; // Par défaut peut éditer car c'est son vinyl
            
            // Si c'est un vinyle manuel, vérifier que l'utilisateur est le créateur
            if ($vinyl->discogs_type === 'manual' || !$vinyl->discogs_id) {
                $canEdit = $vinyl->created_by === $user->id;
                // Charger le créateur pour l'afficher dans le toast
                if ($vinyl->created_by) {
                    $vinyl->load('creator');
                }
            }
            
            $collectionVinyl->can_edit = $canEdit;
            return $collectionVinyl;
        });

        // Récupérer toutes les collections de l'utilisateur pour les modales
        $allCollections = $user->collections()->orderBy('collection_nom')->get();

        return Inertia::render('Vinyls/Index', [
            'vinyls' => $vinyls,
            'allCollections' => $allCollections
        ]);
    }

    /**
     * Show the form for creating a new vinyl
     */
    public function create()
    {
        $user = Auth::user();
        $collections = $user->collections()->orderBy('collection_nom')->get();

        return Inertia::render('Vinyls/Create', [
            'collections' => $collections
        ]);
    }

    /**
     * Store a newly created vinyl in storage
     */
    public function store(Request $request)
    {
        $request->validate([
            'vinyl_nom' => 'required|string|max:255',
            'vinyl_titre' => 'required|string|max:255',
            'vinyl_format' => 'required|integer',
            'collection_id' => 'required|exists:collections,id',
            'prix_achat' => 'nullable|numeric|min:0',
            'annee_achat' => 'nullable|integer|min:1900|max:' . date('Y'),
            'provenance' => 'nullable|integer',
            'commentaires' => 'nullable|string',
            'note' => 'nullable|integer|min:1|max:10',
        ]);

        $user = Auth::user();

        // Vérifier que la collection appartient à l'utilisateur
        $collection = $user->collections()->findOrFail($request->collection_id);

        // Créer le vinyle
        $vinyl = Vinyl::create([
            'vinyl_nom' => $request->vinyl_nom,
            'vinyl_titre' => $request->vinyl_titre,
            'vinyl_format' => $request->vinyl_format,
            'vinyl_nbcollect' => 1,
            'vinyl_alias' => 0,
        ]);

        // Créer l'association avec la collection
        CollectionVinyl::create([
            'collection_id' => $collection->id,
            'vinyl_id' => $vinyl->id,
            'user_id' => $user->id,
            'prix_achat' => $request->prix_achat,
            'annee_achat' => $request->annee_achat,
            'provenance' => $request->provenance,
            'commentaires' => $request->commentaires,
            'note' => $request->note,
            'date_ajout' => Carbon::now(),
        ]);

        // Mettre à jour la date de modification de la collection
        $collection->update([
            'collection_date_modif' => Carbon::now()
        ]);

        return back()->with('success', 'Vinyle ajouté avec succès.');
    }

    /**
     * Display the specified vinyl
     */
    public function show(CollectionVinyl $collectionVinyl, DiscogsService $discogsService)
    {
        // Charger les relations nécessaires
        $collectionVinyl->load(['vinyl', 'collection', 'user']);
        
        // Vérifier si la collection est publique ou si l'utilisateur est propriétaire
        $isOwner = Auth::check() && $collectionVinyl->user_id === Auth::id();
        $isPublicCollection = $collectionVinyl->collection->visibility === 'public';
        
        // Permettre l'accès si l'utilisateur est propriétaire OU si la collection est publique
        if (!$isOwner && !$isPublicCollection) {
            abort(403, 'Cette collection n\'est pas publique.');
        }

        // Vérifier si l'utilisateur peut éditer le vinyle
        $canEdit = $isOwner;
        $vinyl = $collectionVinyl->vinyl;
        if (!$vinyl->discogs_id) {
            $canEdit = $isOwner && $vinyl->created_by === Auth::id();
        }
        
        // Si c'est un vinyle Discogs, enrichir avec les données complètes
        $vinyl = $collectionVinyl->vinyl;
        if ($vinyl->discogs_id) {
            try {
                // Récupérer les détails complets depuis Discogs
                if ($vinyl->discogs_type === 'master') {
                    $discogsData = $discogsService->getMaster($vinyl->discogs_id);
                } else {
                    $discogsData = $discogsService->getRelease($vinyl->discogs_id);
                }
                
                if ($discogsData) {
                    // Ajouter les données Discogs enrichies sans écraser les données existantes
                    $vinyl->discogs_data = [
                        'tracklist' => $discogsData['tracklist'] ?? [],
                        'videos' => $discogsData['videos'] ?? [],
                        'images' => $discogsData['images'] ?? [],
                        'genres' => $discogsData['genres'] ?? [],
                        'styles' => $discogsData['styles'] ?? [],
                        'notes' => $discogsData['notes'] ?? null,
                        'data_quality' => $discogsData['data_quality'] ?? null,
                        'country' => $discogsData['country'] ?? null,
                        'released' => $discogsData['released'] ?? null,
                        'formats' => $discogsData['formats'] ?? [],
                        'labels' => $discogsData['labels'] ?? [],
                        // Pour les masters, ajouter les infos spécifiques
                        'main_release' => $discogsData['main_release'] ?? null,
                        'main_release_url' => $discogsData['main_release_url'] ?? null,
                        'versions_url' => $discogsData['versions_url'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                // Log l'erreur mais ne pas faire échouer la page
                \Log::warning('Impossible de récupérer les détails Discogs pour le vinyle ' . $vinyl->id . ': ' . $e->getMessage());
                $vinyl->discogs_data = null;
            }
        }

        return Inertia::render('Vinyls/Show', [
            'collectionVinyl' => $collectionVinyl,
            'isOwner' => $isOwner,
            'canEdit' => $canEdit
        ]);
    }

    /**
     * Display a vinyl's details with all owners
     */
    public function showVinyl(Vinyl $vinyl, DiscogsService $discogsService)
    {
        // Enrichir avec les données Discogs si nécessaire
        if ($vinyl->discogs_id) {
            try {
                // Récupérer les détails complets depuis Discogs
                if ($vinyl->discogs_type === 'master') {
                    $discogsData = $discogsService->getMaster($vinyl->discogs_id);
                } else {
                    $discogsData = $discogsService->getRelease($vinyl->discogs_id);
                }
                
                if ($discogsData) {
                    // Ajouter les données Discogs enrichies sans écraser les données existantes
                    $vinyl->discogs_data = [
                        'tracklist' => $discogsData['tracklist'] ?? [],
                        'videos' => $discogsData['videos'] ?? [],
                        'images' => $discogsData['images'] ?? [],
                        'genres' => $discogsData['genres'] ?? [],
                        'styles' => $discogsData['styles'] ?? [],
                        'notes' => $discogsData['notes'] ?? null,
                        'data_quality' => $discogsData['data_quality'] ?? null,
                        'country' => $discogsData['country'] ?? null,
                        'released' => $discogsData['released'] ?? null,
                        'formats' => $discogsData['formats'] ?? [],
                        'labels' => $discogsData['labels'] ?? [],
                        // Pour les masters, ajouter les infos spécifiques
                        'main_release' => $discogsData['main_release'] ?? null,
                        'main_release_url' => $discogsData['main_release_url'] ?? null,
                        'versions_url' => $discogsData['versions_url'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                // Log l'erreur mais ne pas faire échouer la page
                \Log::warning('Impossible de récupérer les détails Discogs pour le vinyle ' . $vinyl->id . ': ' . $e->getMessage());
                $vinyl->discogs_data = null;
            }
        }

        // Récupérer tous les possesseurs de ce vinyle avec collections publiques
        $owners = $vinyl->collectionVinyls()
            ->with(['user', 'collection'])
            ->whereHas('collection', function($query) {
                $query->where('visibility', 'public');
            })
            ->whereHas('user', function($query) {
                $query->where('profile_public', true);
            })
            ->get();

        // Charger le créateur si c'est un vinyle manuel
        if (($vinyl->discogs_type === 'manual' || !$vinyl->discogs_id) && $vinyl->created_by) {
            $vinyl->load('creator');
        }

        // Si l'utilisateur connecté possède ce vinyle, ajouter ses exemplaires même privés
        if (Auth::check()) {
            $userOwnedVinyls = $vinyl->collectionVinyls()
                ->with(['user', 'collection'])
                ->where('user_id', Auth::id())
                ->get();
                
            $owners = $owners->merge($userOwnedVinyls)->unique('id');
        }

        // Récupérer les collections de l'utilisateur connecté
        $userCollections = [];
        if (Auth::check()) {
            $userCollections = Auth::user()->collections()->get(['id', 'collection_nom']);
        }

        return Inertia::render('Vinyls/VinylShow', [
            'vinyl' => $vinyl,
            'owners' => $owners,
            'userCollections' => $userCollections
        ]);
    }

    /**
     * Show the form for editing the specified vinyl
     */
    public function edit(CollectionVinyl $collectionVinyl)
    {
        // Vérifier que l'utilisateur est propriétaire de l'exemplaire
        if ($collectionVinyl->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cet exemplaire.');
        }

        $user = Auth::user();
        $collections = $user->collections()->orderBy('collection_nom')->get();
        $collectionVinyl->load(['vinyl', 'collection']);
        
        // Charger le créateur pour les vinyles manuels
        $vinyl = $collectionVinyl->vinyl;
        if (!$vinyl->discogs_id && $vinyl->created_by) {
            $vinyl->load('creator');
        }
        
        // Déterminer les permissions d'édition
        $canEditVinyl = $this->canEditVinyl($vinyl);
        $canEditInstance = true; // L'utilisateur peut toujours éditer son exemplaire

        return Inertia::render('Vinyls/Edit', [
            'collectionVinyl' => $collectionVinyl,
            'collections' => $collections,
            'canEditVinyl' => $canEditVinyl,
            'canEditInstance' => $canEditInstance,
            'vinylCreator' => $vinyl->creator ?? null
        ]);
    }
    
    /**
     * Déterminer si l'utilisateur peut éditer les informations du vinyle
     */
    private function canEditVinyl($vinyl): bool
    {
        // Personne ne peut éditer un vinyle Discogs (données venant de l'API)
        if ($vinyl->discogs_id) {
            return false;
        }
        
        // Pour les vinyles manuels (discogs_id = null), seul le créateur peut éditer
        return $vinyl->created_by === Auth::id();
    }

    /**
     * Update the specified vinyl in storage
     */
    public function update(Request $request, CollectionVinyl $collectionVinyl)
    {
        // Vérifier que l'utilisateur est propriétaire de l'exemplaire
        if ($collectionVinyl->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cet exemplaire.');
        }

        $vinyl = $collectionVinyl->vinyl;
        $canEditVinyl = $this->canEditVinyl($vinyl);
        $isManualVinyl = is_null($vinyl->discogs_id);

        // Validation selon les permissions
        if ($canEditVinyl && $isManualVinyl) {
            // Vinyle manuel : tous les champs modifiables avec validation complète
            $rules = [
                'collection_id' => 'required|exists:collections,id',
                'prix_achat' => 'nullable|numeric|min:0',
                'annee_achat' => 'nullable|integer|min:1900|max:' . date('Y'),
                'provenance' => 'nullable|integer',
                'commentaires' => 'nullable|string',
                'note' => 'nullable|integer|min:1|max:10',
                'pochette_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
                'vinyl_nom' => 'required|string|max:255',
                'vinyl_titre' => 'nullable|string|max:255',
                'artiste' => 'required|string|max:255',
                'label' => 'nullable|string|max:255',
                'reference' => 'nullable|string|max:255',
                'annee' => 'nullable|integer|min:1900|max:' . date('Y'),
                'pays' => 'nullable|integer',
                'tracks' => 'nullable|string',
                'specificite' => 'nullable|string',
                'refMatrice' => 'nullable|string|max:255',
                'distribution' => 'nullable|string|max:255',
                'edition' => 'nullable|integer',
                'anneeOriginal' => 'nullable|integer|min:1900|max:' . date('Y'),
                'vinyl_format' => 'required|integer',
                'pochette_url' => 'nullable|url',
            ];
        } else {
            // Vinyle Discogs : validation uniquement des champs pivot (collection)
            $rules = [
                'collection_id' => 'required|exists:collections,id',
                'prix_achat' => 'nullable|numeric|min:0',
                'annee_achat' => 'nullable|integer|min:1900|max:' . date('Y'),
                'provenance' => 'nullable|integer',
                'commentaires' => 'nullable|string',
                'note' => 'nullable|integer|min:1|max:10',
                // Pas de validation des champs vinyle pour Discogs car ils ne sont pas modifiables
            ];
        }

        $request->validate($rules);

        $user = Auth::user();

        // Vérifier que la collection appartient à l'utilisateur
        $collection = $user->collections()->findOrFail($request->collection_id);

        // Gérer l'upload d'image si présent
        $pochetteUrl = $vinyl->pochette;
        if ($request->hasFile('pochette_file')) {
            $pochetteUrl = $this->uploadVinylImage($request->file('pochette_file'));
        } elseif ($isManualVinyl && $request->filled('pochette_url')) {
            $pochetteUrl = $request->pochette_url;
        }

        // Mettre à jour le vinyle seulement si l'utilisateur a les permissions
        if ($canEditVinyl && $isManualVinyl) {
            // Vinyle manuel ET l'utilisateur est le créateur : mise à jour complète
            $vinylUpdateData = [
                'vinyl_nom' => $request->vinyl_nom,
                'vinyl_titre' => $request->vinyl_titre ?: $request->vinyl_nom,
                'artiste' => $request->artiste,
                'label' => $request->label,
                'reference' => $request->reference,
                'annee' => $request->annee,
                'pays' => $request->pays,
                'tracks' => $request->tracks,
                'specificite' => $request->specificite,
                'refMatrice' => $request->refMatrice,
                'distribution' => $request->distribution,
                'edition' => $request->edition,
                'anneeOriginal' => $request->anneeOriginal,
                'vinyl_format' => $request->vinyl_format,
                'pochette' => $pochetteUrl,
            ];
            $vinyl->update($vinylUpdateData);
        }
        // Si pas de permissions pour éditer le vinyle, on ne met à jour que l'exemplaire

        // Mettre à jour l'association avec la collection
        $collectionVinyl->update([
            'collection_id' => $collection->id,
            'prix_achat' => $request->prix_achat,
            'annee_achat' => $request->annee_achat,
            'provenance' => $request->provenance,
            'commentaires' => $request->commentaires,
            'note' => $request->note,
        ]);

        // Mettre à jour la date de modification de la collection
        $collection->update([
            'collection_date_modif' => Carbon::now()
        ]);

        // Pour une requête AJAX (modal), utiliser back()
        return back()->with('success', 'Vinyle mis à jour avec succès.');
    }

    /**
     * Remove the specified vinyl from storage
     */
    public function destroy(CollectionVinyl $collectionVinyl)
    {
        // Vérifier que l'utilisateur est propriétaire
        if ($collectionVinyl->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à ce vinyle.');
        }

        $collection = $collectionVinyl->collection;
        $vinyl = $collectionVinyl->vinyl;
        
        // Supprimer l'exemplaire de la collection
        $collectionVinyl->delete();

        // Vérifier si d'autres utilisateurs possèdent ce vinyle
        $remainingCount = CollectionVinyl::where('vinyl_id', $vinyl->id)->count();
        
        if ($remainingCount === 0) {
            // Personne d'autre ne possède ce vinyle, on peut le supprimer complètement
            
            // Supprimer l'image de S3 si elle existe
            if ($vinyl->pochette && !$vinyl->discogs_id) { // Ne pas supprimer les images Discogs
                $this->deleteVinylImage($vinyl->pochette);
            }
            
            // Supprimer le vinyle de la base de données
            $vinyl->delete();
            
            \Log::info('Vinyle orphelin supprimé', [
                'vinyl_id' => $vinyl->id,
                'vinyl_nom' => $vinyl->vinyl_nom,
                'image_deleted' => !empty($vinyl->pochette)
            ]);
        }

        // Mettre à jour la date de modification de la collection
        $collection->update([
            'collection_date_modif' => Carbon::now()
        ]);

        return back()->with('success', 'Vinyle supprimé avec succès.');
    }

    /**
     * Duplicate a vinyl to create a user's own copy
     */
    public function duplicate(CollectionVinyl $collectionVinyl)
    {
        $user = Auth::user();
        $originalVinyl = $collectionVinyl->vinyl;
        
        // Vérifier que le vinyle est manuel (pas de discogs_id) et que l'utilisateur n'est pas déjà le créateur
        if ($originalVinyl->discogs_id) {
            return back()->with('error', 'Les vinyles Discogs ne peuvent pas être dupliqués.');
        }
        
        if ($originalVinyl->created_by === $user->id) {
            return back()->with('error', 'Vous êtes déjà le créateur de ce vinyle.');
        }
        
        // Dupliquer l'image si elle existe
        $newPochetteUrl = null;
        if ($originalVinyl->pochette) {
            $newPochetteUrl = $this->duplicateVinylImage($originalVinyl->pochette);
        }
        
        // Créer une copie du vinyle avec l'utilisateur actuel comme créateur
        $newVinyl = Vinyl::create([
            'vinyl_nom' => $originalVinyl->vinyl_nom,
            'vinyl_titre' => $originalVinyl->vinyl_titre,
            'vinyl_format' => $originalVinyl->vinyl_format,
            'artiste' => $originalVinyl->artiste,
            'label' => $originalVinyl->label,
            'reference' => $originalVinyl->reference,
            'annee' => $originalVinyl->annee,
            'pays' => $originalVinyl->pays,
            'pochette' => $newPochetteUrl, // Utiliser la nouvelle URL d'image
            'tracks' => $originalVinyl->tracks,
            'specificite' => $originalVinyl->specificite,
            'refMatrice' => $originalVinyl->refMatrice,
            'distribution' => $originalVinyl->distribution,
            'edition' => $originalVinyl->edition,
            'anneeOriginal' => $originalVinyl->anneeOriginal,
            'vinyl_nbcollect' => 1,
            'vinyl_alias' => 0,
            'discogs_id' => null, // NULL pour les vinyles manuels
            'discogs_type' => null, // NULL pour les vinyles manuels
            'created_by' => $user->id,
        ]);
        
        // Vérifier si l'utilisateur possède déjà cet exemplaire
        if ($collectionVinyl->user_id === $user->id) {
            // L'utilisateur possède déjà cet exemplaire, on met à jour le vinyl_id
            $collectionVinyl->update([
                'vinyl_id' => $newVinyl->id,
            ]);
            
            // Rediriger vers la page d'édition de l'exemplaire existant
            return redirect()->route('vinyls.edit', $collectionVinyl->id)
                ->with('success', 'Le vinyle a été remplacé par votre copie personnelle. Vous pouvez maintenant modifier les informations.');
        } else {
            // L'utilisateur ne possède pas cet exemplaire (consulte depuis une collection publique)
            // Créer un nouvel exemplaire dans une de ses collections
            $defaultCollection = $user->collections()->first();
            if (!$defaultCollection) {
                return back()->with('error', 'Vous devez avoir au moins une collection pour dupliquer un vinyle.');
            }
            
            $newCollectionVinyl = CollectionVinyl::create([
                'user_id' => $user->id,
                'collection_id' => $defaultCollection->id,
                'vinyl_id' => $newVinyl->id,
                'prix_achat' => $collectionVinyl->prix_achat,
                'annee_achat' => $collectionVinyl->annee_achat,
                'provenance' => $collectionVinyl->provenance,
                'commentaires' => $collectionVinyl->commentaires,
                'note' => $collectionVinyl->note,
                'date_ajout' => Carbon::now(),
            ]);
            
            // Rediriger vers la page d'édition du nouveau vinyle
            return redirect()->route('vinyls.edit', $newCollectionVinyl->id)
                ->with('success', 'Copie du vinyle créée dans votre collection. Vous pouvez maintenant modifier les informations.');
        }
    }
    
    /**
     * Delete a vinyl image from S3
     */
    private function deleteVinylImage($imageUrl)
    {
        try {
            // Vérifier si c'est une URL S3
            if (str_contains($imageUrl, 's3.amazonaws.com') || str_contains($imageUrl, 'digitaloceanspaces.com')) {
                // Extraire le chemin depuis l'URL
                $path = parse_url($imageUrl, PHP_URL_PATH);
                $path = ltrim($path, '/');
                
                // Supprimer le fichier de S3
                if (Storage::disk('s3')->exists($path)) {
                    Storage::disk('s3')->delete($path);
                    \Log::info('Image supprimée de S3', ['path' => $path]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la suppression de l\'image du vinyle: ' . $e->getMessage());
        }
    }
    
    /**
     * Duplicate a vinyl image to create a new copy
     */
    private function duplicateVinylImage($originalUrl)
    {
        try {
            // Si c'est une URL externe (Discogs ou autre), télécharger l'image
            if (filter_var($originalUrl, FILTER_VALIDATE_URL)) {
                $imageContent = @file_get_contents($originalUrl);
                if (!$imageContent) {
                    return null;
                }
                
                // Créer un fichier temporaire
                $tempPath = tempnam(sys_get_temp_dir(), 'vinyl_');
                file_put_contents($tempPath, $imageContent);
                
                // Créer un UploadedFile à partir du contenu
                $file = new \Illuminate\Http\UploadedFile($tempPath, 'image.jpg', 'image/jpeg', null, true);
                
                // Utiliser la méthode existante pour uploader
                $newUrl = $this->uploadVinylImage($file);
                
                // Nettoyer le fichier temporaire
                @unlink($tempPath);
                
                return $newUrl;
            }
            
            // Si c'est déjà une image stockée sur S3, la dupliquer directement
            if (str_contains($originalUrl, 's3.amazonaws.com') || str_contains($originalUrl, 'digitaloceanspaces.com')) {
                // Extraire le chemin depuis l'URL
                $path = parse_url($originalUrl, PHP_URL_PATH);
                $path = ltrim($path, '/');
                
                // Copier le fichier sur S3
                $newPath = "vinyls/" . \Illuminate\Support\Str::uuid() . ".jpg";
                Storage::disk('s3')->copy($path, $newPath);
                
                return Storage::disk('s3')->url($newPath);
            }
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la duplication de l\'image du vinyle: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Store a manually created vinyl (not from Discogs)
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'vinyl_nom' => 'required|string|max:255',
            'artiste' => 'required|string|max:255',
            'vinyl_titre' => 'nullable|string|max:255',
            'label' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'annee' => 'nullable|integer|min:1900|max:' . date('Y'),
            'pochette' => 'nullable|url',
            'pochette_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'discogs_type' => 'nullable|in:release,master',
            'collection_id' => 'required|exists:collections,id',
            'prix_achat' => 'nullable|numeric|min:0',
            'annee_achat' => 'nullable|integer|min:1900|max:' . date('Y'),
            'provenance' => 'nullable|integer',
            'commentaires' => 'nullable|string',
            'note' => 'nullable|integer|min:1|max:10',
        ]);

        $user = Auth::user();

        // Vérifier que la collection appartient à l'utilisateur
        $collection = $user->collections()->findOrFail($request->collection_id);

        // Gérer l'upload d'image si présent
        $pochetteUrl = $request->pochette;
        if ($request->hasFile('pochette_file')) {
            $pochetteUrl = $this->uploadVinylImage($request->file('pochette_file'));
        }

        // Créer le vinyle
        $vinyl = Vinyl::create([
            'vinyl_nom' => $request->vinyl_nom,
            'vinyl_titre' => $request->vinyl_titre ?? $request->vinyl_nom,
            'artiste' => $request->artiste,
            'label' => $request->label,
            'reference' => $request->reference,
            'annee' => $request->annee,
            'pochette' => $pochetteUrl,
            'vinyl_format' => 1, // Format par défaut (LP)
            'vinyl_nbcollect' => 1,
            'vinyl_alias' => 0,
            'discogs_id' => null, // Pas de Discogs ID pour un vinyle manuel
            'discogs_type' => $request->discogs_type ?: null, // Type choisi par l'utilisateur ou null
            'created_by' => $user->id, // Enregistrer le créateur du vinyle manuel
        ]);

        // Associer le vinyle à la collection
        $collectionVinyl = CollectionVinyl::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'vinyl_id' => $vinyl->id,
            'prix_achat' => $request->prix_achat,
            'annee_achat' => $request->annee_achat,
            'provenance' => $request->provenance ?? 0,
            'commentaires' => $request->commentaires,
            'note' => $request->note,
            'date_ajout' => Carbon::now(),
        ]);

        // Mettre à jour la date de modification de la collection
        $collection->update(['collection_date_modif' => Carbon::now()]);

        // Pour une requête Inertia AJAX, utiliser back() est plus approprié
        return back()->with('success', 'Vinyle ajouté manuellement avec succès.');
    }

    /**
     * Store a vinyl from Discogs data
     */
    public function storeFromDiscogs(Request $request, DiscogsService $discogsService)
    {
        $request->validate([
            'discogs_id' => 'required',
            'discogs_data' => 'required|array',
            'collection_id' => 'nullable|exists:collections,id',
            'prix_achat' => 'nullable|numeric|min:0',
            'commentaires' => 'nullable|string',
            'note' => 'nullable|integer|min:1|max:10',
        ]);

        $user = Auth::user();

        // Validation supplémentaire : s'assurer qu'une collection existe
        if ($request->collection_id && !$user->collections()->where('id', $request->collection_id)->exists()) {
            return redirect()->back()->with('error', 'La collection sélectionnée ne vous appartient pas.');
        }

        // Si pas de collection spécifiée, utiliser la première collection ou en créer une
        $collectionId = $request->collection_id;
        if (!$collectionId) {
            $collection = $user->collections()->first();
            if (!$collection) {
                // Créer une collection par défaut
                $collection = Collection::create([
                    'user_id' => $user->id,
                    'collection_nom' => 'Ma Collection',
                    'collection_description' => 'Collection créée automatiquement',
                    'collection_date_creation' => Carbon::now(),
                    'collection_date_modif' => Carbon::now(),
                ]);
            }
            $collectionId = $collection->id;
        } else {
            // Vérifier que la collection appartient à l'utilisateur
            $collection = $user->collections()->findOrFail($collectionId);
        }

        // Extraire les infos Discogs depuis la requête
        $discogsId = $request->discogs_id;
        
        // Déterminer le type à partir des données ou de l'ID
        $discogsType = 'release'; // Par défaut
        if (isset($request->discogs_data['type'])) {
            $discogsType = $request->discogs_data['type'];
        } elseif (preg_match('/^m\d+$/i', $discogsId)) {
            // Si l'ID commence par 'm', c'est un master (format m123456)
            $discogsType = 'master';
            // Retirer le 'm' du début de l'ID
            $discogsId = substr($discogsId, 1);
        }

        // Récupérer les détails complets depuis Discogs pour avoir toutes les informations
        $completeDiscogsData = null;
        if ($discogsType === 'master') {
            $completeDiscogsData = $discogsService->getMaster($discogsId);
        } else {
            $completeDiscogsData = $discogsService->getRelease($discogsId);
        }

        // Utiliser les données complètes si disponibles, sinon fallback sur les données de recherche
        $dataToUse = $completeDiscogsData ?: $request->discogs_data;

        // Convertir les données Discogs
        $vinylData = $discogsService->convertToVinylData($dataToUse, $discogsId, $discogsType);

        // 1. Vérifier si ce vinyle Discogs exact existe déjà (déduplication Discogs uniquement)
        $exactDiscogs = Vinyl::where('discogs_id', $discogsId)
            ->where('discogs_type', $discogsType)
            ->first();

        if ($exactDiscogs) {
            // C'est exactement le même vinyle Discogs, on utilise celui-ci
            $vinyl = $exactDiscogs;

            // Mettre à jour les infos si elles sont meilleures (image manquante, etc.)
            if (!$vinyl->pochette && $vinylData['pochette']) {
                $vinyl->update([
                    'pochette' => $vinylData['pochette'],
                    'reference' => $vinylData['reference'] ?? $vinyl->reference,
                    'label' => $vinylData['label'] ?? $vinyl->label,
                    'annee' => $vinylData['annee'] ?? $vinyl->annee,
                    'discogs_updated_at' => now()
                ]);
            }
        } else {
            // 2. Pas de déduplication avec les anciens vinyles - créer un nouveau vinyle Discogs
            $vinyl = Vinyl::create($vinylData);
        }

        // Vérifier si l'utilisateur a déjà ce vinyle dans cette collection
        $existingInCollection = CollectionVinyl::where('collection_id', $collectionId)
            ->where('vinyl_id', $vinyl->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existingInCollection) {
            return redirect()->back()->with('error', 'Ce vinyle est déjà dans votre collection.');
        }

        // Créer l'association avec la collection
        CollectionVinyl::create([
            'collection_id' => $collectionId,
            'vinyl_id' => $vinyl->id,
            'user_id' => $user->id,
            'prix_achat' => $request->prix_achat,
            'commentaires' => $request->commentaires,
            'note' => $request->note,
            'date_ajout' => Carbon::now(),
        ]);

        // Mettre à jour la date de modification de la collection
        $collection->update([
            'collection_date_modif' => Carbon::now()
        ]);

        // Pour une requête Inertia AJAX depuis une modal, utiliser back()
        return back()->with('success', 'Vinyle ajouté avec succès depuis Discogs.');
    }

    /**
     * Upload vinyl image and return the URL
     */
    private function uploadVinylImage($file)
    {
        try {
            // Compression et redimensionnement de l'image
            $compressedImage = $this->compressImage($file);

            // Générer un nom unique
            $imagePath = "vinyls/" . Str::uuid() . ".jpg";

            // Upload vers S3 (ou le disque configuré)
            $uploaded = Storage::disk('s3')->put($imagePath, $compressedImage, [
                'ContentType' => 'image/jpeg',
                'CacheControl' => 'max-age=31536000',
                'visibility' => 'public',
            ]);

            if ($uploaded) {
                return Storage::disk('s3')->url($imagePath);
            }
        } catch (\Exception $e) {
            \Log::error('Erreur upload image vinyle: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Compress and resize image (inspired by ImportVinylImagesCommand)
     */
    private function compressImage($file, $quality = 80)
    {
        try {
            $image = imagecreatefromstring($file->getContent());
            if (!$image) {
                return $file->getContent();
            }

            // Obtenir les dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Redimensionner si trop grande (max 1200x1200 pour garder une bonne qualité)
            $maxSize = 1200;
            if ($width > $maxSize || $height > $maxSize) {
                $ratio = min($maxSize / $width, $maxSize / $height);
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);

                // Améliorer la qualité du redimensionnement
                imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

                imagedestroy($image);
                $image = $resized;
            }

            ob_start();
            imagejpeg($image, null, $quality);
            $compressedData = ob_get_contents();
            ob_end_clean();

            imagedestroy($image);

            // Vérifier que la compression est bénéfique
            $originalSize = strlen($file->getContent());
            $compressedSize = strlen($compressedData);

            // Si la compression augmente la taille de plus de 10%, garder l'original
            if ($compressedSize > $originalSize * 1.1) {
                return $file->getContent();
            }

            return $compressedData;

        } catch (\Exception $e) {
            return $file->getContent(); // Retourne l'original en cas d'erreur
        }
    }

    /**
     * Add an existing vinyl to a user's collection
     */
    public function addToCollection(Request $request)
    {
        $request->validate([
            'vinyl_id' => 'required|exists:vinyls,id',
            'collection_id' => 'required|exists:collections,id'
        ]);

        $user = Auth::user();
        $collection = $user->collections()->findOrFail($request->collection_id);

        // Vérifier si le vinyle n'est pas déjà dans cette collection
        $exists = CollectionVinyl::where('user_id', $user->id)
            ->where('collection_id', $collection->id)
            ->where('vinyl_id', $request->vinyl_id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Ce vinyle est déjà dans cette collection.');
        }

        // Ajouter le vinyle à la collection
        CollectionVinyl::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'vinyl_id' => $request->vinyl_id,
            'date_ajout' => Carbon::now(),
        ]);

        // Mettre à jour la date de modification de la collection
        $collection->update([
            'collection_date_modif' => Carbon::now()
        ]);

        return back()->with('success', 'Vinyle ajouté à votre collection avec succès.');
    }
}
