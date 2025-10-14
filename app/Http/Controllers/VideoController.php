<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
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
     * Display video library with filters
     */
    public function index(Request $request)
    {
        try {
            $api = $this->getApiService();

            // Get filter parameters from request
            $filters = [
                'limit' => $request->input('limit', 24),
                'offset' => $request->input('offset', 0),
                'category' => $request->input('category'),
                'difficulty' => $request->input('difficulty'),
                'instructor' => $request->input('instructor'),
                'search' => $request->input('search'),
                'sort_by' => $request->input('sort_by', 'created_at'),
                'sort_order' => $request->input('sort_order', 'desc'),
            ];

            // Remove null values
            $filters = array_filter($filters, function ($value) {
                return $value !== null && $value !== '';
            });

            // Get videos from API
            $response = $api->getVideos($filters);
            $videos = $response['videos'] ?? [];
            $total = $response['total'] ?? 0;

            // Get stats for filters
            $stats = $api->getWordPressStats();
            $categories = $stats['categories'] ?? [];
            $instructors = $stats['instructors'] ?? [];

            // Calculate pagination
            $perPage = $filters['limit'];
            $currentPage = floor($filters['offset'] / $perPage) + 1;
            $totalPages = ceil($total / $perPage);

            return view('videos.index', compact(
                'videos',
                'total',
                'categories',
                'instructors',
                'currentPage',
                'totalPages',
                'filters'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to load videos', [
                'error' => $e->getMessage()
            ]);

            return view('videos.index', [
                'videos' => [],
                'total' => 0,
                'categories' => [],
                'instructors' => [],
                'currentPage' => 1,
                'totalPages' => 1,
                'filters' => [],
                'error' => 'Unable to load videos. Please try again later.'
            ]);
        }
    }

    /**
     * Display single video detail page
     */
    public function show(Request $request, int $id)
    {
        try {
            $api = $this->getApiService();

            // Get video details
            $video = $api->getVideoById($id);

            // Get related videos
            $relatedVideos = $api->getRelatedVideos($id, 6);

            // Get audio preview URL if available
            $audioUrl = null;
            if (!empty($video['audio_s3_key'])) {
                $audioUrl = $api->getAudioPreviewUrl($id);
            }

            return view('videos.show', compact('video', 'relatedVideos', 'audioUrl'));

        } catch (\Exception $e) {
            Log::error('Failed to load video', [
                'video_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('videos.index')
                ->with('error', 'Video not found.');
        }
    }

    /**
     * Get audio preview URL (AJAX endpoint)
     */
    public function getAudioPreview(int $id)
    {
        try {
            $api = $this->getApiService();
            $audioUrl = $api->getAudioPreviewUrl($id);

            return response()->json([
                'success' => true,
                'url' => $audioUrl
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate audio preview URL'
            ], 500);
        }
    }
}
