<?php

namespace App\Http\Controllers;

use App\Jobs\ImportDiscogsCollectionJob;
use App\Models\Collection;
use App\Models\DiscogsImport;
use App\Models\DiscogsFolderMapping;
use App\Services\DiscogsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DiscogsImportController extends Controller
{
    public function __construct(
        protected DiscogsService $discogsService
    ) {}

    /**
     * Show the Discogs import page
     */
    public function index()
    {
        $user = Auth::user();

        $data = [
            'isConnected' => !empty($user->discogs_oauth_token),
            'discogsUsername' => $user->discogs_username,
            'connectedAt' => $user->discogs_connected_at,
            'folders' => [],
            'collections' => $user->collections()->orderBy('collection_nom')->get(['id', 'collection_nom']),
            'folderMappings' => DiscogsFolderMapping::where('user_id', $user->id)
                ->with('collection:id,collection_nom')
                ->get(),
            'recentImports' => DiscogsImport::where('user_id', $user->id)
                ->with('collection:id,collection_nom')
                ->orderBy('created_at', 'desc')
                ->paginate(10),
            'activeImport' => DiscogsImport::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'processing'])
                ->first(),
        ];

        // If connected, fetch folders
        if ($data['isConnected']) {
            try {
                $data['folders'] = $this->discogsService->getUserFolders(
                    $user->discogs_username,
                    $user->discogs_oauth_token,
                    $user->discogs_oauth_token_secret
                );
            } catch (\Exception $e) {
                Log::error('Failed to fetch Discogs folders', ['error' => $e->getMessage()]);
                $data['folderError'] = 'Impossible de récupérer vos dossiers Discogs. Veuillez vous reconnecter.';
            }
        }

        return Inertia::render('Discogs/Import', $data);
    }

    /**
     * Start OAuth flow - redirect to Discogs
     */
    public function connect(Request $request)
    {
        try {
            // Generate a unique nonce to track this OAuth flow
            $nonce = uniqid('discogs_', true);

            // Get request token from Discogs
            $requestToken = $this->discogsService->getRequestToken($nonce);

            // Store the request token temporarily
            Cache::put("discogs_oauth_{$nonce}", [
                'oauth_token' => $requestToken['oauth_token'],
                'oauth_token_secret' => $requestToken['oauth_token_secret'],
                'user_id' => Auth::id(),
            ], now()->addMinutes(15));

            // Redirect user to Discogs authorization page
            $authorizeUrl = $this->discogsService->getAuthorizeUrl($requestToken['oauth_token']);

            return redirect()->away($authorizeUrl);

        } catch (\Exception $e) {
            Log::error('Discogs OAuth connect error', ['error' => $e->getMessage()]);
            return redirect()->route('discogs.import')
                ->with('error', 'Erreur lors de la connexion à Discogs: ' . $e->getMessage());
        }
    }

    /**
     * OAuth callback from Discogs
     */
    public function callback(Request $request)
    {
        $nonce = $request->get('nonce');
        $oauthToken = $request->get('oauth_token');
        $oauthVerifier = $request->get('oauth_verifier');

        if (!$nonce || !$oauthToken || !$oauthVerifier) {
            return redirect()->route('discogs.import')
                ->with('error', 'Paramètres OAuth manquants');
        }

        // Retrieve stored request token
        $stored = Cache::get("discogs_oauth_{$nonce}");
        if (!$stored || $stored['oauth_token'] !== $oauthToken) {
            return redirect()->route('discogs.import')
                ->with('error', 'Session OAuth expirée ou invalide');
        }

        // Verify user is the same
        if ($stored['user_id'] !== Auth::id()) {
            return redirect()->route('discogs.import')
                ->with('error', 'Session utilisateur invalide');
        }

        try {
            // Exchange for access token
            $accessToken = $this->discogsService->getAccessToken(
                $oauthToken,
                $stored['oauth_token_secret'],
                $oauthVerifier
            );

            // Get user identity
            $identity = $this->discogsService->getIdentity(
                $accessToken['oauth_token'],
                $accessToken['oauth_token_secret']
            );

            // Update user with Discogs credentials
            Auth::user()->update([
                'discogs_username' => $identity['username'] ?? null,
                'discogs_oauth_token' => $accessToken['oauth_token'],
                'discogs_oauth_token_secret' => $accessToken['oauth_token_secret'],
                'discogs_connected_at' => now(),
            ]);

            // Clean up cache
            Cache::forget("discogs_oauth_{$nonce}");

            return redirect()->route('discogs.import')
                ->with('success', 'Compte Discogs connecté avec succès !');

        } catch (\Exception $e) {
            Log::error('Discogs OAuth callback error', ['error' => $e->getMessage()]);
            return redirect()->route('discogs.import')
                ->with('error', 'Erreur lors de la finalisation de la connexion: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Discogs account
     */
    public function disconnect()
    {
        Auth::user()->update([
            'discogs_username' => null,
            'discogs_oauth_token' => null,
            'discogs_oauth_token_secret' => null,
            'discogs_connected_at' => null,
        ]);

        return redirect()->route('discogs.import')
            ->with('success', 'Compte Discogs déconnecté');
    }

    /**
     * Start importing a folder
     */
    public function startImport(Request $request)
    {
        $request->validate([
            'folder_id' => 'required|integer',
            'folder_name' => 'required|string',
            'collection_id' => 'nullable|integer|exists:collections,id',
            'new_collection_name' => 'required_without:collection_id|nullable|string|max:255',
        ]);

        $user = Auth::user();

        // Check if Discogs is connected
        if (!$user->discogs_oauth_token) {
            return back()->with('error', 'Veuillez d\'abord connecter votre compte Discogs');
        }

        // Check if there's already an active import
        $activeImport = DiscogsImport::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($activeImport) {
            return back()->with('error', 'Un import est déjà en cours. Veuillez attendre qu\'il se termine.');
        }

        // Check cooldown for the same folder (30 minutes minimum between imports)
        $cooldownMinutes = 30;
        $recentImport = DiscogsImport::where('user_id', $user->id)
            ->where('discogs_folder_id', $request->folder_id)
            ->where('created_at', '>=', now()->subMinutes($cooldownMinutes))
            ->first();

        if ($recentImport) {
            $minutesSinceLastImport = (int) $recentImport->created_at->diffInMinutes(now());
            $waitMinutes = max(1, $cooldownMinutes - $minutesSinceLastImport);
            return back()->with('error', "Veuillez attendre encore {$waitMinutes} minute(s) avant de relancer un import pour ce dossier.");
        }

        // Get or create collection
        if ($request->collection_id) {
            $collection = Collection::where('id', $request->collection_id)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } else {
            $collection = Collection::create([
                'user_id' => $user->id,
                'collection_nom' => $request->new_collection_name,
                'collection_date_crea' => now(),
                'collection_date_modif' => now(),
                'visibility' => 'private',
            ]);
        }

        // Create import record
        $import = DiscogsImport::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'discogs_folder_id' => $request->folder_id,
            'status' => 'pending',
        ]);

        // Update or create folder mapping
        DiscogsFolderMapping::updateOrCreate(
            [
                'user_id' => $user->id,
                'discogs_folder_id' => $request->folder_id,
            ],
            [
                'collection_id' => $collection->id,
                'discogs_folder_name' => $request->folder_name,
            ]
        );

        // Dispatch the import job
        ImportDiscogsCollectionJob::dispatch(
            $user->id,
            $import->id,
            $request->folder_id,
            $collection->id
        );

        return redirect()->route('discogs.import')
            ->with('success', 'Import démarré ! Vous pouvez suivre la progression ci-dessous.');
    }

    /**
     * Get import status (for polling)
     */
    public function importStatus(DiscogsImport $import)
    {
        // Verify ownership
        if ($import->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json([
            'id' => $import->id,
            'status' => $import->status,
            'total_items' => $import->total_items,
            'processed_items' => $import->processed_items,
            'imported_items' => $import->imported_items,
            'skipped_items' => $import->skipped_items,
            'removed_items' => $import->removed_items,
            'progress_percentage' => $import->getProgressPercentage(),
            'error_message' => $import->error_message,
            'errors_count' => $import->errors ? count($import->errors) : 0,
            'started_at' => $import->started_at?->toIso8601String(),
            'completed_at' => $import->completed_at?->toIso8601String(),
        ]);
    }

    /**
     * Get folder preview (count items without importing)
     */
    public function folderPreview(Request $request)
    {
        $request->validate([
            'folder_id' => 'required|integer',
        ]);

        $user = Auth::user();

        if (!$user->discogs_oauth_token) {
            return response()->json(['error' => 'Discogs not connected'], 401);
        }

        try {
            $response = $this->discogsService->getFolderItems(
                $user->discogs_username,
                $request->folder_id,
                $user->discogs_oauth_token,
                $user->discogs_oauth_token_secret,
                1,
                1
            );

            return response()->json([
                'total_items' => $response['pagination']['items'] ?? 0,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
