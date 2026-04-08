<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VideoController extends Controller
{
    protected BackendApiService $api;

    /** Must match FastAPI VIDEO_LIST_DYNAMIC_FIELDS in wordpress.py */
    private const METADATA_EXPLORER_COLUMNS = [
        'id', 'wp_post_id', 'jwp_id', 'post_type', 'post_status', 'title', 'slug',
        'thumbnail_url', 'instructor', 'body_area', 'helps_with', 'props',
        'short_description', 'long_description', 'content_tags', 'video_category',
        'category_for_ai', 'video_time', 'run_time', 'video_topic',
        'video_body_area_taxonomy', 'sync_status', 'error_message', 'last_processed',
        'wp_created', 'wp_modified', 'created_at', 'updated_at', 'tenant_id',
        'has_embedding', 'has_audio_preview', 'embedding_count',
        'audio_preview_duration_seconds', 'audio_preview_status',
    ];

    private const METADATA_EXPLORER_DEFAULT = [
        'id', 'wp_post_id', 'title', 'run_time', 'video_time', 'sync_status',
    ];

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
     * @return array<string, list<string>>
     */
    protected function metadataExplorerColumnGroups(): array
    {
        return [
            'Identifiers' => ['id', 'wp_post_id', 'jwp_id', 'slug', 'title', 'tenant_id'],
            'Publishing' => ['post_type', 'post_status'],
            'Runtime' => ['run_time', 'video_time'],
            'People and taxonomy' => [
                'instructor', 'body_area', 'video_category', 'category_for_ai',
                'video_topic', 'video_body_area_taxonomy',
            ],
            'Content' => [
                'helps_with', 'props', 'content_tags', 'thumbnail_url',
                'short_description', 'long_description',
            ],
            'Sync and processing' => [
                'sync_status', 'error_message', 'last_processed',
                'wp_created', 'wp_modified', 'created_at', 'updated_at',
            ],
            'Embeddings and audio' => [
                'has_embedding', 'has_audio_preview', 'embedding_count',
                'audio_preview_duration_seconds', 'audio_preview_status',
            ],
        ];
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
                'category_for_ai' => $request->input('category_for_ai'),
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
            $categories_for_ai = $statsResponse['categories_for_ai'] ?? [];
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
                'categories_for_ai',
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
                'categories_for_ai' => [],
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
     * Update video thumbnail URL (manual override)
     */
    public function updateThumbnail(Request $request, int $id)
    {
        $request->validate([
            'thumbnail_url' => 'nullable|string|max:1000',
        ]);

        try {
            $api = $this->getApiService();
            $api->updateVideoThumbnail($id, $request->input('thumbnail_url', ''));

            return redirect()->route('videos.show', $id)
                ->with('success', 'Thumbnail updated. Note: it may be overwritten on the next WordPress sync.');
        } catch (\Exception $e) {
            Log::error('Failed to update thumbnail', [
                'video_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('videos.show', $id)
                ->with('error', 'Failed to update thumbnail: ' . $e->getMessage());
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
     * Column-filterable view of pipeline video rows (GET /api/v1/wordpress/videos?fields=...).
     */
    public function database(Request $request)
    {
        $allowed = self::METADATA_EXPLORER_COLUMNS;
        $allowedSet = array_flip($allowed);

        $cols = $request->input('cols', []);
        if (! is_array($cols)) {
            $cols = [];
        }
        $cols = array_values(array_filter($cols, fn ($c) => is_string($c) && isset($allowedSet[$c])));
        if ($cols === []) {
            $cols = self::METADATA_EXPLORER_DEFAULT;
        }

        $limit = min(100, max(1, (int) $request->input('limit', 50)));
        $offset = max(0, (int) $request->input('offset', 0));

        $filters = [
            'limit' => $limit,
            'offset' => $offset,
            'fields' => implode(',', $cols),
        ];

        if ($request->filled('search')) {
            $filters['search'] = $request->input('search');
        }
        if ($request->filled('status')) {
            $filters['status'] = $request->input('status');
        }
        if ($request->filled('post_type')) {
            $filters['post_type'] = $request->input('post_type');
        }

        try {
            $api = $this->getApiService();
            $response = $api->getVideos($filters);

            $videos = $response['videos'] ?? $response;
            if (! is_array($videos)) {
                $videos = [];
            }
            $total = (int) ($response['total'] ?? count($videos));
            $totalPages = (int) max(1, (int) ceil($total / $limit));
            $currentPage = (int) floor($offset / $limit) + 1;

            return view('videos.database', [
                'videos' => $videos,
                'total' => $total,
                'selectedColumns' => $cols,
                'defaultColumns' => self::METADATA_EXPLORER_DEFAULT,
                'columnGroups' => $this->metadataExplorerColumnGroups(),
                'allowedColumns' => $allowed,
                'limit' => $limit,
                'offset' => $offset,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'filters' => [
                    'search' => $request->input('search', ''),
                    'status' => $request->input('status', ''),
                    'post_type' => $request->input('post_type', ''),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load videos database explorer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('videos.database', [
                'videos' => [],
                'total' => 0,
                'selectedColumns' => $cols,
                'defaultColumns' => self::METADATA_EXPLORER_DEFAULT,
                'columnGroups' => $this->metadataExplorerColumnGroups(),
                'allowedColumns' => $allowed,
                'limit' => $limit,
                'offset' => $offset,
                'currentPage' => 1,
                'totalPages' => 1,
                'filters' => [
                    'search' => $request->input('search', ''),
                    'status' => $request->input('status', ''),
                    'post_type' => $request->input('post_type', ''),
                ],
                'error' => 'Unable to load videos: '.$e->getMessage(),
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
