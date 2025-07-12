<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\VinylController;
use App\Http\Controllers\Api\DiscogsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// SEO routes
Route::get('/sitemap.xml', function () {
    $sitemapPath = public_path('sitemap.xml');
    if (file_exists($sitemapPath)) {
        return response()->file($sitemapPath, [
            'Content-Type' => 'application/xml'
        ]);
    }
    return response('Sitemap not found', 404);
})->name('sitemap');

Route::get('/robots.txt', function () {
    $content = "User-agent: *\nDisallow:\n\nSitemap: " . url('/sitemap.xml');
    return response($content, 200, [
        'Content-Type' => 'text/plain'
    ]);
})->name('robots');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Collections routes
    Route::resource('collections', CollectionController::class);
    
    // Vinyls routes
    Route::resource('vinyls', VinylController::class);
    Route::post('vinyls/from-discogs', [VinylController::class, 'storeFromDiscogs'])->name('vinyls.store-from-discogs');
    
    // API Routes pour Discogs
    Route::prefix('api')->group(function () {
        Route::get('discogs/search', [DiscogsController::class, 'search'])->name('api.discogs.search');
        Route::get('discogs/release/{id}', [DiscogsController::class, 'getRelease'])->name('api.discogs.release');
    });
});

require __DIR__.'/auth.php';
