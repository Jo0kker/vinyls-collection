<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Collection;
use App\Models\Vinyl;
use App\Models\CollectionVinyl;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with user's collections and statistics
     */
    public function index()
    {
        $user = Auth::user();

        // Récupérer les statistiques
        $stats = [
            'collections_count' => $user->collections()->count(),
            'vinyls_count' => $user->vinylCollections()->count(),
            'total_value' => $user->vinylCollections()->sum('prix_achat') ?? 0,
            'year_additions' => $user->vinylCollections()
                ->whereYear('date_ajout', Carbon::now()->year)
                ->count()
        ];

        // Récupérer les collections récentes (5 dernières)
        $recentCollections = $user->collections()
            ->withCount('collectionVinyls as vinyls_count')
            ->orderBy('collection_date_modif', 'desc')
            ->take(5)
            ->get();

        // Récupérer les vinyles récents (5 derniers)
        $recentVinyls = $user->vinylCollections()
            ->with(['vinyl', 'collection'])
            ->orderBy('date_ajout', 'desc')
            ->take(5)
            ->get()
            ->map(function ($collectionVinyl) {
                return [
                    'id' => $collectionVinyl->id,
                    'vinyl_nom' => $collectionVinyl->vinyl->vinyl_nom ?? 'Nom inconnu',
                    'vinyl_titre' => $collectionVinyl->vinyl->vinyl_titre ?? 'Titre inconnu',
                    'collection_nom' => $collectionVinyl->collection->collection_nom ?? 'Collection inconnue',
                    'artiste' => $collectionVinyl->vinyl->artiste,
                    'pochette' => $collectionVinyl->vinyl->pochette,
                    'date_ajout' => $collectionVinyl->date_ajout,
                    'prix_achat' => $collectionVinyl->prix_achat,
                    'note' => $collectionVinyl->note,
                ];
            });

        // Récupérer toutes les collections pour le sélecteur
        $allCollections = $user->collections()
            ->orderBy('collection_nom')
            ->get(['id', 'collection_nom']);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentCollections' => $recentCollections,
            'recentVinyls' => $recentVinyls,
            'allCollections' => $allCollections
        ]);
    }

    /**
     * Search across all user's collections
     */
    public function search(Request $request)
    {
        $search = $request->get('q', '');
        
        if (empty($search)) {
            return response()->json(['results' => []]);
        }

        $user = Auth::user();
        $searchTerm = $search;

        $results = CollectionVinyl::with(['vinyl', 'collection'])
            ->where('user_id', $user->id)
            ->whereHas('vinyl', function($q) use ($searchTerm) {
                $q->where(function($query) use ($searchTerm) {
                    $query->whereRaw('vinyl_nom ILIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('artiste ILIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('vinyl_titre ILIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('label ILIKE ?', ["%{$searchTerm}%"])
                          ->orWhereRaw('reference ILIKE ?', ["%{$searchTerm}%"]);
                    
                    if (is_numeric($searchTerm)) {
                        $query->orWhere('annee', '=', intval($searchTerm));
                    }
                });
            })
            ->orderBy('date_ajout', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($collectionVinyl) {
                return [
                    'id' => $collectionVinyl->id,
                    'vinyl_id' => $collectionVinyl->vinyl_id,
                    'vinyl_nom' => $collectionVinyl->vinyl->vinyl_nom ?? 'Nom inconnu',
                    'vinyl_titre' => $collectionVinyl->vinyl->vinyl_titre ?? 'Titre inconnu',
                    'artiste' => $collectionVinyl->vinyl->artiste,
                    'pochette' => $collectionVinyl->vinyl->pochette,
                    'annee' => $collectionVinyl->vinyl->annee,
                    'label' => $collectionVinyl->vinyl->label,
                    'collection_id' => $collectionVinyl->collection_id,
                    'collection_nom' => $collectionVinyl->collection->collection_nom ?? 'Collection inconnue',
                    'prix_achat' => $collectionVinyl->prix_achat,
                    'note' => $collectionVinyl->note,
                ];
            });

        return response()->json(['results' => $results]);
    }
}
