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
                'post_type' => $request->input('post_type'),
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
            $statsResponse = $api->getWordPressStats();
            // Categories and instructors are at the top level of the response
            $categories = $statsResponse['categories'] ?? [];
            $instructors = $statsResponse['instructors'] ?? [];
            
            // Debug: Log if categories are missing
            if (empty($categories)) {
                Log::warning('No categories found in stats response', [
                    'response_keys' => array_keys($statsResponse),
                    'has_stats_key' => isset($statsResponse['stats']),
                    'has_categories_key' => isset($statsResponse['categories'])
                ]);
            }

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

            // Get audio preview URL (use direct s3_url for public buckets)
            $audioUrl = null;
            if (!empty($audioPreviews)) {
                $audioPreview = $audioPreviews[0];
                // Use the s3_url directly (bucket is public)
                $audioUrl = $audioPreview['s3_url'] ?? null;
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

    /**
     * Display all videos from database with URL validation
     */
    public function database(Request $request)
    {
        try {
            $api = $this->getApiService();

            // Get first 10 videos for now (to avoid slow loading)
            $filters = [
                'limit' => 10,
                'offset' => 0,
            ];

            $response = $api->getVideos($filters);
            
            // API returns a direct array of videos, not wrapped in 'videos' key
            if (isset($response['videos'])) {
                $videos = $response['videos'];
            } else {
                $videos = $response;
            }

            // Process videos and fetch details for each to get thumbnail and audio URLs
            $processedVideos = [];
            $totalVideos = count($videos);
            
            foreach ($videos as $index => $video) {
                $videoId = $video['id'] ?? null;
                $wpPostId = $video['wp_post_id'] ?? null;
                $title = $video['title'] ?? 'Untitled';
                $videoCategory = $video['video_category'] ?? 'N/A';
                
                $thumbnail = null;
                $audioFile = null;
                
                // Fetch individual video details to get thumbnail and audio URLs
                if ($videoId) {
                    try {
                        $videoDetails = $api->getVideoById($videoId);
                        $videoData = $videoDetails['video'] ?? $videoDetails;
                        
                        // Get thumbnail URL from API response, or fallback to fetching from WordPress
                        $thumbnail = $videoData['thumbnail_url'] ?? null;
                        
                        if (!$thumbnail && $wpPostId) {
                            // Fallback: try to fetch from WordPress REST API
                            $thumbnail = $this->getWordPressThumbnailUrl($wpPostId, $title);
                        }
                        
                        // Extract audio from audio_previews
                        $audioPreviews = $videoDetails['audio_previews'] ?? [];
                        if (!empty($audioPreviews) && is_array($audioPreviews)) {
                            $firstAudio = $audioPreviews[0];
                            $audioFile = $firstAudio['s3_url'] ?? $firstAudio['url'] ?? null;
                        }
                        
                        // Also check for video_category in details if not found in list
                        if ($videoCategory === 'N/A' && isset($videoData['video_category'])) {
                            $videoCategory = $videoData['video_category'];
                        }
                        
                    } catch (\Exception $e) {
                        Log::warning('Failed to fetch video details', [
                            'video_id' => $videoId,
                            'error' => $e->getMessage()
                        ]);
                        // Continue with null values
                    }
                }
                
                // Check if URLs exist
                $thumbnailExists = $this->checkUrlExists($thumbnail);
                $audioExists = $this->checkUrlExists($audioFile);
                
                $processedVideos[] = [
                    'id' => $videoId,
                    'wp_post_id' => $wpPostId,
                    'title' => $title,
                    'video_category' => $videoCategory,
                    'thumbnail' => $thumbnail,
                    'thumbnail_exists' => $thumbnailExists,
                    'audio_file' => $audioFile,
                    'audio_exists' => $audioExists,
                ];
                
                // Log progress every 50 videos
                if (($index + 1) % 50 === 0) {
                    Log::info('Processing videos database', [
                        'progress' => ($index + 1) . '/' . $totalVideos
                    ]);
                }
            }

            // Sort by wp_post_id
            usort($processedVideos, function($a, $b) {
                return ($a['wp_post_id'] ?? 0) <=> ($b['wp_post_id'] ?? 0);
            });

            return view('videos.database', [
                'videos' => $processedVideos,
                'total' => count($processedVideos),
                'thumbnail_valid' => count(array_filter($processedVideos, fn($v) => $v['thumbnail_exists'])),
                'audio_valid' => count(array_filter($processedVideos, fn($v) => $v['audio_exists'])),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load videos database', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('videos.database', [
                'videos' => [],
                'total' => 0,
                'thumbnail_valid' => 0,
                'audio_valid' => 0,
                'error' => 'Unable to load videos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Check if a URL exists (returns HTTP 200)
     */
    private function checkUrlExists(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode >= 200 && $httpCode < 400;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get WordPress thumbnail URL for a video post
     * WordPress stores thumbnails in wp-content/uploads/{year}/{month}/{filename}
     */
    protected function getWordPressThumbnailUrl(?int $wpPostId, string $title = ''): ?string
    {
        if (!$wpPostId) {
            return null;
        }

        // Try WordPress REST API first (if available)
        // WordPress custom post types might use different endpoints
        $wpRestUrl = config('wordpress.rest_api_url', 'https://www.tuneupfitness.com/wp-json/wp/v2');
        
        try {
            // Try 'videos' endpoint first (plural)
            $response = \Illuminate\Support\Facades\Http::timeout(3)
                ->get("{$wpRestUrl}/videos/{$wpPostId}", [
                    '_fields' => 'id,featured_media'
                ]);
            
            // If that fails, try 'video' (singular) or 'posts'
            if (!$response->successful()) {
                $response = \Illuminate\Support\Facades\Http::timeout(3)
                    ->get("{$wpRestUrl}/video/{$wpPostId}", [
                        '_fields' => 'id,featured_media'
                    ]);
            }
            
            if (!$response->successful()) {
                $response = \Illuminate\Support\Facades\Http::timeout(3)
                    ->get("{$wpRestUrl}/posts/{$wpPostId}", [
                        '_fields' => 'id,featured_media'
                    ]);
            }
            
            if ($response->successful()) {
                $data = $response->json();
                $featuredMediaId = $data['featured_media'] ?? null;
                
                if ($featuredMediaId && $featuredMediaId > 0) {
                    // Get attachment URL
                    $mediaResponse = \Illuminate\Support\Facades\Http::timeout(3)
                        ->get("{$wpRestUrl}/media/{$featuredMediaId}", [
                            '_fields' => 'source_url'
                        ]);
                    
                    if ($mediaResponse->successful()) {
                        $mediaData = $mediaResponse->json();
                        $thumbnailUrl = $mediaData['source_url'] ?? null;
                        
                        if ($thumbnailUrl) {
                            Log::debug('Got thumbnail from WordPress REST API', [
                                'wp_post_id' => $wpPostId,
                                'featured_media_id' => $featuredMediaId,
                                'thumbnail_url' => $thumbnailUrl
                            ]);
                            return $thumbnailUrl;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::debug('WordPress REST API not available, trying URL construction', [
                'wp_post_id' => $wpPostId,
                'error' => $e->getMessage()
            ]);
        }

        // Fallback: Construct most likely URL pattern
        // Pattern: https://www.tuneupfitness.com/wp-content/uploads/{year}/{month}/{slug}-{size}.webp
        // Based on user's example: .../Armpit_Rollout_A_Secret_Source_of_Shoulder_Pain-copy-457x257.webp
        $baseUrl = 'https://www.tuneupfitness.com/wp-content/uploads';
        $currentYear = date('Y');
        $currentMonth = date('m');
        
        // Generate slug from title (matching WordPress pattern)
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug); // WordPress uses underscores in filenames
        $slug = trim($slug, '_');
        
        // Try most common pattern: {slug}-copy-457x257.webp (based on user's example)
        $url = "{$baseUrl}/{$currentYear}/{$currentMonth}/{$slug}-copy-457x257.webp";
        
        // Return the most likely URL (browser will handle 404 if wrong)
        return $url;
    }
}
