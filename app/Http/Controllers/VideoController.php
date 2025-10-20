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
            
            // API returns a direct array of videos, not wrapped in 'videos' key
            if (isset($response['videos'])) {
                // Wrapped format (with total)
                $videos = $response['videos'];
                $total = $response['total'] ?? count($videos);
            } else {
                // Direct array format
                $videos = $response;
                $total = count($videos);
            }

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

            // Get full video details including embeddings and audio
            $response = $api->getVideoById($id);
            
            // The API returns a nested structure: {status, video, embeddings, audio_previews}
            $video = $response['video'] ?? $response;
            $embeddings = $response['embeddings'] ?? [];
            $audioPreviews = $response['audio_previews'] ?? [];

            // Get related videos
            $relatedVideos = [];
            try {
                $relatedVideos = $api->getRelatedVideos($id, 6);
            } catch (\Exception $e) {
                Log::warning('Failed to get related videos', ['video_id' => $id]);
            }

            // Get audio preview presigned URL if available
            $audioUrl = null;
            if (!empty($audioPreviews)) {
                $audioPreview = $audioPreviews[0];
                if (!empty($audioPreview['s3_key'])) {
                    try {
                        // Use presigned URL for private buckets
                        $presignedResponse = $api->getPresignedUrl($audioPreview['s3_key'], 3600);
                        $audioUrl = $presignedResponse['presigned_url'] ?? $presignedResponse['url'] ?? null;
                    } catch (\Exception $e) {
                        Log::warning('Failed to get presigned URL', [
                            's3_key' => $audioPreview['s3_key'],
                            'error' => $e->getMessage()
                        ]);
                        
                        // Fallback: try using s3_url directly (might work for some buckets)
                        $audioUrl = $audioPreview['s3_url'] ?? null;
                    }
                }
            }

            return view('videos.show', compact('video', 'embeddings', 'audioPreviews', 'relatedVideos', 'audioUrl'));

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
