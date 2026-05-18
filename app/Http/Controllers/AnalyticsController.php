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
    public function index(Request $request)
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

            // Get recent search queries
            $recentQueries = [];
            try {
                $queriesResponse = $api->getRecentQueries(50, 7);
                $recentQueries = $queriesResponse['queries'] ?? [];
            } catch (\Exception $e) {
                Log::warning('Queries endpoint failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            $feedbackDays = $this->resolveFeedbackDays($request);
            $searchFeedback = [];
            $searchFeedbackError = null;
            try {
                $feedbackResponse = $api->getSearchFeedback(200, $feedbackDays);
                $searchFeedback = is_array($feedbackResponse['feedback'] ?? null)
                    ? $feedbackResponse['feedback']
                    : [];
            } catch (\Exception $e) {
                Log::warning('Search feedback endpoint failed', ['error' => $e->getMessage()]);
                $searchFeedbackError = $e->getMessage();
            }

            $feedbackSummary = $this->summarizeFeedback($searchFeedback);

            return view('analytics.index', compact(
                'tenantInfo',
                'quota',
                'analytics',
                'stats',
                'recentQueries',
                'searchFeedback',
                'searchFeedbackError',
                'feedbackDays',
                'feedbackSummary',
            ));

        } catch (\Exception $e) {
            Log::error('Failed to load analytics', [
                'error' => $e->getMessage()
            ]);

            return view('analytics.index', [
                'tenantInfo' => null,
                'quota' => null,
                'analytics' => null,
                'stats' => null,
                'recentQueries' => [],
                'searchFeedback' => [],
                'searchFeedbackError' => null,
                'feedbackDays' => 30,
                'feedbackSummary' => ['up' => 0, 'down' => 0, 'total' => 0],
                'error' => 'Unable to load analytics data.',
            ]);
        }
    }

    private function resolveFeedbackDays(Request $request): int
    {
        $days = (int) $request->query('feedback_days', 30);

        return in_array($days, [7, 30, 90], true) ? $days : 30;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{up: int, down: int, total: int}
     */
    private function summarizeFeedback(array $rows): array
    {
        $up = 0;
        $down = 0;
        foreach ($rows as $row) {
            $vote = (int) ($row['vote'] ?? 0);
            if ($vote === 1) {
                $up++;
            } elseif ($vote === -1) {
                $down++;
            }
        }

        return ['up' => $up, 'down' => $down, 'total' => $up + $down];
    }
}
