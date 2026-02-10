<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function index(Request $request)
    {
        try {
            // Get API service with tenant's API key
            $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');
            $api = new BackendApiService($apiKey);

            // Get stats from backend
            $stats = $api->getWordPressStats();
            $tenantInfo = $api->getTenantInfo();
            $quota = $api->getTenantQuota();

            // Get recent videos - clear cache and don't sort to show all videos
            // This ensures newly synced videos appear on dashboard
            $api->clearEndpointCache('/api/v1/wordpress/videos', [
                'limit' => 20,
                'offset' => 0
            ]);
            
            // Get videos without sorting to show all (newly synced videos will be included)
            $allVideos = $api->getVideos([
                'limit' => 20,
                'offset' => 0
            ]);
            
            // Take first 6 for display (or all if less than 6)
            $recentVideos = is_array($allVideos) ? array_slice($allVideos, 0, 6) : [];

            // Get latest sync log to show sync status
            $syncLogs = [];
            try {
                $syncLogsResponse = $api->getSyncLogs(1); // Get just the latest one
                $syncLogs = $syncLogsResponse['logs'] ?? [];
            } catch (\Exception $e) {
                Log::warning('Failed to get sync logs for dashboard', [
                    'error' => $e->getMessage()
                ]);
            }

            return view('dashboard', [
                'stats' => $stats['stats'] ?? [],
                'tenant' => $tenantInfo,
                'quota' => $quota,
                'recentVideos' => $recentVideos,
                'latestSync' => !empty($syncLogs) ? $syncLogs[0] : null
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard load failed', [
                'error' => $e->getMessage()
            ]);

            return view('dashboard', [
                'stats' => [
                    'total_videos' => 0,
                    'videos_with_embeddings' => 0,
                    'videos_with_audio_previews' => 0,
                    'completion_rate' => 0
                ],
                'tenant' => [],
                'quota' => [],
                'recentVideos' => [],
                'error' => 'Unable to load dashboard data. Please try again later.'
            ]);
        }
    }

    /**
     * Clear all cached statistics
     */
    public function clearCache(Request $request)
    {
        try {
            // Clear Laravel cache
            Cache::flush();
            
            // Clear API service cache
            $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');
            $api = new BackendApiService($apiKey);
            $api->clearCache();
            
            return redirect()->route('dashboard')->with('success', 'Statistics cache cleared successfully!');
            
        } catch (\Exception $e) {
            Log::error('Cache clear failed', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('dashboard')->with('error', 'Failed to clear cache: ' . $e->getMessage());
        }
    }
}

