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
    Route::get('/videos', [App\Http\Controllers\VideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/{id}', [App\Http\Controllers\VideoController::class, 'show'])->name('videos.show');
    
    // Analytics
    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
    
    // Sync Logs
    Route::get('/sync-logs', [App\Http\Controllers\SyncLogController::class, 'index'])->name('sync-logs.index');
    Route::post('/sync-logs/trigger', [App\Http\Controllers\SyncLogController::class, 'trigger'])->name('sync-logs.trigger');
    Route::post('/sync-logs/clear', [App\Http\Controllers\SyncLogController::class, 'clear'])->name('sync-logs.clear');
    
    // Account
    Route::get('/account', [App\Http\Controllers\AccountController::class, 'index'])->name('account.index');
    Route::put('/account', [App\Http\Controllers\AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [App\Http\Controllers\AccountController::class, 'updatePassword'])->name('account.password');
});
