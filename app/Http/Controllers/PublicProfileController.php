<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Collection;

class PublicProfileController extends Controller
{
    /**
     * Display a listing of all public profiles
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $sortBy = $request->get('sort_by', 'vinyl_count');

        $query = User::where('profile_public', true)
                    ->whereNotNull('email_verified_at')
                    ->select(['id', 'name', 'avatar', 'location', 'bio', 'created_at'])
                    ->withCount(['vinylCollections as vinyl_count'])
                    ->withCount(['collections as public_collections_count' => function ($query) {
                        $query->where('visibility', 'public');
                    }]);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%")
                  ->orWhere('bio', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting with computed counts
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'collection_count':
                $query->orderByDesc('public_collections_count');
                break;
            case 'recent':
                $query->orderByDesc('created_at');
                break;
            case 'vinyl_count':
            default:
                $query->orderByDesc('vinyl_count');
                break;
        }

        $users = $query->paginate(24)->appends($request->query());

        return Inertia::render('Profiles/Index', [
            'users' => $users,
            'search' => $search,
            'sortBy' => $sortBy
        ]);
    }

    /**
     * Display the specified user's public profile
     */
    public function show(User $user)
    {
        // Vérifier que le profil est public
        if (!$user->profile_public) {
            abort(404, 'Ce profil n\'est pas public.');
        }

        // Charger les collections publiques avec quelques vinyles pour aperçu
        $collections = $user->publicCollections()
                          ->withCount('collectionVinyls')
                          ->with(['collectionVinyls' => function($query) {
                              $query->with('vinyl')
                                    ->orderBy('date_ajout', 'desc')
                                    ->take(3); // Aperçu des 3 derniers vinyles ajoutés
                          }])
                          ->orderBy('collection_date_modif', 'desc')
                          ->get();

        // Statistiques du collectionneur
        $stats = [
            'total_collections' => $collections->count(),
            'total_vinyls' => $user->vinylCollections()->count(),
            'genres' => [], // À implémenter plus tard
            'member_since' => $user->created_at->format('F Y')
        ];

        return Inertia::render('Profiles/Show', [
            'user' => $user,
            'collections' => $collections,
            'stats' => $stats
        ]);
    }

    /**
     * Display a specific public collection
     */
    public function showCollection(User $user, Collection $collection, Request $request)
    {
        // Vérifier que le profil et la collection sont publics
        if (!$user->profile_public || $collection->visibility !== 'public' || $collection->user_id !== $user->id) {
            abort(404, 'Cette collection n\'est pas accessible.');
        }

        // Paramètres de filtrage et tri
        $search = $request->get('search', '');
        $sortBy = $request->get('sort', 'date_ajout');
        $sortOrder = $request->get('order', 'desc');
        $perPage = $request->get('per_page', 30);
        $letter = $request->get('letter', '');

        // Filtres avancés
        $filterArtiste = $request->get('artiste', '');
        $filterLabel = $request->get('label', '');
        $filterFormat = $request->get('format', '');
        $filterYearFrom = $request->get('year_from', '');
        $filterYearTo = $request->get('year_to', '');

        // Construire la requête de base
        $query = $collection->collectionVinyls()->with('vinyl');

        // Filtre par lettre
        if ($letter) {
            if ($letter === '#') {
                $query->whereHas('vinyl', function($q) {
                    $q->whereRaw("vinyl_nom NOT SIMILAR TO '[A-Za-z]%'");
                });
            } else {
                $query->whereHas('vinyl', function($q) use ($letter) {
                    $q->whereRaw('UPPER(LEFT(vinyl_nom, 1)) = ?', [strtoupper($letter)]);
                });
            }
        }

        // Recherche textuelle
        if ($search) {
            $query->whereHas('vinyl', function($q) use ($search) {
                $q->where(function($query) use ($search) {
                    $query->where('vinyl_nom', 'ILIKE', "%{$search}%")
                          ->orWhere('artiste', 'ILIKE', "%{$search}%")
                          ->orWhere('label', 'ILIKE', "%{$search}%");
                });
            });
        }

        // Filtres avancés
        if ($filterArtiste) {
            $query->whereHas('vinyl', function($q) use ($filterArtiste) {
                $q->where('artiste', 'ILIKE', "%{$filterArtiste}%");
            });
        }

        if ($filterLabel) {
            $query->whereHas('vinyl', function($q) use ($filterLabel) {
                $q->where('label', 'ILIKE', "%{$filterLabel}%");
            });
        }

        if ($filterFormat) {
            $query->whereHas('vinyl', function($q) use ($filterFormat) {
                $q->where('format', 'ILIKE', "%{$filterFormat}%");
            });
        }

        if ($filterYearFrom) {
            $query->whereHas('vinyl', function($q) use ($filterYearFrom) {
                $q->where('annee', '>=', (int)$filterYearFrom);
            });
        }

        if ($filterYearTo) {
            $query->whereHas('vinyl', function($q) use ($filterYearTo) {
                $q->where('annee', '<=', (int)$filterYearTo);
            });
        }

        // Appliquer le tri
        switch ($sortBy) {
            case 'titre':
                $query->join('vinyls', 'collection_vinyls.vinyl_id', '=', 'vinyls.id')
                      ->orderBy('vinyls.vinyl_nom', $sortOrder)
                      ->select('collection_vinyls.*');
                break;
            case 'artiste':
                $query->join('vinyls', 'collection_vinyls.vinyl_id', '=', 'vinyls.id')
                      ->orderBy('vinyls.artiste', $sortOrder)
                      ->select('collection_vinyls.*');
                break;
            case 'annee':
                $query->join('vinyls', 'collection_vinyls.vinyl_id', '=', 'vinyls.id')
                      ->orderBy('vinyls.annee', $sortOrder)
                      ->select('collection_vinyls.*');
                break;
            case 'date_ajout':
            default:
                $query->orderBy('date_ajout', $sortOrder);
                break;
        }

        $vinyls = $query->paginate((int)$perPage)->appends($request->query());

        // Préparer les données de la collection sans les relations pour éviter la duplication
        $collectionData = $collection->only([
            'id',
            'collection_nom',
            'collection_commentaires',
            'collection_date_crea',
            'collection_date_modif',
            'visibility',
            'user_id'
        ]);

        // Récupérer les autres collections publiques de l'utilisateur
        $userCollections = $user->collections()
            ->where('visibility', 'public')
            ->withCount('collectionVinyls as vinyls_count')
            ->orderBy('collection_nom')
            ->get(['id', 'collection_nom', 'vinyls_count']);

        return Inertia::render('Profiles/Collection', [
            'user' => $user,
            'collection' => $collectionData,
            'vinyls' => $vinyls,
            'userCollections' => $userCollections,
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

    /**
     * Display all vinyls from a user's public collections
     */
    public function showVinyls(User $user, Request $request)
    {
        // Vérifier que le profil est public
        if (!$user->profile_public) {
            abort(404, 'Ce profil n\'est pas public.');
        }

        // Paramètres de filtrage et tri
        $search = $request->get('search', '');
        $sortBy = $request->get('sort', 'date_ajout');
        $sortOrder = $request->get('order', 'desc');
        $perPage = $request->get('per_page', 30);
        $letter = $request->get('letter', '');
        $collectionId = $request->get('collection', '');

        // Filtres avancés
        $filterArtiste = $request->get('artiste', '');
        $filterLabel = $request->get('label', '');
        $filterFormat = $request->get('format', '');
        $filterYearFrom = $request->get('year_from', '');
        $filterYearTo = $request->get('year_to', '');

        // Récupérer les IDs des collections publiques
        $publicCollectionIds = $user->collections()
            ->where('visibility', 'public')
            ->pluck('id');

        // Construire la requête de base - vinyles des collections publiques uniquement
        $query = \App\Models\CollectionVinyl::whereIn('collection_id', $publicCollectionIds)
            ->with(['vinyl', 'collection:id,collection_nom']);

        // Filtre par collection
        if ($collectionId) {
            $query->where('collection_id', $collectionId);
        }

        // Filtre par lettre
        if ($letter) {
            if ($letter === '#') {
                $query->whereHas('vinyl', function($q) {
                    $q->whereRaw("vinyl_nom NOT SIMILAR TO '[A-Za-z]%'");
                });
            } else {
                $query->whereHas('vinyl', function($q) use ($letter) {
                    $q->whereRaw('UPPER(LEFT(vinyl_nom, 1)) = ?', [strtoupper($letter)]);
                });
            }
        }

        // Recherche textuelle
        if ($search) {
            $query->whereHas('vinyl', function($q) use ($search) {
                $q->where(function($query) use ($search) {
                    $query->where('vinyl_nom', 'ILIKE', "%{$search}%")
                          ->orWhere('artiste', 'ILIKE', "%{$search}%")
                          ->orWhere('label', 'ILIKE', "%{$search}%");
                });
            });
        }

        // Filtres avancés
        if ($filterArtiste) {
            $query->whereHas('vinyl', function($q) use ($filterArtiste) {
                $q->where('artiste', 'ILIKE', "%{$filterArtiste}%");
            });
        }

        if ($filterLabel) {
            $query->whereHas('vinyl', function($q) use ($filterLabel) {
                $q->where('label', 'ILIKE', "%{$filterLabel}%");
            });
        }

        if ($filterFormat) {
            $query->whereHas('vinyl', function($q) use ($filterFormat) {
                $q->where('format', 'ILIKE', "%{$filterFormat}%");
            });
        }

        if ($filterYearFrom) {
            $query->whereHas('vinyl', function($q) use ($filterYearFrom) {
                $q->where('annee', '>=', (int)$filterYearFrom);
            });
        }

        if ($filterYearTo) {
            $query->whereHas('vinyl', function($q) use ($filterYearTo) {
                $q->where('annee', '<=', (int)$filterYearTo);
            });
        }

        // Appliquer le tri
        switch ($sortBy) {
            case 'titre':
                $query->join('vinyls', 'collection_vinyls.vinyl_id', '=', 'vinyls.id')
                      ->orderBy('vinyls.vinyl_nom', $sortOrder)
                      ->select('collection_vinyls.*');
                break;
            case 'artiste':
                $query->join('vinyls', 'collection_vinyls.vinyl_id', '=', 'vinyls.id')
                      ->orderBy('vinyls.artiste', $sortOrder)
                      ->select('collection_vinyls.*');
                break;
            case 'annee':
                $query->join('vinyls', 'collection_vinyls.vinyl_id', '=', 'vinyls.id')
                      ->orderBy('vinyls.annee', $sortOrder)
                      ->select('collection_vinyls.*');
                break;
            case 'date_ajout':
            default:
                $query->orderBy('date_ajout', $sortOrder);
                break;
        }

        $vinyls = $query->paginate((int)$perPage)->appends($request->query());

        // Récupérer les collections publiques pour le filtre
        $collections = $user->collections()
            ->where('visibility', 'public')
            ->select(['id', 'collection_nom'])
            ->withCount('collectionVinyls')
            ->orderBy('collection_nom')
            ->get();

        // Statistiques
        $totalVinyls = \App\Models\CollectionVinyl::whereIn('collection_id', $publicCollectionIds)->count();

        return Inertia::render('Profiles/Vinyls', [
            'user' => $user->only(['id', 'name', 'avatar', 'location', 'bio']),
            'vinyls' => $vinyls,
            'collections' => $collections,
            'totalVinyls' => $totalVinyls,
            'filters' => [
                'search' => $search,
                'sort' => $sortBy,
                'order' => $sortOrder,
                'per_page' => (int)$perPage,
                'letter' => $letter,
                'collection' => $collectionId,
                'artiste' => $filterArtiste,
                'label' => $filterLabel,
                'format' => $filterFormat,
                'year_from' => $filterYearFrom,
                'year_to' => $filterYearTo,
            ]
        ]);
    }
}
