<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Collection;
use App\Models\Vinyl;
use App\Models\CollectionVinyl;
use Illuminate\Support\Facades\Auth;
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
        ]);

        $user = Auth::user();
        
        $collection = $user->collections()->create([
            'collection_nom' => $request->collection_nom,
            'collection_commentaires' => $request->collection_commentaires,
            'collection_date_crea' => Carbon::now(),
            'collection_date_modif' => Carbon::now(),
            'ordre' => $user->collections()->count() + 1,
        ]);

        return redirect()->route('collections.show', $collection)->with('success', 'Collection créée avec succès.');
    }

    /**
     * Display the specified collection
     */
    public function show(Collection $collection)
    {
        // Vérifier que l'utilisateur est propriétaire de la collection
        if ($collection->user_id !== Auth::id()) {
            abort(403, 'Vous n\'avez pas accès à cette collection.');
        }

        $collection->load(['collectionVinyls.vinyl']);

        return Inertia::render('Collections/Show', [
            'collection' => $collection
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
        ]);

        $collection->update([
            'collection_nom' => $request->collection_nom,
            'collection_commentaires' => $request->collection_commentaires,
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
}