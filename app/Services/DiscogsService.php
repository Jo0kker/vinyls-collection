<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DiscogsService
{
    private $baseUrl = 'https://api.discogs.com';
    private $userAgent;
    private $rateLimitRemaining = 60;

    public function __construct()
    {
        $this->userAgent = config('app.name', 'VinylCollection') . '/1.0';
    }

    // ==========================================
    // OAuth Authentication Methods
    // ==========================================

    /**
     * Get OAuth request token (Step 1 of OAuth flow)
     */
    public function getRequestToken(string $callbackNonce): array
    {
        $timestamp = time();
        $nonce = $timestamp;

        $authHeader = sprintf(
            'OAuth oauth_consumer_key="%s",oauth_nonce="%s",oauth_signature="%s&",oauth_signature_method="PLAINTEXT",oauth_timestamp="%s",oauth_callback="%s"',
            config('services.discogs.client_id'),
            $nonce,
            config('services.discogs.client_secret'),
            $timestamp,
            config('services.discogs.redirect') . '?nonce=' . $callbackNonce
        );

        $response = Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Authorization' => $authHeader,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->get($this->baseUrl . '/oauth/request_token');

        if ($response->successful()) {
            $result = [];
            parse_str($response->body(), $result);
            return $result;
        }

        Log::error('Discogs OAuth Request Token Error', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        throw new \Exception('Failed to get Discogs request token: ' . $response->body());
    }

    /**
     * Get OAuth access token (Step 3 of OAuth flow)
     */
    public function getAccessToken(string $oauthToken, string $oauthTokenSecret, string $oauthVerifier): array
    {
        $timestamp = time();
        $nonce = $timestamp;

        $authHeader = sprintf(
            'OAuth oauth_consumer_key="%s",oauth_nonce="%s",oauth_token="%s",oauth_signature="%s&%s",oauth_signature_method="PLAINTEXT",oauth_timestamp="%s",oauth_verifier="%s"',
            config('services.discogs.client_id'),
            $nonce,
            $oauthToken,
            config('services.discogs.client_secret'),
            $oauthTokenSecret,
            $timestamp,
            $oauthVerifier
        );

        $response = Http::withHeaders([
            'User-Agent' => $this->userAgent,
            'Authorization' => $authHeader,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->post($this->baseUrl . '/oauth/access_token');

        if ($response->successful()) {
            $result = [];
            parse_str($response->body(), $result);
            return $result;
        }

        Log::error('Discogs OAuth Access Token Error', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        throw new \Exception('Failed to get Discogs access token: ' . $response->body());
    }

    /**
     * Get user identity with OAuth tokens
     */
    public function getIdentity(string $accessToken, string $accessTokenSecret): array
    {
        $response = $this->makeOAuthRequest('oauth/identity', $accessToken, $accessTokenSecret);
        return $response ?? [];
    }

    /**
     * Get user profile data
     */
    public function getUserProfile(string $username, string $accessToken, string $accessTokenSecret): array
    {
        $response = $this->makeOAuthRequest("users/{$username}", $accessToken, $accessTokenSecret);
        return $response ?? [];
    }

    // ==========================================
    // Collection Methods
    // ==========================================

    /**
     * Get user's collection folders
     * Note: Excludes the "All" folder (id=0) which contains all items from all folders
     */
    public function getUserFolders(string $username, string $accessToken, string $accessTokenSecret): array
    {
        $response = $this->makeOAuthRequest(
            "users/{$username}/collection/folders",
            $accessToken,
            $accessTokenSecret
        );

        $folders = $response['folders'] ?? [];

        // Filter out the "All" folder (id=0) which contains duplicates of all items
        return array_values(array_filter($folders, fn($folder) => $folder['id'] !== 0));
    }

    /**
     * Get items from a specific folder with pagination
     */
    public function getFolderItems(
        string $username,
        int $folderId,
        string $accessToken,
        string $accessTokenSecret,
        int $page = 1,
        int $perPage = 50
    ): array {
        // Respecter le rate limiting
        $this->respectRateLimit();

        $response = $this->makeOAuthRequest(
            "users/{$username}/collection/folders/{$folderId}/releases",
            $accessToken,
            $accessTokenSecret,
            ['page' => $page, 'per_page' => $perPage]
        );

        return $response ?? ['releases' => [], 'pagination' => []];
    }

    /**
     * Get all items from a folder (handles pagination automatically)
     * Yields items one by one to save memory on large collections
     */
    public function getAllFolderItems(
        string $username,
        int $folderId,
        string $accessToken,
        string $accessTokenSecret,
        callable $progressCallback = null
    ): \Generator {
        $page = 1;
        $perPage = 50;

        do {
            $response = $this->getFolderItems(
                $username,
                $folderId,
                $accessToken,
                $accessTokenSecret,
                $page,
                $perPage
            );

            $releases = $response['releases'] ?? [];
            $pagination = $response['pagination'] ?? [];

            foreach ($releases as $release) {
                yield $release;
            }

            if ($progressCallback) {
                $progressCallback($page, $pagination['pages'] ?? 1, count($releases));
            }

            $page++;
            $hasMore = isset($pagination['pages']) && $page <= $pagination['pages'];

        } while ($hasMore);
    }

    /**
     * Make an OAuth authenticated request
     */
    private function makeOAuthRequest(string $endpoint, string $accessToken, string $accessTokenSecret, array $queryParams = []): ?array
    {
        $timestamp = time();
        $nonce = $timestamp . rand(1000, 9999);

        $authHeader = sprintf(
            'OAuth oauth_consumer_key="%s",oauth_nonce="%s",oauth_token="%s",oauth_signature="%s&%s",oauth_signature_method="PLAINTEXT",oauth_timestamp="%s"',
            config('services.discogs.client_id'),
            $nonce,
            $accessToken,
            config('services.discogs.client_secret'),
            $accessTokenSecret,
            $timestamp
        );

        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Authorization' => $authHeader,
            ])->get($this->baseUrl . '/' . $endpoint, $queryParams);

            // Update rate limit info from headers
            $this->rateLimitRemaining = (int) ($response->header('X-Discogs-Ratelimit-Remaining') ?? 60);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Discogs OAuth Request Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Discogs OAuth Request Exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Respect Discogs rate limiting (60 requests per minute)
     */
    private function respectRateLimit(): void
    {
        // If we're running low on requests, wait
        if ($this->rateLimitRemaining < 5) {
            Log::info('Discogs rate limit approaching, waiting 60 seconds...');
            sleep(60);
            $this->rateLimitRemaining = 60;
        }
    }

    /**
     * Get the authorize URL for user to grant access
     */
    public function getAuthorizeUrl(string $requestToken): string
    {
        return "https://www.discogs.com/oauth/authorize?oauth_token={$requestToken}";
    }

    /**
     * Recherche des vinyles sur Discogs
     */
    public function search($query, $type = 'release', $perPage = 20)
    {
        try {
            // Détecter si c'est un code Discogs (format [r123456] ou [m123456])
            if (preg_match('/\[([rm])(\d+)\]/i', $query, $matches)) {
                $prefix = strtolower($matches[1]);
                $id = $matches[2];
                
                if ($prefix === 'r') {
                    // C'est un release ID, récupérer directement
                    $release = $this->getRelease($id);
                    if ($release) {
                        return ['results' => [$release]];
                    }
                } elseif ($prefix === 'm') {
                    // C'est un master ID, récupérer le master
                    $master = $this->getMaster($id);
                    if ($master) {
                        // Forcer le type à 'master' pour s'assurer qu'il est bien défini
                        $master['type'] = 'master';
                        
                        // Ajouter un thumb pour l'affichage dans la modal de recherche
                        if (!isset($master['thumb']) && isset($master['images']) && !empty($master['images'])) {
                            // Utiliser l'image 150x150 si disponible, sinon l'URI normale
                            $firstImage = $master['images'][0];
                            $master['thumb'] = $firstImage['uri150'] ?? $firstImage['uri'] ?? null;
                        }
                        
                        return ['results' => [$master]];
                    }
                }
                
                return ['results' => []];
            }

            // Recherche normale
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Authorization' => 'Discogs token=' . config('services.discogs.token', ''),
            ])->get($this->baseUrl . '/database/search', [
                'q' => $query,
                'type' => $type,
                'per_page' => $perPage,
                'format' => 'vinyl'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Discogs API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return ['results' => []];

        } catch (\Exception $e) {
            Log::error('Discogs Service Error: ' . $e->getMessage());
            return ['results' => []];
        }
    }

    /**
     * Récupère les détails d'un release Discogs
     */
    public function getRelease($releaseId)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Authorization' => 'Discogs token=' . config('services.discogs.token', ''),
            ])->get($this->baseUrl . '/releases/' . $releaseId);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Discogs Get Release Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les détails d'un master Discogs
     */
    public function getMaster($masterId)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => $this->userAgent,
                'Authorization' => 'Discogs token=' . config('services.discogs.token', ''),
            ])->get($this->baseUrl . '/masters/' . $masterId);

            if ($response->successful()) {
                $data = $response->json();
                // Forcer le type à 'master' car l'API Discogs ne le retourne pas toujours
                $data['type'] = 'master';
                return $data;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Discogs Get Master Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Convertit les données Discogs en format pour notre base de données
     */
    public function convertToVinylData($discogsData, $discogsId = null, $discogsType = 'release')
    {
        $artists = [];
        
        // Essayer différentes sources pour les artistes
        if (isset($discogsData['artists']) && !empty($discogsData['artists'])) {
            $artists = collect($discogsData['artists'])->pluck('name')->toArray();
        } elseif (isset($discogsData['artist'])) {
            // Fallback pour le format de recherche
            $artists = [$discogsData['artist']];
        } elseif (isset($discogsData['artists_sort'])) {
            // Fallback pour artists_sort
            $artists = [$discogsData['artists_sort']];
        }

        // Essaie de trouver une image de qualité
        $pochette = null;
        if (isset($discogsData['images']) && !empty($discogsData['images'])) {
            // Préfère les images primaires, sinon prend la première
            $primaryImage = collect($discogsData['images'])
                ->firstWhere('type', 'primary');
            
            if ($primaryImage) {
                $pochette = $primaryImage['uri'];
            } else {
                $pochette = $discogsData['images'][0]['uri'];
            }
        } elseif (isset($discogsData['thumb'])) {
            $pochette = $discogsData['thumb'];
        }

        // Gestion des informations supplémentaires
        $annee = null;
        if (isset($discogsData['year']) && $discogsData['year'] > 0) {
            $annee = $discogsData['year'];
        }

        $label = null;
        if (isset($discogsData['labels']) && !empty($discogsData['labels'])) {
            $label = implode(', ', collect($discogsData['labels'])->pluck('name')->toArray());
        }

        $reference = null;
        if (isset($discogsData['labels']) && !empty($discogsData['labels'])) {
            $catno = collect($discogsData['labels'])->pluck('catno')->filter()->first();
            if ($catno && $catno !== 'none') {
                $reference = $catno;
            }
        }

        $artisteName = !empty($artists) ? implode(', ', $artists) : 'Artiste inconnu';

        return [
            'vinyl_nom' => $discogsData['title'] ?? 'Titre inconnu',
            'vinyl_titre' => $discogsData['title'] ?? 'Titre inconnu',
            'vinyl_format' => 1, // 1 pour Vinyl par défaut, à adapter selon votre système de codes
            'artiste' => $artisteName,
            'pochette' => $pochette,
            'vinyl_alias' => 0,
            'vinyl_nbcollect' => 1,
            'reference' => $reference,
            'label' => $label,
            'annee' => $annee,
            'pays' => $discogsData['country'] ?? null,
            'discogs_id' => $discogsId,
            'discogs_type' => $discogsType,
            'discogs_updated_at' => now(),
        ];
    }
}