<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Protected routes (require authentication)
Route::middleware(['auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/clear-cache', [\App\Http\Controllers\DashboardController::class, 'clearCache'])->name('dashboard.clear-cache');
    
    // Videos
    Route::get('/videos/namespace-studio', [App\Http\Controllers\NamespaceStudioController::class, 'index'])->name('videos.namespace-studio');
    Route::post('/videos/namespace-studio/reconcile', [App\Http\Controllers\NamespaceStudioController::class, 'reconcile'])->name('videos.namespace-studio.reconcile');
    Route::get('/videos/{id}/embedding-text', [App\Http\Controllers\NamespaceStudioController::class, 'embeddingText'])
        ->whereNumber('id')
        ->name('videos.embedding-text');
    Route::get('/videos', [App\Http\Controllers\VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/search-visible-audio', [App\Http\Controllers\VideoController::class, 'searchVisibleAudio'])->name('videos.search-visible-audio');
    Route::post('/videos/{id}/audio-script', [App\Http\Controllers\VideoController::class, 'updateAudioScript'])->name('videos.update-audio-script');
    Route::get('/videos/database', [App\Http\Controllers\VideoController::class, 'database'])->name('videos.database');
    Route::get('/videos/embeddings-reconcile', [App\Http\Controllers\VideoController::class, 'embeddingReconcile'])->name('videos.embeddings-reconcile');
    Route::get('/videos/database/export', [App\Http\Controllers\VideoController::class, 'databaseExport'])->name('videos.database-export');
    Route::get('/videos/{id}', [App\Http\Controllers\VideoController::class, 'show'])->name('videos.show');
    Route::post('/videos/{id}/thumbnail', [App\Http\Controllers\VideoController::class, 'updateThumbnail'])->name('videos.update-thumbnail');
    
    // Query AI Pipeline
    Route::get('/query', [App\Http\Controllers\QueryController::class, 'index'])->name('query.index');

    // Semantic search (POST /api/v1/search playground)
    Route::get('/ai-search', [App\Http\Controllers\AiSearchController::class, 'index'])->name('ai-search.index');
    Route::post('/ai-search', [App\Http\Controllers\AiSearchController::class, 'search'])->name('ai-search.search');
    Route::post('/ai-search/feedback', [App\Http\Controllers\AiSearchController::class, 'feedback'])->name('ai-search.feedback');

    // Analytics
    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::post('/analytics/search', [App\Http\Controllers\AnalyticsController::class, 'search'])->name('analytics.search');
    
    // Sync Logs
    Route::get('/sync-logs', [App\Http\Controllers\SyncLogController::class, 'index'])->name('sync-logs.index');
    Route::post('/sync-logs/trigger', [App\Http\Controllers\SyncLogController::class, 'trigger'])->name('sync-logs.trigger');
    Route::post('/sync-logs/clear', [App\Http\Controllers\SyncLogController::class, 'clear'])->name('sync-logs.clear');
    
    // Account
    Route::get('/account', [App\Http\Controllers\AccountController::class, 'index'])->name('account.index');
    Route::put('/account', [App\Http\Controllers\AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [App\Http\Controllers\AccountController::class, 'updatePassword'])->name('account.password');
});
