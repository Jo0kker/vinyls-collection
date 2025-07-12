<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Collection;
use App\Models\Vinyl;
use App\Models\CollectionVinyl;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
}
