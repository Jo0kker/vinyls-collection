<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscogsService
{
    private $baseUrl = 'https://api.discogs.com';
    private $userAgent;

    public function __construct()
    {
        $this->userAgent = config('app.name', 'VinylCollection') . '/1.0';
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
            'discogs_id' => $discogsId,
            'discogs_type' => $discogsType,
            'discogs_updated_at' => now(),
        ];
    }
}