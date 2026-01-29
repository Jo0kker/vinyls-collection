<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\VinylController;
use App\Http\Controllers\CollectionVinylController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Api\DiscogsController;
use App\Http\Controllers\Api\UserSearchController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\ForumCategoryController;
use App\Http\Controllers\ForumThreadController;
use App\Http\Controllers\ForumPostController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Admin\SupportAdminController;
use App\Http\Controllers\LegalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Legal pages
Route::get('/mentions-legales', [LegalController::class, 'mentionsLegales'])->name('legal.mentions');
Route::get('/cgu', [LegalController::class, 'cgu'])->name('legal.cgu');
Route::get('/confidentialite', [LegalController::class, 'privacyPolicy'])->name('legal.privacy');

// SEO routes - robots.txt
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Public profiles routes (accessible to everyone)
Route::get('/collectors', [PublicProfileController::class, 'index'])->name('collectors.index');
Route::get('/collectors/{user}', [PublicProfileController::class, 'show'])->name('collectors.show');
Route::get('/collectors/{user}/collections/{collection}', [PublicProfileController::class, 'showCollection'])->name('collectors.collection');
Route::get('/collectors/{user}/vinyls', [PublicProfileController::class, 'showVinyls'])->name('collectors.vinyls');

// Catalogue global des vinyles
Route::get('/explore', [CatalogController::class, 'index'])->name('catalog.index');

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

    // Route pour l'upload d'image
    Route::post('vinyls/{id}/update-image', [VinylController::class, 'updateImage'])->name('vinyls.update-image');

    // Collections routes
    Route::resource('collections', CollectionController::class);
    Route::get('collections/{collection}/export', [CollectionController::class, 'export'])
        ->name('collections.export')
        ->middleware('throttle:export');
    Route::delete('collections/{collection}/vinyl/{collectionVinyl}', [CollectionController::class, 'removeVinyl'])->name('collections.vinyl.remove');
    Route::patch('collections/{collection}/vinyl/{collectionVinyl}/move', [CollectionController::class, 'moveVinyl'])->name('collections.vinyl.move');
    Route::get('collections/{collection}/deletion-stats', [CollectionController::class, 'getDeletionStats'])->name('collections.deletion-stats');
    
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
        Route::post('check-image-accessibility/{collectionVinyl}', [CollectionController::class, 'checkImageAccessibility'])->name('api.check-image');
        Route::get('users/search', [UserSearchController::class, 'search'])->name('api.users.search');
        Route::get('conversations', [ConversationController::class, 'apiIndex'])->name('api.conversations.index');
        Route::post('conversations', [ConversationController::class, 'apiStore'])->name('api.conversations.store');
        Route::post('support', [SupportController::class, 'apiCreate'])->name('api.support.create');
    });

});

// Discogs import routes
Route::middleware('auth')->prefix('discogs')->name('discogs.')->group(function () {
    Route::get('/', [App\Http\Controllers\DiscogsImportController::class, 'index'])->name('import');
    Route::get('/connect', [App\Http\Controllers\DiscogsImportController::class, 'connect'])->name('connect');
    Route::get('/callback', [App\Http\Controllers\DiscogsImportController::class, 'callback'])->name('callback');
    Route::post('/disconnect', [App\Http\Controllers\DiscogsImportController::class, 'disconnect'])->name('disconnect');
    Route::post('/import', [App\Http\Controllers\DiscogsImportController::class, 'startImport'])->name('start-import');
    Route::get('/import/{import}/status', [App\Http\Controllers\DiscogsImportController::class, 'importStatus'])->name('import-status');
    Route::get('/folder-preview', [App\Http\Controllers\DiscogsImportController::class, 'folderPreview'])->name('folder-preview');
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

    // Thread subscription routes
    Route::post('thread/{thread_id}/subscribe', [ForumThreadController::class, 'subscribe'])->name('thread.subscribe');
    Route::post('thread/{thread_id}/unsubscribe', [ForumThreadController::class, 'unsubscribe'])->name('thread.unsubscribe');

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

// Messages (Private messaging system)
Route::middleware('auth')->prefix('messages')->name('messages.')->group(function () {
    Route::get('/', [ConversationController::class, 'index'])->name('index');
    Route::get('/debug', fn () => \Inertia\Inertia::render('Messages/Debug'))->name('debug');
    Route::post('/', [ConversationController::class, 'store'])->name('store');
    Route::get('/{conversation}', [ConversationController::class, 'show'])->name('show')->whereNumber('conversation');

    // Message actions
    Route::post('/{conversation}/messages', [MessageController::class, 'store'])->name('send');
    Route::get('/{conversation}/messages', [MessageController::class, 'loadMore'])->name('load-more');
    Route::post('/{conversation}/typing', [MessageController::class, 'typing'])->name('typing');
    Route::post('/{conversation}/read', [MessageController::class, 'markAsRead'])->name('read');

    // Group conversation management
    Route::post('/{conversation}/participants', [ConversationController::class, 'addParticipants'])->name('participants.add');
    Route::delete('/{conversation}/participants/{participant}', [ConversationController::class, 'removeParticipant'])->name('participants.remove');
    Route::post('/{conversation}/leave', [ConversationController::class, 'leave'])->name('leave');
    Route::patch('/{conversation}/group', [ConversationController::class, 'updateGroup'])->name('group.update');
});

// Support (for logged-in users)
Route::middleware('auth')->prefix('support')->name('support.')->group(function () {
    Route::get('/', [SupportController::class, 'index'])->name('index');
    Route::post('/', [SupportController::class, 'create'])->name('create');
    Route::get('/{conversation}', [SupportController::class, 'show'])->name('show');
});

// Contact form (public - no auth required)
Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/contact/success', [ContactController::class, 'success'])->name('contact.success');

// Admin Support routes
Route::middleware(['auth', 'role:admin'])->prefix('admin/support')->name('admin.support.')->group(function () {
    Route::get('/', [SupportAdminController::class, 'index'])->name('index');
    Route::get('/tickets/{ticket}', [SupportAdminController::class, 'showTicket'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [SupportAdminController::class, 'replyToTicket'])->name('tickets.reply');
    Route::patch('/tickets/{ticket}/status', [SupportAdminController::class, 'updateTicketStatus'])->name('tickets.status');
    Route::patch('/tickets/{ticket}/assign', [SupportAdminController::class, 'assignTicket'])->name('tickets.assign');
    Route::get('/conversations/{conversation}', [SupportAdminController::class, 'showConversation'])->name('conversations.show');
    Route::patch('/conversations/{conversation}/status', [SupportAdminController::class, 'updateConversationStatus'])->name('conversations.status');
});

require __DIR__.'/auth.php';
