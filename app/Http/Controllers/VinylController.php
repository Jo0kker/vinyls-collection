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
    public function show(CollectionVinyl $collectionVinyl)
    {
        // Vérifier que l'utilisateur est propriétaire
        if ($collectionVinyl->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à ce vinyle.');
        }

        $collectionVinyl->load(['vinyl', 'collection']);

        return Inertia::render('Vinyls/Show', [
            'collectionVinyl' => $collectionVinyl
        ]);
    }

    /**
     * Show the form for editing the specified vinyl
     */
    public function edit(CollectionVinyl $collectionVinyl)
    {
        // Vérifier que l'utilisateur est propriétaire
        if ($collectionVinyl->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à ce vinyle.');
        }

        $user = Auth::user();
        $collections = $user->collections()->orderBy('collection_nom')->get();
        $collectionVinyl->load(['vinyl', 'collection']);

        return Inertia::render('Vinyls/Edit', [
            'collectionVinyl' => $collectionVinyl,
            'collections' => $collections
        ]);
    }

    /**
     * Update the specified vinyl in storage
     */
    public function update(Request $request, CollectionVinyl $collectionVinyl)
    {
        // Vérifier que l'utilisateur est propriétaire
        if ($collectionVinyl->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à ce vinyle.');
        }

        $vinyl = $collectionVinyl->vinyl;
        $isManualVinyl = $vinyl->discogs_type === 'manual' || is_null($vinyl->discogs_id);

        // Validation selon le type de vinyle
        if ($isManualVinyl) {
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

        // Mettre à jour le vinyle selon son type
        if ($isManualVinyl) {
            // Vinyle manuel : mise à jour complète
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
        } else {
            // Vinyle Discogs : aucune modification des données du vinyle (y compris l'image)
            // Seuls les champs pivot peuvent être modifiés
        }

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
        $collectionVinyl->delete();

        // Mettre à jour la date de modification de la collection
        $collection->update([
            'collection_date_modif' => Carbon::now()
        ]);

        return back()->with('success', 'Vinyle supprimé avec succès.');
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
        $discogsType = isset($request->discogs_data['type']) ? $request->discogs_data['type'] : 'release';

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
}
