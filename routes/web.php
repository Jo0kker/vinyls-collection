<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\VinylController;
use App\Http\Controllers\CollectionVinylController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Api\DiscogsController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\ForumCategoryController;
use App\Http\Controllers\ForumThreadController;
use App\Http\Controllers\ForumPostController;
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

// SEO routes - robots.txt
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Public profiles routes (accessible to everyone)
Route::get('/collectors', [PublicProfileController::class, 'index'])->name('collectors.index');
Route::get('/collectors/{user}', [PublicProfileController::class, 'show'])->name('collectors.show');
Route::get('/collectors/{user}/collections/{collection}', [PublicProfileController::class, 'showCollection'])->name('collectors.collection');

// Route pour afficher les détails d'un vinyle (accessible à tous si public)
Route::get('vinyles/{vinyl}', [VinylController::class, 'showVinyl'])->name('vinyl.show');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/dashboard/search', [DashboardController::class, 'search'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard.search');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Collections routes
    Route::resource('collections', CollectionController::class);
    Route::get('collections/{collection}/export', [CollectionController::class, 'export'])
        ->name('collections.export')
        ->middleware('throttle:export');
    Route::delete('collections/{collection}/vinyl/{collectionVinyl}', [CollectionController::class, 'removeVinyl'])->name('collections.vinyl.remove');
    Route::patch('collections/{collection}/vinyl/{collectionVinyl}/move', [CollectionController::class, 'moveVinyl'])->name('collections.vinyl.move');
    
    // Vinyls routes (mes vinyles personnels)
    Route::resource('mes-vinyles', VinylController::class)->names([
        'index' => 'vinyls.index',
        'create' => 'vinyls.create',
        'store' => 'vinyls.store',
        'edit' => 'vinyls.edit',
        'update' => 'vinyls.update',
        'destroy' => 'vinyls.destroy'
    ])->parameters([
        'mes-vinyles' => 'collectionVinyl'
    ]);
    
    Route::post('vinyls/from-discogs', [VinylController::class, 'storeFromDiscogs'])->name('vinyls.store-from-discogs');
    Route::post('vinyls/manual', [VinylController::class, 'storeManual'])->name('vinyls.store-manual');
    Route::post('vinyls/add-to-collection', [VinylController::class, 'addToCollection'])->name('vinyls.add-to-collection');
    Route::post('vinyls/{collectionVinyl}/duplicate', [VinylController::class, 'duplicate'])->name('vinyls.duplicate');
    
    // Routes pour l'édition des exemplaires (informations spécifiques à l'utilisateur)
    Route::get('exemplaires/{collectionVinyl}/edit', [CollectionVinylController::class, 'edit'])->name('collection-vinyl.edit');
    Route::put('exemplaires/{collectionVinyl}', [CollectionVinylController::class, 'update'])->name('collection-vinyl.update');
    Route::get('api/exemplaires/{collectionVinyl}/editable-fields', [CollectionVinylController::class, 'getEditableFields'])->name('collection-vinyl.editable-fields');
    
    // API Routes pour Discogs
    Route::prefix('api')->group(function () {
        Route::get('discogs/search', [DiscogsController::class, 'search'])->name('api.discogs.search');
        Route::get('discogs/release/{id}', [DiscogsController::class, 'getRelease'])->name('api.discogs.release');
    });
    
});

// Forum routes (consultation ouverte, sécurité gérée par les policies)
Route::prefix('forum')->name('forum.')->group(function () {
    Route::get('/', [ForumController::class, 'index'])->name('index');
    Route::get('category/{category_id}', [ForumCategoryController::class, 'show'])->name('category.show');
    Route::get('thread/{thread_id}', [ForumThreadController::class, 'show'])->name('thread.show');
    Route::get('recent', [ForumController::class, 'recent'])->name('recent');
    Route::get('search', [ForumController::class, 'search'])->name('search');
});

// Forum routes authentifiées (création/réponse)
Route::middleware('auth')->prefix('forum')->name('forum.')->group(function () {
    Route::get('category/{category_id}/thread/create', [ForumThreadController::class, 'create'])->name('thread.create');
    Route::post('category/{category_id}/thread', [ForumThreadController::class, 'store'])->name('thread.store');
    Route::post('thread/{thread_id}/post', [ForumPostController::class, 'store'])->name('post.store');
    Route::get('post/{post_id}/edit', [ForumPostController::class, 'edit'])->name('post.edit');
    Route::put('post/{post_id}', [ForumPostController::class, 'update'])->name('post.update');
    
    // Routes pour utilisateurs connectés
    Route::get('unread', [ForumController::class, 'unread'])->name('unread');
    Route::post('mark-all-read', [ForumController::class, 'markAllAsRead'])->name('mark-all-read');
    Route::get('my-threads', [ForumController::class, 'myThreads'])->name('my-threads');
    
    // Thread management routes
    Route::post('thread/{thread_id}/lock', [ForumThreadController::class, 'lock'])->name('thread.lock');
    Route::post('thread/{thread_id}/unlock', [ForumThreadController::class, 'unlock'])->name('thread.unlock');
    Route::post('thread/{thread_id}/pin', [ForumThreadController::class, 'pin'])->name('thread.pin');
    Route::post('thread/{thread_id}/unpin', [ForumThreadController::class, 'unpin'])->name('thread.unpin');
    Route::post('thread/{thread_id}/rename', [ForumThreadController::class, 'rename'])->name('thread.rename');
    Route::post('thread/{thread_id}/move', [ForumThreadController::class, 'move'])->name('thread.move');
    Route::delete('thread/{thread_id}', [ForumThreadController::class, 'destroy'])->name('thread.destroy');
    Route::post('thread/{thread_id}/restore', [ForumThreadController::class, 'restore'])->name('thread.restore');
    Route::delete('thread/{thread_id}/force', [ForumThreadController::class, 'forceDestroy'])->name('thread.force-destroy');
    
    // Post management routes
    Route::delete('post/{post_id}', [ForumPostController::class, 'destroy'])->name('post.destroy');
    Route::post('post/{post_id}/restore', [ForumPostController::class, 'restore'])->name('post.restore');
    Route::delete('post/{post_id}/force', [ForumPostController::class, 'forceDestroy'])->name('post.force-destroy');
    Route::post('post/bulk', [ForumPostController::class, 'bulk'])->name('post.bulk');
    
    // Category management routes (admin only)
    Route::get('manage', [ForumCategoryController::class, 'manage'])->name('category.manage');
    Route::post('category', [ForumCategoryController::class, 'store'])->name('category.store');
    Route::put('category/{category_id}', [ForumCategoryController::class, 'update'])->name('category.update');
    Route::delete('category/{category_id}', [ForumCategoryController::class, 'destroy'])->name('category.destroy');
    Route::post('category/bulk-manage', [ForumCategoryController::class, 'bulkManage'])->name('category.bulk-manage');
});

// SEO Routes
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

require __DIR__.'/auth.php';
