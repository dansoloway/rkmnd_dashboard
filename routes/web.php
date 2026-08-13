<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AiSearchController;
use App\Http\Controllers\CatalogTermsController;
use App\Http\Controllers\MowRowController;
use App\Http\Controllers\NamespaceStudioController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VocabularyController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->middleware('throttle:6,1')
    ->name('password.email');
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

// Protected routes (require authentication)
Route::middleware(['auth', 'restrict.analytics'])->group(function () {

    // Dashboard (product hub)
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/clear-cache', [\App\Http\Controllers\DashboardController::class, 'clearCache'])->name('dashboard.clear-cache');

    // --- AI Video Search product ---
    Route::prefix('ai-search')->name('ai-search.')->group(function () {
        Route::get('/videos', [VideoController::class, 'index'])->name('videos.index');
        Route::get('/search-visible-audio', [VideoController::class, 'searchVisibleAudio'])->name('search-visible-audio');
        Route::get('/namespace-studio', [NamespaceStudioController::class, 'index'])->name('namespace-studio');
        Route::post('/namespace-studio/reconcile', [NamespaceStudioController::class, 'reconcile'])->name('namespace-studio.reconcile');
        Route::post('/namespace-studio/fix-upsert', [NamespaceStudioController::class, 'fixUpsert'])->name('namespace-studio.fix-upsert');
        Route::post('/namespace-studio/fix-delete', [NamespaceStudioController::class, 'fixDelete'])->name('namespace-studio.fix-delete');
        Route::get('/videos/{id}/embedding-text', [NamespaceStudioController::class, 'embeddingText'])
            ->whereNumber('id')
            ->name('embedding-text');
        Route::get('/playground', [AiSearchController::class, 'index'])->name('playground.index');
        Route::post('/playground', [AiSearchController::class, 'search'])->name('playground.search');
        Route::get('/vocabulary', [VocabularyController::class, 'index'])->name('vocabulary.index');
        Route::get('/vocabulary/create', [VocabularyController::class, 'create'])->name('vocabulary.create');
        Route::post('/vocabulary', [VocabularyController::class, 'store'])->name('vocabulary.store');
        Route::get('/vocabulary/{conceptId}/edit', [VocabularyController::class, 'edit'])->name('vocabulary.edit');
        Route::put('/vocabulary/{conceptId}', [VocabularyController::class, 'update'])->name('vocabulary.update');
        Route::delete('/vocabulary/{conceptId}', [VocabularyController::class, 'destroy'])->name('vocabulary.destroy');
        Route::get('/catalog-terms', [CatalogTermsController::class, 'index'])->name('catalog-terms.index');
        Route::post('/catalog-terms/terms', [CatalogTermsController::class, 'storeTerm'])->name('catalog-terms.store');
        Route::delete('/catalog-terms/terms', [CatalogTermsController::class, 'destroyTerm'])->name('catalog-terms.destroy');
        Route::put('/catalog-terms/proper-nouns', [CatalogTermsController::class, 'updateProperNouns'])->name('catalog-terms.proper-nouns');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::post('/analytics/search', [AnalyticsController::class, 'search'])->name('analytics.search');
    });

    // --- MOW/ROW PWA product ---
    Route::prefix('mow-row')->name('mow-row.')->group(function () {
        Route::get('/catalog', [MowRowController::class, 'catalog'])->name('catalog');
        Route::patch('/catalog/videos/{id}/content-pillar', [MowRowController::class, 'updateContentPillar'])
            ->whereNumber('id')
            ->name('catalog.content-pillar');
        Route::get('/featured', [MowRowController::class, 'featured'])->name('featured');
        Route::get('/namespace-studio', [NamespaceStudioController::class, 'index'])->name('namespace-studio');
        Route::post('/namespace-studio/reconcile', [NamespaceStudioController::class, 'reconcile'])->name('namespace-studio.reconcile');
        Route::post('/namespace-studio/fix-upsert', [NamespaceStudioController::class, 'fixUpsert'])->name('namespace-studio.fix-upsert');
        Route::post('/namespace-studio/fix-delete', [NamespaceStudioController::class, 'fixDelete'])->name('namespace-studio.fix-delete');
        Route::get('/videos/{id}/embedding-text', [NamespaceStudioController::class, 'embeddingText'])
            ->whereNumber('id')
            ->name('embedding-text');
        Route::get('/search', [AiSearchController::class, 'index'])->name('search.index');
        Route::post('/search', [AiSearchController::class, 'search'])->name('search.search');
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
        Route::post('/analytics/search', [AnalyticsController::class, 'search'])->name('analytics.search');
    });

    // Shared video detail + platform tools (legacy paths kept with redirects)
    Route::get('/videos/namespace-studio', fn () => redirect()->route('ai-search.namespace-studio'))->name('videos.namespace-studio');
    Route::post('/videos/namespace-studio/reconcile', [NamespaceStudioController::class, 'reconcile'])->name('videos.namespace-studio.reconcile');
    Route::post('/videos/namespace-studio/fix-upsert', [NamespaceStudioController::class, 'fixUpsert'])->name('videos.namespace-studio.fix-upsert');
    Route::post('/videos/namespace-studio/fix-delete', [NamespaceStudioController::class, 'fixDelete'])->name('videos.namespace-studio.fix-delete');
    Route::get('/videos/{id}/embedding-text', [NamespaceStudioController::class, 'embeddingText'])
        ->whereNumber('id')
        ->name('videos.embedding-text');

    Route::redirect('/videos', '/ai-search/videos')->name('videos.index');
    Route::redirect('/ai-search', '/ai-search/playground');
    Route::get('/videos/search-visible-audio', fn () => redirect()->route('ai-search.search-visible-audio'));
    Route::get('/videos/database', [VideoController::class, 'database'])->name('videos.database');
    Route::get('/videos/embeddings-reconcile', [VideoController::class, 'embeddingReconcile'])->name('videos.embeddings-reconcile');
    Route::get('/videos/database/export', [VideoController::class, 'databaseExport'])->name('videos.database-export');
    Route::get('/videos/{id}', [VideoController::class, 'show'])->whereNumber('id')->name('videos.show');
    Route::post('/videos/{id}/audio-script', [VideoController::class, 'updateAudioScript'])->name('videos.update-audio-script');
    Route::post('/videos/{id}/thumbnail', [VideoController::class, 'updateThumbnail'])->name('videos.update-thumbnail');

    Route::redirect('/query', '/videos/database')->name('query.index');

    Route::redirect('/ai-search-legacy', '/ai-search/playground');
    Route::get('/ai-search-old', fn () => redirect()->route('ai-search.playground.index'))->name('ai-search.index');
    Route::post('/ai-search-old', [AiSearchController::class, 'search'])->name('ai-search.search');
    Route::post('/ai-search/feedback', [AiSearchController::class, 'feedback'])->name('ai-search.feedback');
    Route::post('/ai-search/playground/feedback', [AiSearchController::class, 'feedback'])->name('ai-search.playground.feedback');
    Route::post('/mow-row/search/feedback', [AiSearchController::class, 'feedback'])->name('mow-row.search.feedback');

    Route::get('/analytics', fn () => redirect()->route('ai-search.analytics'))->name('analytics.redirect');
    Route::get('/analytics-old', fn () => redirect()->route('ai-search.analytics'))->name('analytics.index');
    Route::post('/analytics-old', [AnalyticsController::class, 'search'])->name('analytics.search');

    // Sync Logs
    Route::get('/sync-logs', [App\Http\Controllers\SyncLogController::class, 'index'])->name('sync-logs.index');
    Route::post('/sync-logs/trigger', [App\Http\Controllers\SyncLogController::class, 'trigger'])->name('sync-logs.trigger');
    Route::post('/sync-logs/clear', [App\Http\Controllers\SyncLogController::class, 'clear'])->name('sync-logs.clear');

    // Account
    Route::get('/account', [App\Http\Controllers\AccountController::class, 'index'])->name('account.index');
    Route::put('/account', [App\Http\Controllers\AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [App\Http\Controllers\AccountController::class, 'updatePassword'])->name('account.password');

    // User management (admins only)
    Route::middleware(['admin'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::post('/{user}/send-reset-link', [UserManagementController::class, 'sendResetLink'])->name('send-reset-link');
    });
});
