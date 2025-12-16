<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Collection;
use App\Models\Vinyl;
use App\Models\CollectionVinyl;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Exports\CollectionExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\ImageHelper;

class CollectionController extends Controller
{
    /**
     * Display a listing of the user's collections
     */
    public function index()
    {
        $user = Auth::user();
        
        $collections = $user->collections()
            ->withCount('collectionVinyls as vinyls_count')
            ->orderBy('collection_date_modif', 'desc')
            ->get();

        return Inertia::render('Collections/Index', [
            'collections' => $collections
        ]);
    }

    /**
     * Show the form for creating a new collection
     */
    public function create()
    {
        return Inertia::render('Collections/Create');
    }

    /**
     * Store a newly created collection in storage
     */
    public function store(Request $request)
    {
        $request->validate([
            'collection_nom' => 'required|string|max:255',
            'collection_commentaires' => 'nullable|string',
            'visibility' => 'required|in:public,private,friends',
        ]);

        $user = Auth::user();
        
        $collection = $user->collections()->create([
            'collection_nom' => $request->collection_nom,
            'collection_commentaires' => $request->collection_commentaires,
            'visibility' => $request->visibility,
            'collection_date_crea' => Carbon::now(),
            'collection_date_modif' => Carbon::now(),
            'ordre' => $user->collections()->count() + 1,
        ]);

        return redirect()->route('collections.show', $collection)->with('success', 'Collection créée avec succès.');
    }

    /**
     * Display the specified collection
     */
    public function show(Collection $collection, Request $request)
    {
        // Vérifier que l'utilisateur est propriétaire de la collection
        if ($collection->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cette collection.');
        }

        // Récupérer les paramètres de recherche, tri et pagination
        $search = $request->get('search', '');
        $sortBy = $request->get('sort', 'date_ajout');
        $sortOrder = $request->get('order', 'desc');
        $perPage = $request->get('per_page', 20); // Par défaut 20 items

        // Récupérer les filtres avancés
        $filterTitre = $request->get('filter_titre', '');
        $filterArtiste = $request->get('filter_artiste', '');
        $filterAnneeMin = $request->get('filter_annee_min', '');
        $filterAnneeMax = $request->get('filter_annee_max', '');
        $filterGenre = $request->get('filter_genre', '');
        $filterLabel = $request->get('filter_label', '');
        $filterFormat = $request->get('filter_format', '');
        $filterPays = $request->get('filter_pays', '');
        $filterCommentaires = $request->get('filter_commentaires', '');
        
        // Valider que per_page est dans les valeurs autorisées
        if (!in_array($perPage, [20, 50, 100, 1000])) {
            $perPage = 20;
        }

        // Construire la requête avec recherche et tri
        $query = $collection->collectionVinyls()->with('vinyl');

        // Appliquer la recherche si un terme est fourni
        if ($search) {
            $searchTerm = $search;
            $query->whereHas('vinyl', function($q) use ($searchTerm) {
                $q->where(function($query) use ($searchTerm) {
                    $query->whereRaw('vinyl_nom ILIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('artiste ILIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('vinyl_titre ILIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('label ILIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('reference ILIKE ?', ["%{$searchTerm}%"]);

                    // Ajouter la condition sur l'année seulement si c'est numérique
                    if (is_numeric($searchTerm)) {
                        $query->orWhere('annee', '=', intval($searchTerm));
                    }
                });
            });
        }

        // Appliquer les filtres avancés
        if ($filterTitre) {
            $query->whereHas('vinyl', function($q) use ($filterTitre) {
                $q->whereRaw('vinyl_nom ILIKE ?', ["%{$filterTitre}%"]);
            });
        }

        if ($filterArtiste) {
            $query->whereHas('vinyl', function($q) use ($filterArtiste) {
                $q->whereRaw('artiste ILIKE ?', ["%{$filterArtiste}%"]);
            });
        }

        if ($filterAnneeMin) {
            $query->whereHas('vinyl', function($q) use ($filterAnneeMin) {
                $q->where('annee', '>=', intval($filterAnneeMin));
            });
        }

        if ($filterAnneeMax) {
            $query->whereHas('vinyl', function($q) use ($filterAnneeMax) {
                $q->where('annee', '<=', intval($filterAnneeMax));
            });
        }

        if ($filterGenre) {
            $query->whereHas('vinyl', function($q) use ($filterGenre) {
                $q->where('genre', $filterGenre);
            });
        }

        if ($filterLabel) {
            $query->whereHas('vinyl', function($q) use ($filterLabel) {
                $q->where('label', $filterLabel);
            });
        }

        if ($filterFormat) {
            $query->whereHas('vinyl', function($q) use ($filterFormat) {
                $q->where('vinyl_format', $filterFormat);
            });
        }

        if ($filterPays) {
            $query->whereHas('vinyl', function($q) use ($filterPays) {
                $q->where('pays', $filterPays);
            });
        }

        if ($filterCommentaires) {
            $query->whereRaw('commentaires ILIKE ?', ["%{$filterCommentaires}%"]);
        }

        // Appliquer le tri en utilisant des sous-requêtes pour préserver les relations
        switch ($sortBy) {
            // Tris sur les champs de vinyls
            case 'artiste':
            case 'vinyl_titre':
            case 'vinyl_nom':
            case 'annee':
                $query->orderBy(
                    \DB::table('vinyls')
                        ->select($sortBy)
                        ->whereColumn('vinyls.id', 'collection_vinyls.vinyl_id')
                        ->limit(1),
                    $sortOrder
                );
                break;

            // Tris sur les champs de collection_vinyls
            case 'annee_achat':
            case 'prix_achat':
            case 'note':
            case 'updated_at':
                $query->orderBy('collection_vinyls.' . $sortBy, $sortOrder);
                break;

            case 'date_ajout':
            default:
                $query->orderBy('collection_vinyls.date_ajout', $sortOrder);
                break;
        }

        // Paginer les résultats
        $collectionVinylsPaginated = $query->paginate($perPage)->withQueryString();

        // Ajouter les informations de permissions pour chaque vinyl
        $user = Auth::user();
        $collectionVinylsPaginated->getCollection()->transform(function ($collectionVinyl) use ($user, $collection) {
            $vinyl = $collectionVinyl->vinyl;
            
            // S'assurer que collection_id est bien présent
            $collectionVinyl->collection_id = $collectionVinyl->collection_id ?? $collection->id;
            
            // L'utilisateur peut toujours éditer son exemplaire
            $collectionVinyl->can_edit_instance = true;
            
            // Déterminer si l'utilisateur peut éditer les infos du vinyle
            $canEditVinyl = false;
            if ($vinyl) {
                // Personne ne peut éditer un vinyle Discogs
                if ($vinyl->discogs_id && $vinyl->discogs_type !== 'manual') {
                    $canEditVinyl = false;
                }
                // Pour les vinyles manuels, seul le créateur peut éditer
                elseif ($vinyl->discogs_type === 'manual' || !$vinyl->discogs_id) {
                    $canEditVinyl = $vinyl->created_by === $user->id;
                    // Charger le créateur pour l'afficher si nécessaire
                    if ($vinyl->created_by && !$canEditVinyl) {
                        $vinyl->load('creator');
                    }
                }
            }
            
            $collectionVinyl->can_edit_vinyl = $canEditVinyl;
            // Rétro-compatibilité : can_edit = true si on peut éditer l'exemplaire OU le vinyle
            $collectionVinyl->can_edit = true; // On peut toujours éditer au moins l'exemplaire

            // On ne vérifie plus l'accessibilité ici pour éviter les problèmes de performance

            return $collectionVinyl;
        });

        // Créer un objet collection simplifié avec les données paginées
        $collectionData = $collection->toArray();
        $collectionData['collection_vinyls'] = $collectionVinylsPaginated->items();

        // Récupérer toutes les collections de l'utilisateur pour les dropdowns
        $userCollections = Auth::user()->collections()
            ->orderBy('collection_nom')
            ->get(['id', 'collection_nom']);

        return Inertia::render('Collections/Show', [
            'collection' => $collectionData,
            'pagination' => [
                'data' => $collectionVinylsPaginated->items(),
                'current_page' => $collectionVinylsPaginated->currentPage(),
                'last_page' => $collectionVinylsPaginated->lastPage(),
                'per_page' => $collectionVinylsPaginated->perPage(),
                'total' => $collectionVinylsPaginated->total(),
                'from' => $collectionVinylsPaginated->firstItem(),
                'to' => $collectionVinylsPaginated->lastItem(),
                'links' => $collectionVinylsPaginated->links()->elements,
            ],
            'userCollections' => $userCollections,
            'filters' => [
                'search' => $search,
                'sort' => $sortBy,
                'order' => $sortOrder,
                'per_page' => $perPage
            ]
        ]);
    }

    /**
     * Show the form for editing the specified collection
     */
    public function edit(Collection $collection)
    {
        // Vérifier que l'utilisateur est propriétaire de la collection
        if ($collection->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cette collection.');
        }

        return Inertia::render('Collections/Edit', [
            'collection' => $collection
        ]);
    }

    /**
     * Update the specified collection in storage
     */
    public function update(Request $request, Collection $collection)
    {
        // Vérifier que l'utilisateur est propriétaire de la collection
        if ($collection->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cette collection.');
        }

        $request->validate([
            'collection_nom' => 'required|string|max:255',
            'collection_commentaires' => 'nullable|string',
            'visibility' => 'required|in:public,private,friends',
        ]);

        $collection->update([
            'collection_nom' => $request->collection_nom,
            'collection_commentaires' => $request->collection_commentaires,
            'visibility' => $request->visibility,
            'collection_date_modif' => Carbon::now(),
        ]);

        return redirect()->route('collections.show', $collection)->with('success', 'Collection mise à jour avec succès.');
    }

    /**
     * Get deletion statistics for a collection
     */
    public function getDeletionStats(Collection $collection)
    {
        // Vérifier que l'utilisateur est propriétaire de la collection
        if ($collection->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cette collection.');
        }

        // Récupérer tous les vinyles de cette collection
        $vinylIds = $collection->collectionVinyls()->pluck('vinyl_id')->unique()->toArray();
        $totalVinyls = $collection->collectionVinyls()->count();

        // Compter combien de vinyles deviendront orphelins - optimisation avec groupBy
        $orphanVinyls = 0;
        if (!empty($vinylIds)) {
            $vinylCounts = CollectionVinyl::whereIn('vinyl_id', $vinylIds)
                ->groupBy('vinyl_id')
                ->selectRaw('vinyl_id, count(*) as count')
                ->pluck('count', 'vinyl_id');

            foreach ($vinylIds as $vinylId) {
                if (($vinylCounts[$vinylId] ?? 0) === 1) {
                    $orphanVinyls++;
                }
            }
        }

        return response()->json([
            'total_vinyls' => $totalVinyls,
            'orphan_vinyls' => $orphanVinyls,
            'collection_name' => $collection->collection_nom,
        ]);
    }

    /**
     * Remove the specified collection from storage
     * La suppression en cascade des vinyls orphelins et des images S3 est gérée dans le modèle Collection
     */
    public function destroy(Collection $collection)
    {
        // Vérifier que l'utilisateur est propriétaire de la collection
        if ($collection->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cette collection.');
        }

        $collectionName = $collection->collection_nom;
        $collection->delete();

        return redirect()->route('collections.index')->with('success', "Collection \"{$collectionName}\" supprimée avec succès.");
    }

    /**
     * Remove a vinyl from a collection
     */
    public function removeVinyl(Collection $collection, CollectionVinyl $collectionVinyl)
    {
        // Vérifier que l'utilisateur est propriétaire de la collection
        if ($collection->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cette collection.');
        }

        // Vérifier que le vinyle appartient à cette collection et à cet utilisateur
        if ($collectionVinyl->collection_id !== $collection->id || $collectionVinyl->user_id !== Auth::id()) {
            abort(403, 'Ce vinyle n\'appartient pas à cette collection.');
        }

        $vinyl = $collectionVinyl->vinyl;
        
        // Supprimer l'exemplaire de la collection
        $collectionVinyl->delete();

        // Vérifier si d'autres utilisateurs possèdent ce vinyle
        $remainingCount = CollectionVinyl::where('vinyl_id', $vinyl->id)->count();
        
        if ($remainingCount === 0) {
            // Personne d'autre ne possède ce vinyle, on peut le supprimer complètement

            // Supprimer l'image du storage (la méthode vérifie si c'est une image S3)
            ImageHelper::deleteVinylImage($vinyl->pochette);

            // Supprimer le vinyle de la base de données
            $vinyl->delete();
        }

        // Mettre à jour la date de modification de la collection
        $collection->update([
            'collection_date_modif' => Carbon::now()
        ]);

        return redirect()->route('collections.show', $collection)->with('success', 'Vinyle supprimé de la collection avec succès.');
    }

    /**
     * Move a vinyl to another collection
     */
    public function moveVinyl(Collection $collection, CollectionVinyl $collectionVinyl, Request $request)
    {
        $request->validate([
            'target_collection_id' => 'required|exists:collections,id'
        ]);

        $user = Auth::user();
        
        // Vérifier que l'utilisateur est propriétaire de la collection source
        if ($collection->user_id !== $user->id) {
            abort(403, 'Vous n\'avez pas accès à cette collection.');
        }

        // Vérifier que le vinyle appartient à cette collection et à cet utilisateur
        if ($collectionVinyl->collection_id !== $collection->id || $collectionVinyl->user_id !== $user->id) {
            abort(403, 'Ce vinyle n\'appartient pas à cette collection.');
        }

        // Vérifier que la collection cible appartient à l'utilisateur
        $targetCollection = $user->collections()->findOrFail($request->target_collection_id);

        // Vérifier que le vinyle n'est pas déjà dans la collection cible
        $existsInTarget = CollectionVinyl::where('collection_id', $targetCollection->id)
            ->where('vinyl_id', $collectionVinyl->vinyl_id)
            ->where('user_id', $user->id)
            ->exists();

        if ($existsInTarget) {
            return redirect()->back()->with('error', 'Ce vinyle est déjà dans la collection de destination.');
        }

        // Déplacer le vinyle
        $collectionVinyl->update([
            'collection_id' => $targetCollection->id
        ]);

        // Mettre à jour les dates de modification des deux collections
        $collection->update(['collection_date_modif' => Carbon::now()]);
        $targetCollection->update(['collection_date_modif' => Carbon::now()]);

        return redirect()->route('collections.show', $collection)->with('success', 'Vinyle déplacé vers "' . $targetCollection->collection_nom . '" avec succès.');
    }

    /**
     * Export collection to Excel
     */
    public function export(Request $request, Collection $collection)
    {
        // Vérifier que l'utilisateur est propriétaire de la collection
        if ($collection->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cette collection.');
        }

        // Récupérer les paramètres d'export
        $columns = $request->has('columns')
            ? explode(',', $request->get('columns'))
            : [];

        // Récupérer les filtres
        $filters = [];
        if ($request->has('date_ajout_from')) {
            $filters['date_ajout_from'] = $request->get('date_ajout_from');
        }
        if ($request->has('date_ajout_to')) {
            $filters['date_ajout_to'] = $request->get('date_ajout_to');
        }
        if ($request->has('annee_achat_min')) {
            $filters['annee_achat_min'] = $request->get('annee_achat_min');
        }
        if ($request->has('annee_achat_max')) {
            $filters['annee_achat_max'] = $request->get('annee_achat_max');
        }
        if ($request->has('annee_sortie_min')) {
            $filters['annee_sortie_min'] = $request->get('annee_sortie_min');
        }
        if ($request->has('annee_sortie_max')) {
            $filters['annee_sortie_max'] = $request->get('annee_sortie_max');
        }
        if ($request->has('prix_min')) {
            $filters['prix_min'] = $request->get('prix_min');
        }
        if ($request->has('prix_max')) {
            $filters['prix_max'] = $request->get('prix_max');
        }
        if ($request->has('note_min')) {
            $filters['note_min'] = $request->get('note_min');
        }
        if ($request->has('provenance')) {
            $filters['provenance'] = $request->get('provenance');
        }

        // Récupérer les options de tri
        $sortBy = $request->get('sort_by', 'date_ajout');
        $sortOrder = $request->get('sort_order', 'desc');

        // Créer le nom du fichier avec un suffixe si des filtres sont appliqués
        $fileName = 'collection-' . \Str::slug($collection->collection_nom);
        if (!empty($filters)) {
            $fileName .= '-filtre';
        }
        $fileName .= '-' . date('Y-m-d') . '.xlsx';

        return Excel::download(
            new CollectionExport($collection, $columns, $filters, $sortBy, $sortOrder),
            $fileName
        );
    }

    /**
     * API pour vérifier l'accessibilité d'une image
     */
    public function checkImageAccessibility(Request $request, CollectionVinyl $collectionVinyl)
    {
        // On ne bloque pas si l'utilisateur n'est pas owner
        // On vérifie juste l'accessibilité de l'image
        // La décision d'autoriser le changement se fait côté frontend et lors de l'update

        $collectionVinyl->load('vinyl');
        $vinyl = $collectionVinyl->vinyl;

        if (!$vinyl || !$vinyl->pochette) {
            return response()->json(['accessible' => false]);
        }

        $imageUrl = $vinyl->pochette;

        try {
            // Timeout réduit à 3 secondes - si ça met plus de temps, c'est considéré comme inaccessible
            $response = Http::timeout(3)->head($imageUrl);
            $isAccessible = $response->successful() && str_starts_with($response->header('content-type') ?? '', 'image/');

            return response()->json([
                'accessible' => $isAccessible,
                'is_owner' => $collectionVinyl->user_id === Auth::id()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'accessible' => false,
                'is_owner' => $collectionVinyl->user_id === Auth::id()
            ]);
        }
    }
}