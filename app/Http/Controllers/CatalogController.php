<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Vinyl;
use App\Models\CollectionVinyl;
use App\Models\Collection;
use App\Models\User;

class CatalogController extends Controller
{
    /**
     * Display the global vinyl catalog
     */
    public function index(Request $request)
    {
        // Paramètres de filtrage et tri
        $search = $request->get('search', '');
        $sortBy = $request->get('sort', 'vinyl_nom');
        $sortOrder = $request->get('order', 'asc');
        $perPage = $request->get('per_page', 30);
        $letter = $request->get('letter', '');

        // Filtres avancés
        $filterArtiste = $request->get('artiste', '');
        $filterLabel = $request->get('label', '');
        $filterFormat = $request->get('format', '');
        $filterYearFrom = $request->get('year_from', '');
        $filterYearTo = $request->get('year_to', '');

        // Construire la requête de base sur les vinyles
        $query = Vinyl::query();

        // Filtre par lettre
        if ($letter) {
            if ($letter === '#') {
                $query->whereRaw("vinyl_nom NOT SIMILAR TO '[A-Za-z]%'");
            } else {
                $query->whereRaw('UPPER(LEFT(vinyl_nom, 1)) = ?', [strtoupper($letter)]);
            }
        }

        // Recherche textuelle
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('vinyl_nom', 'ILIKE', "%{$search}%")
                  ->orWhere('artiste', 'ILIKE', "%{$search}%")
                  ->orWhere('label', 'ILIKE', "%{$search}%");
            });
        }

        // Filtres avancés
        if ($filterArtiste) {
            $query->where('artiste', 'ILIKE', "%{$filterArtiste}%");
        }

        if ($filterLabel) {
            $query->where('label', 'ILIKE', "%{$filterLabel}%");
        }

        if ($filterFormat) {
            $query->where('format', 'ILIKE', "%{$filterFormat}%");
        }

        if ($filterYearFrom) {
            $query->where('annee', '>=', (int)$filterYearFrom);
        }

        if ($filterYearTo) {
            $query->where('annee', '<=', (int)$filterYearTo);
        }

        // Appliquer le tri
        switch ($sortBy) {
            case 'artiste':
                $query->orderBy('artiste', $sortOrder);
                break;
            case 'annee':
                $query->orderBy('annee', $sortOrder);
                break;
            case 'vinyl_nom':
            case 'titre':
            default:
                $query->orderBy('vinyl_nom', $sortOrder);
                break;
        }

        // Ajouter le compteur de collectionneurs distincts pour chaque vinyle
        // On compte les utilisateurs uniques qui possèdent ce vinyle dans leurs collections publiques
        $publicUserIds = User::where('profile_public', true)
            ->whereNotNull('email_verified_at')
            ->pluck('id');

        $publicCollectionIds = Collection::whereIn('user_id', $publicUserIds)
            ->where('visibility', 'public')
            ->pluck('id');

        $query->addSelect([
            'collectors_count' => CollectionVinyl::selectRaw('COUNT(DISTINCT collections.user_id)')
                ->join('collections', 'collection_vinyls.collection_id', '=', 'collections.id')
                ->whereColumn('collection_vinyls.vinyl_id', 'vinyls.id')
                ->whereIn('collection_vinyls.collection_id', $publicCollectionIds)
        ]);

        $vinyls = $query->paginate((int)$perPage)->appends($request->query());

        // Statistiques globales
        $totalVinyls = Vinyl::count();
        $totalCollectors = User::where('profile_public', true)
            ->whereNotNull('email_verified_at')
            ->count();
        $totalCollections = Collection::where('visibility', 'public')
            ->whereHas('user', function($q) {
                $q->where('profile_public', true)
                  ->whereNotNull('email_verified_at');
            })
            ->count();

        return Inertia::render('Catalog/Index', [
            'vinyls' => $vinyls,
            'stats' => [
                'totalVinyls' => $totalVinyls,
                'totalCollectors' => $totalCollectors,
                'totalCollections' => $totalCollections,
            ],
            'filters' => [
                'search' => $search,
                'sort' => $sortBy,
                'order' => $sortOrder,
                'per_page' => (int)$perPage,
                'letter' => $letter,
                'artiste' => $filterArtiste,
                'label' => $filterLabel,
                'format' => $filterFormat,
                'year_from' => $filterYearFrom,
                'year_to' => $filterYearTo,
            ]
        ]);
    }
}
