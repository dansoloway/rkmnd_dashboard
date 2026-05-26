<?php

namespace Tests\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Stub AI Pipeline HTTP responses for feature tests (no real network).
 */
class BackendApiFake
{
    /**
     * @param  array<string, array<string, mixed>|callable(Request): mixed>  $overrides  Path substring => JSON array or callback
     */
    public static function register(array $overrides = []): void
    {
        $base = rtrim((string) config('backend.api_url'), '/');

        Http::fake(function (Request $request) use ($base, $overrides) {
            $url = $request->url();
            if (! str_starts_with($url, $base)) {
                return Http::response(['error' => 'unfaked host'], 404);
            }

            $path = parse_url($url, PHP_URL_PATH) ?? '';

            foreach ($overrides as $needle => $response) {
                if (! str_contains($path, (string) $needle)) {
                    continue;
                }
                if (is_callable($response)) {
                    return $response($request);
                }

                return Http::response($response);
            }

            if (str_contains($path, '/api/v1/wordpress/embeddings/reconcile')) {
                return Http::response(self::reconcilePayload());
            }

            if (str_contains($path, '/api/v1/wordpress/pinecone/vectors/upsert')) {
                return Http::response([
                    'namespace' => 'v6_title_tags',
                    'results' => [
                        [
                            'video_id' => 2,
                            'jwp_id' => 'jwp-missing',
                            'ok' => true,
                            'skipped' => false,
                            'error' => null,
                        ],
                    ],
                    'ok_count' => 1,
                    'failed_count' => 0,
                ]);
            }

            if (str_contains($path, '/api/v1/wordpress/pinecone/vectors/delete')) {
                return Http::response([
                    'namespace' => 'v6_title_tags',
                    'deleted' => ['orphan-vec'],
                    'failed' => [],
                ]);
            }

            if (str_contains($path, '/related')) {
                return Http::response([]);
            }

            if (preg_match('#/api/v1/wordpress/videos/\d+$#', $path)) {
                return Http::response(self::videoDetailPayload());
            }

            if (str_contains($path, '/api/v1/wordpress/videos')) {
                return Http::response(self::videosListPayload());
            }

            if (str_contains($path, '/api/v1/wordpress/stats')) {
                return Http::response(self::statsPayload());
            }

            if (str_contains($path, '/api/v1/namespaces')) {
                return Http::response(self::namespacesPayload());
            }

            if (str_contains($path, '/api/v1/tenant/info')) {
                return Http::response(self::tenantInfoPayload());
            }

            if (str_contains($path, '/api/v1/tenant/quota')) {
                return Http::response(self::tenantQuotaPayload());
            }

            if (str_contains($path, '/api/v1/tenant/analytics')) {
                return Http::response(self::tenantAnalyticsPayload());
            }

            if (str_contains($path, '/api/v1/wordpress/sync/logs')) {
                return Http::response(['logs' => []]);
            }

            if (str_contains($path, '/api/v1/search/feedback')) {
                return Http::response([
                    'status' => 'success',
                    'feedback_id' => 1,
                    'vote' => (int) (json_decode($request->body(), true)['vote'] ?? 1),
                ]);
            }

            if (str_contains($path, '/api/v1/search')) {
                return Http::response(self::searchPayload());
            }

            if (str_contains($path, '/api/v1/tenant/search-feedback/analytics')) {
                $namespace = null;
                $url = $request->url();
                $queryString = parse_url($url, PHP_URL_QUERY);
                if (is_string($queryString) && $queryString !== '') {
                    parse_str($queryString, $qs);
                    $raw = $qs['namespace'] ?? null;
                    if (is_string($raw) && trim($raw) !== '') {
                        $namespace = trim($raw);
                    }
                }

                return Http::response(self::searchFeedbackAnalyticsPayload($namespace));
            }

            if (str_contains($path, '/api/v1/tenant/search-feedback')) {
                return Http::response(self::searchFeedbackPayload());
            }

            if (str_contains($path, '/api/v1/tenant/queries/by-user')) {
                return Http::response(self::queriesByUserPayload());
            }

            if (str_contains($path, '/api/v1/tenant/queries')) {
                return Http::response(self::recentQueriesPayload());
            }

            return Http::response(['status' => 'ok'], 200);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function statsPayload(): array
    {
        return [
            'status' => 'success',
            'stats' => [
                'total_videos' => 2,
                'pending_processing' => 0,
                'completed_processing' => 2,
                'processing_errors' => 0,
                'videos_with_embeddings' => 1,
                'videos_with_audio_previews' => 1,
                'completion_rate' => 100,
            ],
            'categories' => ['Yoga' => 2],
            'categories_for_ai' => ['MBR Class' => 2],
            'instructors' => ['Instructor A' => 2],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function namespacesPayload(): array
    {
        return [
            'namespaces' => ['v6_title_tags', 'v6_title_only', 'v7'],
            'default' => 'v6_title_tags',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function videosListPayload(): array
    {
        return [
            'videos' => [
                [
                    'id' => 1,
                    'wp_post_id' => 100,
                    'jwp_id' => 'jwp-abc',
                    'title' => 'Test Video One',
                    'thumbnail_url' => 'https://example.com/thumb1.jpg',
                    'audio_preview_url' => 'https://example.com/audio1.mp3',
                    'embedding_namespaces' => 'v6_title_tags',
                    'sync_status' => 'completed',
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                    'has_embedding' => true,
                    'has_audio_preview' => true,
                ],
                [
                    'id' => 2,
                    'wp_post_id' => 101,
                    'jwp_id' => 'jwp-def',
                    'title' => 'Test Video Two',
                    'thumbnail_url' => null,
                    'embedding_namespaces' => '',
                    'sync_status' => 'completed',
                    'created_at' => now()->toIso8601String(),
                    'updated_at' => now()->toIso8601String(),
                    'has_embedding' => false,
                    'has_audio_preview' => false,
                ],
            ],
            'total' => 2,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function videoDetailPayload(): array
    {
        return [
            'status' => 'success',
            'default_search_namespace' => 'v6_title_tags',
            'computed_v6_embedding_text' => 'Title: Test Video One',
            'video' => [
                'id' => 1,
                'wp_post_id' => 100,
                'jwp_id' => 'jwp-abc',
                'title' => 'Test Video One',
                'slug' => 'test-video-one',
                'thumbnail_url' => 'https://example.com/thumb1.jpg',
                'instructor' => 'Instructor A',
                'sync_status' => 'completed',
                'post_type' => 'video',
                'run_time' => '10:00',
                'video_time' => 600,
                'created_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
            'computed_v6_embedding_fields' => ['title'],
            'embeddings' => [
                [
                    'id' => 10,
                    'namespace' => 'v6_title_tags',
                    'embedding_text' => 'Title: Test Video One | Tags: yoga',
                    'pinecone_id' => 'jwp-abc',
                ],
            ],
            'audio_previews' => [
                [
                    'id' => 5,
                    's3_key' => 'tenant_1/videos/1/audio_preview.mp3',
                    's3_url' => 'https://example.com/audio1.mp3',
                    'duration_seconds' => 42,
                    'file_size_bytes' => 128000,
                    'voice_id' => 'voice-test',
                    'generation_status' => 'completed',
                    'source_text' => 'Title: Test Video One. A short preview script.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function reconcilePayload(): array
    {
        return [
            'summary' => [
                'namespace' => 'v6_title_tags',
                'pinecone_vector_count' => 42,
                'db_expected_by_category_count' => 2,
                'in_both_count' => 1,
                'missing_from_pinecone_count' => 1,
                'pinecone_not_in_db_count' => 0,
                'pinecone_in_db_but_not_expected_count' => 0,
            ],
            'missing_from_pinecone' => [
                [
                    'video_id' => 2,
                    'jwp_id' => 'jwp-missing',
                    'wp_post_id' => 102,
                    'title' => 'Missing From Pinecone',
                    'category_for_ai' => 'MBR Class',
                    'post_status' => 'publish',
                    'post_type' => 'video',
                    'tenant_id' => 1,
                ],
            ],
            'pinecone_not_in_db' => [
                ['jwp_id' => 'orphan-vec'],
            ],
            'pinecone_unexpected' => [
                [
                    'video_id' => 1,
                    'jwp_id' => 'jwp-unexpected',
                    'wp_post_id' => 100,
                    'title' => 'Unexpected Vector',
                    'category_for_ai' => 'MBR Class',
                    'post_status' => 'draft',
                    'post_type' => 'video',
                    'tenant_id' => 1,
                    'reason' => 'post_status=draft',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function tenantInfoPayload(): array
    {
        return [
            'name' => 'test_client',
            'display_name' => 'Test Client',
            'plan_type' => 'pro',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function tenantQuotaPayload(): array
    {
        return [
            'quota' => ['used' => 10, 'limit' => 1000],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function tenantAnalyticsPayload(): array
    {
        return [
            'analytics' => ['searches' => 0],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function queriesByUserPayload(): array
    {
        return [
            'users' => [
                [
                    'user_name' => 'Jane Member',
                    'user_email' => 'jane@example.com',
                    'search_count' => 2,
                    'last_search_at' => now()->subHour()->toIso8601String(),
                    'queries' => [
                        [
                            'query' => 'shoulder pain',
                            'search_id' => 'test-search-session-001',
                            'timestamp' => now()->subHour()->toIso8601String(),
                            'result_count' => 1,
                            'namespace' => 'v6_title_tags',
                        ],
                    ],
                ],
            ],
            'count' => 1,
            'days' => 7,
        ];
    }

    public static function recentQueriesPayload(): array
    {
        return [
            'queries' => [
                [
                    'query' => 'shoulder pain',
                    'search_id' => 'test-search-session-001',
                    'timestamp' => now()->subHour()->toIso8601String(),
                    'result_count' => 1,
                    'namespace' => 'v6_title_tags',
                    'user_name' => 'Jane Member',
                    'user_email' => 'jane@example.com',
                    'results' => [
                        [
                            'title' => 'Hip Mobility Flow',
                            'wp_post_id' => 5001,
                            'video_id' => 81,
                            'score' => 0.91,
                        ],
                    ],
                ],
            ],
            'count' => 1,
            'days' => 7,
        ];
    }

    public static function searchFeedbackAnalyticsPayload(?string $namespace = null): array
    {
        $detail = self::searchFeedbackPayload()['feedback'];
        $detail[0]['video_title'] = 'Hip Mobility Flow';
        $detail[0]['search_created_at'] = now()->subHours(3)->toIso8601String();

        if ($namespace !== null && $namespace !== '') {
            $detail = array_values(array_filter(
                $detail,
                fn (array $row) => ($row['namespace'] ?? '') === $namespace
            ));
        }

        return [
            'days' => 30,
            'namespace' => $namespace !== null && $namespace !== '' ? $namespace : null,
            'summary' => [
                'up' => 1,
                'down' => 1,
                'total' => 2,
                'video_ratings' => 1,
                'query_level_ratings' => 1,
                'distinct_queries' => 2,
                'distinct_videos' => 1,
            ],
            'by_query' => [
                [
                    'query' => 'hip mobility for runners',
                    'namespace' => 'v6_title_tags',
                    'up' => 1,
                    'down' => 0,
                    'total' => 1,
                    'last_rated_at' => now()->subHours(2)->toIso8601String(),
                    'whole_search_votes' => [],
                    'video_ratings' => [$detail[0]],
                ],
                [
                    'query' => 'shoulder pain relief',
                    'namespace' => 'v6_title_tags',
                    'up' => 0,
                    'down' => 1,
                    'total' => 1,
                    'last_rated_at' => now()->subDay()->toIso8601String(),
                    'whole_search_votes' => [
                        ['vote' => -1, 'search_id' => 'test-search-session-002', 'updated_at' => now()->subDay()->toIso8601String()],
                    ],
                    'video_ratings' => [],
                ],
            ],
            'by_video' => [
                [
                    'wp_post_id' => 5001,
                    'video_id' => 81,
                    'video_title' => 'Hip Mobility Flow',
                    'up' => 1,
                    'down' => 0,
                    'total' => 1,
                    'last_rated_at' => now()->subHours(2)->toIso8601String(),
                    'query_ratings' => [$detail[0]],
                ],
            ],
            'detail' => $detail,
        ];
    }

    public static function searchFeedbackPayload(): array
    {
        return [
            'feedback' => [
                [
                    'id' => 1,
                    'search_id' => 'test-search-session-001',
                    'query' => 'hip mobility for runners',
                    'namespace' => 'v6_title_tags',
                    'wp_post_id' => 5001,
                    'video_id' => 81,
                    'rank' => 1,
                    'pinecone_score' => 0.9123,
                    'vote' => 1,
                    'source' => 'dashboard',
                    'search_created_at' => now()->subHours(3)->toIso8601String(),
                    'updated_at' => now()->subHours(2)->toIso8601String(),
                ],
                [
                    'id' => 2,
                    'search_id' => 'test-search-session-002',
                    'query' => 'shoulder pain relief',
                    'namespace' => 'v6_title_tags',
                    'wp_post_id' => null,
                    'rank' => null,
                    'pinecone_score' => null,
                    'vote' => -1,
                    'source' => 'dashboard',
                    'updated_at' => now()->subDay()->toIso8601String(),
                ],
            ],
            'count' => 2,
            'days' => 30,
        ];
    }

    public static function searchPayload(): array
    {
        return [
            'status' => 'success',
            'search_id' => 'test-search-session-001',
            'videos' => [
                [
                    'score' => 0.91,
                    'metadata' => [
                        'title' => 'Hip Mobility Flow',
                        'wp_post_id' => 5001,
                        'video_id' => 81,
                        'slug' => 'hip-mobility-flow',
                        'instructor' => 'Jane Doe',
                    ],
                ],
            ],
        ];
    }
}
