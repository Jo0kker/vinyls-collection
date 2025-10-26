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
        $sortBy = $request->get('sort_by', 'vinyl_count'); // Default sort by vinyl count
        
        $query = User::where('profile_public', true)
                    ->whereNotNull('email_verified_at') // Only verified users
                    ->select(['id', 'name', 'avatar', 'location', 'bio', 'vinyl_count', 'public_collections_count', 'created_at']);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%")
                  ->orWhere('bio', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting using cached columns
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
    public function showCollection(User $user, Collection $collection)
    {
        // Vérifier que le profil et la collection sont publics
        if (!$user->profile_public || $collection->visibility !== 'public' || $collection->user_id !== $user->id) {
            abort(404, 'Cette collection n\'est pas accessible.');
        }

        // Charger la collection avec tous ses vinyles
        $collection->load(['collectionVinyls.vinyl']);

        return Inertia::render('Profiles/Collection', [
            'user' => $user,
            'collection' => $collection
        ]);
    }
}
