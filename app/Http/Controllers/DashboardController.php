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

            // Get recent videos - sort by updated_at to show recently synced videos
            // Clear cache to ensure fresh data
            $api->clearEndpointCache('/api/v1/wordpress/videos', [
                'limit' => 20,
                'offset' => 0,
                'sort_by' => 'updated_at',
                'sort_order' => 'desc'
            ]);
            
            // Get videos sorted by updated_at (when they were synced/updated in AI Pipeline)
            $allVideos = $api->getVideos([
                'limit' => 20,
                'offset' => 0,
                'sort_by' => 'updated_at',
                'sort_order' => 'desc'
            ]);
            
            // Take first 6 for display (most recently updated/synced)
            $videosList = isset($allVideos['videos']) ? $allVideos['videos'] : (is_array($allVideos) ? $allVideos : []);
            $recentVideosRaw = array_slice($videosList, 0, 6);
            
            // Use thumbnail_url from API response (if available)
            $recentVideos = [];
            foreach ($recentVideosRaw as $video) {
                // Use thumbnail_url from API, or fallback to fetching from WordPress
                $thumbnail = $video['thumbnail_url'] ?? null;
                
                if (!$thumbnail && !empty($video['wp_post_id'])) {
                    // Fallback: try to fetch from WordPress REST API
                    $thumbnail = $this->getWordPressThumbnailUrl($video['wp_post_id'], $video['title'] ?? '');
                }
                
                $video['thumbnail'] = $thumbnail;
                $recentVideos[] = $video;
            }

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
        
        // Also try without "-copy" suffix
        $urlAlt = "{$baseUrl}/{$currentYear}/{$currentMonth}/{$slug}-457x257.webp";
        
        // Return the most likely URL (browser will handle 404 if wrong)
        // We prioritize the "-copy" pattern based on user's example
        return $url;
    }

}

