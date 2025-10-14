<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    protected BackendApiService $api;

    public function __construct()
    {
        // API service will be initialized in each method
    }

    /**
     * Get API service instance with tenant's API key
     */
    protected function getApiService(): BackendApiService
    {
        $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');
        return new BackendApiService($apiKey);
    }

    /**
     * Display analytics dashboard
     */
    public function index()
    {
        try {
            $api = $this->getApiService();

            // Get tenant info
            $tenantInfo = $api->getTenantInfo();

            // Get quota information
            $quota = $api->getTenantQuota();

            // Get analytics (if available)
            $analytics = null;
            try {
                $analytics = $api->getTenantAnalytics();
            } catch (\Exception $e) {
                // Analytics endpoint might not be available
                Log::info('Analytics endpoint not available');
            }

            // Get WordPress stats
            $stats = null;
            try {
                $stats = $api->getWordPressStats();
            } catch (\Exception $e) {
                Log::info('Stats endpoint not available');
            }

            return view('analytics.index', compact('tenantInfo', 'quota', 'analytics', 'stats'));

        } catch (\Exception $e) {
            Log::error('Failed to load analytics', [
                'error' => $e->getMessage()
            ]);

            return view('analytics.index', [
                'tenantInfo' => null,
                'quota' => null,
                'analytics' => null,
                'stats' => null,
                'error' => 'Unable to load analytics data.'
            ]);
        }
    }
}
