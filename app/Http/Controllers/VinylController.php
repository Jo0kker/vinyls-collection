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
            ->get()
            ->map(function ($collectionVinyl) {
                return [
                    'id' => $collectionVinyl->id,
                    'vinyl_nom' => $collectionVinyl->vinyl->vinyl_nom ?? 'Nom inconnu',
                    'vinyl_titre' => $collectionVinyl->vinyl->vinyl_titre ?? 'Titre inconnu',
                    'collection_nom' => $collectionVinyl->collection->collection_nom ?? 'Collection inconnue',
                    'date_ajout' => $collectionVinyl->date_ajout,
                    'prix_achat' => $collectionVinyl->prix_achat,
                    'note' => $collectionVinyl->note,
                    'commentaires' => $collectionVinyl->commentaires,
                ];
            });

        return Inertia::render('Vinyls/Index', [
            'vinyls' => $vinyls
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

        return redirect()->route('vinyls.index')->with('success', 'Vinyle ajouté avec succès.');
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

        // Mettre à jour le vinyle
        $collectionVinyl->vinyl->update([
            'vinyl_nom' => $request->vinyl_nom,
            'vinyl_titre' => $request->vinyl_titre,
            'vinyl_format' => $request->vinyl_format,
        ]);

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

        return redirect()->route('vinyls.index')->with('success', 'Vinyle mis à jour avec succès.');
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

        return redirect()->route('vinyls.index')->with('success', 'Vinyle supprimé avec succès.');
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
        
        // Convertir les données Discogs
        $vinylData = $discogsService->convertToVinylData($request->discogs_data, $discogsId, $discogsType);

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

        // Si on vient d'une collection spécifique, retourner à cette collection
        if ($collectionId) {
            return redirect()->route('collections.show', $collectionId)->with('success', 'Vinyle ajouté avec succès depuis Discogs.');
        }
        
        return redirect()->route('dashboard')->with('success', 'Vinyle ajouté avec succès depuis Discogs.');
    }
}