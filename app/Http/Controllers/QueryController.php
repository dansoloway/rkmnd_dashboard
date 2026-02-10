<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QueryController extends Controller
{
    /**
     * Query the AI Pipeline database
     */
    public function index(Request $request)
    {
        try {
            $api = $this->getApiService();

            // Get query parameters
            $filters = [
                'limit' => $request->input('limit', 50),
                'offset' => $request->input('offset', 0),
                'search' => $request->input('search'),
                'category' => $request->input('category'),
                'instructor' => $request->input('instructor'),
                'post_type' => $request->input('post_type'),
                'status' => $request->input('status'), // sync_status
                'sort_by' => $request->input('sort_by', 'wp_post_id'),
                'sort_order' => $request->input('sort_order', 'asc'),
            ];

            // Remove null/empty values
            $filters = array_filter($filters, function ($value) {
                return $value !== null && $value !== '';
            });

            // Get videos from API
            $response = $api->getVideos($filters);
            
            // Handle response format
            if (isset($response['videos'])) {
                $videos = $response['videos'];
                $total = $response['total'] ?? count($videos);
            } else {
                $videos = is_array($response) ? $response : [];
                $total = count($videos);
            }

            // Get stats for filters and total count
            $stats = $api->getWordPressStats();
            $categories = $stats['categories'] ?? [];
            $instructors = $stats['instructors'] ?? [];
            $totalInDatabase = $stats['stats']['total_videos'] ?? 0;

            // Calculate pagination
            $perPage = $filters['limit'] ?? 50;
            $currentPage = floor(($filters['offset'] ?? 0) / $perPage) + 1;
            $totalPages = ceil($total / $perPage);

            return view('query.index', compact(
                'videos',
                'total',
                'totalInDatabase',
                'categories',
                'instructors',
                'currentPage',
                'totalPages',
                'filters'
            ));

        } catch (\Exception $e) {
            Log::error('Query failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('query.index', [
                'videos' => [],
                'total' => 0,
                'totalInDatabase' => 0,
                'categories' => [],
                'instructors' => [],
                'currentPage' => 1,
                'totalPages' => 1,
                'filters' => [],
                'error' => 'Query failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get API service instance
     */
    protected function getApiService(): BackendApiService
    {
        $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');
        return new BackendApiService($apiKey);
    }
}
