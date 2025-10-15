<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class BackendApiService
{
    protected string $baseUrl;
    protected int $timeout;
    protected string $apiKey;

    public function __construct(?string $apiKey = null)
    {
        $this->baseUrl = config('backend.api_url');
        $this->timeout = config('backend.timeout');
        $this->apiKey = $apiKey ?? config('backend.default_api_key');
    }

    /**
     * Set the API key for this instance
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * Make an authenticated request to the backend API
     */
    protected function makeRequest(string $method, string $endpoint, array $params = [], int $cacheTtl = 0)
    {
        $cacheKey = $cacheTtl > 0 ? $this->getCacheKey($endpoint, $params) : null;

        // Return cached response if available
        if ($cacheKey && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->$method($this->baseUrl . $endpoint, $params);

            if ($response->successful()) {
                $data = $response->json();
                
                // Cache successful responses
                if ($cacheKey && $cacheTtl > 0) {
                    Cache::put($cacheKey, $data, $cacheTtl);
                }
                
                return $data;
            }

            // Log error responses
            Log::error('Backend API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            throw new Exception("API request failed: {$response->status()}");

        } catch (Exception $e) {
            Log::error('Backend API Exception', [
                'endpoint' => $endpoint,
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate cache key for requests
     */
    protected function getCacheKey(string $endpoint, array $params): string
    {
        return 'backend_api:' . md5($endpoint . serialize($params) . $this->apiKey);
    }

    // ==========================================
    // TENANT ENDPOINTS
    // ==========================================

    /**
     * Get tenant information
     * Cache: 5 minutes
     */
    public function getTenantInfo(): array
    {
        return $this->makeRequest('get', '/api/v1/tenant/info', [], 300);
    }

    /**
     * Get tenant analytics
     * Cache: 10 minutes
     */
    public function getTenantAnalytics(): array
    {
        return $this->makeRequest('get', '/api/v1/tenant/analytics', [], 600);
    }

    /**
     * Get tenant quota information
     * Cache: 10 minutes
     */
    public function getTenantQuota(): array
    {
        return $this->makeRequest('get', '/api/v1/tenant/quota', [], 600);
    }

    // ==========================================
    // VIDEO ENDPOINTS
    // ==========================================

    /**
     * Get list of videos with filters
     * 
     * @param array $filters Supported filters:
     *  - limit: int (default 50)
     *  - offset: int (default 0)
     *  - category: string
     *  - difficulty: string
     *  - duration_min: int
     *  - duration_max: int
     *  - instructor: string
     *  - search: string
     *  - sort_by: string (created_at, title, duration, instructor)
     *  - sort_order: string (asc, desc)
     * 
     * Cache: 2 minutes
     */
    public function getVideos(array $filters = []): array
    {
        // Build query parameters
        $params = array_filter($filters);
        
        return $this->makeRequest('get', '/api/v1/wordpress/videos', $params, 120);
    }

    /**
     * Get single video details by ID
     * Cache: 5 minutes
     */
    public function getVideoById(int $videoId): array
    {
        $endpoint = "/api/v1/wordpress/videos/{$videoId}";
        return $this->makeRequest('get', $endpoint, [], 300);
    }

    /**
     * Get WordPress stats (video count, categories, etc.)
     * Cache: 10 minutes
     */
    public function getWordPressStats(): array
    {
        return $this->makeRequest('get', '/api/v1/wordpress/stats', [], 600);
    }

    /**
     * Search videos using AI semantic search
     * No cache - always get fresh results
     */
    public function searchVideos(string $query, int $limit = 10): array
    {
        return $this->makeRequest('post', '/api/v1/search/semantic', [
            'query' => $query,
            'limit' => $limit
        ]);
    }

    /**
     * Get related videos based on a video ID
     * Cache: 5 minutes
     */
    public function getRelatedVideos(int $videoId, int $limit = 6): array
    {
        return $this->makeRequest('get', "/api/v1/wordpress/videos/{$videoId}/related", [
            'limit' => $limit
        ], 300);
    }

    // ==========================================
    // S3 / AUDIO PREVIEW ENDPOINTS
    // ==========================================

    /**
     * Get public URL for audio file
     * No cache - public URLs don't expire
     * 
     * @param string $audioKey S3 key like "tenant_1/videos/123/audio_preview.mp3"
     */
    public function getPublicUrl(string $audioKey): array
    {
        return $this->makeRequest('get', "/api/v1/s3/public-url/" . urlencode($audioKey));
    }

    /**
     * Get presigned URL for audio file
     * No cache - generate fresh URLs (they expire in 1 hour)
     * 
     * @param string $audioKey S3 key like "audio/123_preview.mp3"
     * @param int $expiresIn Expiration time in seconds (default 3600 = 1 hour)
     */
    public function getPresignedUrl(string $audioKey, int $expiresIn = 3600): array
    {
        return $this->makeRequest('post', '/api/v1/s3/presigned-url', [
            's3_key' => $audioKey,
            'expiration' => $expiresIn
        ]);
    }

    /**
     * Get S3 bucket information
     * Cache: 10 minutes
     */
    public function getS3Info(): array
    {
        return $this->makeRequest('get', '/api/v1/s3/info', [], 600);
    }

    /**
     * List files in S3 bucket with optional prefix
     * Cache: 5 minutes
     */
    public function listS3Files(?string $prefix = null, int $maxKeys = 100): array
    {
        $params = ['max_keys' => $maxKeys];
        if ($prefix) {
            $params['prefix'] = $prefix;
        }
        
        return $this->makeRequest('get', '/api/v1/s3/files', $params, 300);
    }

    /**
     * Helper: Get audio preview URL for a video
     * This combines video info with presigned URL generation
     */
    public function getAudioPreviewUrl(int $videoId): ?string
    {
        try {
            $video = $this->getVideoById($videoId);
            
            // Check if video has audio preview
            if (empty($video['audio_s3_key'])) {
                return null;
            }
            
            $presigned = $this->getPresignedUrl($video['audio_s3_key']);
            
            return $presigned['url'] ?? null;
            
        } catch (Exception $e) {
            Log::error('Failed to get audio preview URL', [
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    // ==========================================
    // HEALTH CHECK
    // ==========================================

    /**
     * Check backend API health
     * No cache - always get current status
     */
    public function healthCheck(): array
    {
        return $this->makeRequest('get', '/health/detailed', []);
    }

    /**
     * Simple health check (returns boolean)
     */
    public function isHealthy(): bool
    {
        try {
            $health = $this->healthCheck();
            return $health['status'] === 'healthy';
        } catch (Exception $e) {
            return false;
        }
    }

    // ==========================================
    // CACHE MANAGEMENT
    // ==========================================

    /**
     * Clear all cached API responses
     */
    public function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * Clear cache for specific endpoint
     */
    public function clearEndpointCache(string $endpoint, array $params = []): void
    {
        $cacheKey = $this->getCacheKey($endpoint, $params);
        Cache::forget($cacheKey);
    }
}


