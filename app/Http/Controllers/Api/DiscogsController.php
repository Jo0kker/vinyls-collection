<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DiscogsService;
use Illuminate\Http\Request;

class DiscogsController extends Controller
{
    private $discogsService;

    public function __construct(DiscogsService $discogsService)
    {
        $this->discogsService = $discogsService;
    }

    /**
     * Recherche des vinyles sur Discogs
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (empty($query)) {
            return response()->json(['results' => []]);
        }

        $results = $this->discogsService->search($query);
        
        return response()->json($results);
    }

    /**
     * Récupère les détails d'un release
     */
    public function getRelease($releaseId)
    {
        $release = $this->discogsService->getRelease($releaseId);
        
        if (!$release) {
            return response()->json(['error' => 'Release not found'], 404);
        }

        return response()->json($release);
    }

    /**
     * Récupère les détails d'un master
     */
    public function getMaster($masterId)
    {
        $master = $this->discogsService->getMaster($masterId);
        
        if (!$master) {
            return response()->json(['error' => 'Master not found'], 404);
        }

        return response()->json($master);
    }
}