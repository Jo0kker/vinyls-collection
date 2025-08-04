<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Collection;
use App\Models\Vinyl;
use App\Models\CollectionVinyl;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // Récupérer les paramètres de recherche et tri
        $search = $request->get('search', '');
        $sortBy = $request->get('sort', 'date_ajout');
        $sortOrder = $request->get('order', 'desc');

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

        // Appliquer le tri en utilisant des sous-requêtes pour préserver les relations
        switch ($sortBy) {
            case 'nom':
                $query->orderBy(
                    \DB::table('vinyls')
                        ->select('vinyl_nom')
                        ->whereColumn('vinyls.id', 'collection_vinyls.vinyl_id')
                        ->limit(1),
                    $sortOrder
                );
                break;
            case 'artiste':
                $query->orderBy(
                    \DB::table('vinyls')
                        ->select('artiste')
                        ->whereColumn('vinyls.id', 'collection_vinyls.vinyl_id')
                        ->limit(1),
                    $sortOrder
                );
                break;
            case 'date_ajout':
            default:
                $query->orderBy('collection_vinyls.date_ajout', $sortOrder);
                break;
        }

        $collectionVinyls = $query->get();

        // Reconstituer l'objet collection avec les vinyles filtrés/triés
        $collection->setRelation('collectionVinyls', $collectionVinyls);

        // Récupérer toutes les collections de l'utilisateur pour le dropdown de déplacement
        $userCollections = Auth::user()->collections()
            ->where('id', '!=', $collection->id)
            ->orderBy('collection_nom')
            ->get(['id', 'collection_nom']);

        return Inertia::render('Collections/Show', [
            'collection' => $collection,
            'userCollections' => $userCollections,
            'filters' => [
                'search' => $search,
                'sort' => $sortBy,
                'order' => $sortOrder
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
     * Remove the specified collection from storage
     */
    public function destroy(Collection $collection)
    {
        // Vérifier que l'utilisateur est propriétaire de la collection
        if ($collection->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cette collection.');
        }

        $collection->delete();

        return redirect()->route('collections.index')->with('success', 'Collection supprimée avec succès.');
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

        $collectionVinyl->delete();

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
}