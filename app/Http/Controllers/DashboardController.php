<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            // Get recent videos (latest 6)
            $recentVideos = $api->getVideos([
                'limit' => 6,
                'offset' => 0,
                'sort_by' => 'created_at',
                'sort_order' => 'desc'
            ]);

            return view('dashboard', [
                'stats' => $stats['stats'] ?? [],
                'tenant' => $tenantInfo,
                'quota' => $quota,
                'recentVideos' => $recentVideos
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
}

