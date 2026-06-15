<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use App\Support\ProductContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    /** @var list<string> */
    private const FALLBACK_NAMESPACES = [
        'v6_title_only',
        'v6_title_tags',
        'v6_title_tags_short',
        'v6_title_tags_long',
        'v6_title_tags_short_long',
        'mow_row_v6_title_tags',
    ];

    protected function getApiService(): BackendApiService
    {
        $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');

        return new BackendApiService($apiKey);
    }

    public function index(Request $request): View
    {
        return $this->renderAnalytics($request);
    }

    public function search(Request $request): View
    {
        $validated = $request->validate([
            'query' => [
                'required',
                'string',
                'max:8192',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        $fail(__('The query cannot be empty.'));
                    }
                },
            ],
            'namespace' => 'nullable|string|max:128',
            'feedback_days' => 'nullable|integer|in:7,30,90',
            'feedback_tab' => 'nullable|string|in:overview,by_query,by_video,detail',
            'feedback_namespace' => 'nullable|string|max:128',
        ]);

        $api = $this->getApiService();
        [, $defaultNamespace] = $this->resolveNamespacesMeta($api);

        $extra = [
            'rateSearchQuery' => trim($validated['query']),
            'rateSearchNamespace' => trim((string) ($validated['namespace'] ?? '')) ?: $defaultNamespace,
            'rateSearchVideos' => [],
            'rateSearchId' => null,
            'rateSearchResponse' => null,
            'rateSearchError' => null,
        ];

        try {
            $payload = ['query' => $extra['rateSearchQuery']];
            if ($extra['rateSearchNamespace'] !== '') {
                $payload['namespace'] = $extra['rateSearchNamespace'];
            }
            $searchResponse = $api->semanticSearchVideos($payload);
            $extra['rateSearchResponse'] = $searchResponse;
            $extra['rateSearchVideos'] = is_array($searchResponse['videos'] ?? null)
                ? $searchResponse['videos']
                : [];
            $extra['rateSearchId'] = is_string($searchResponse['search_id'] ?? null)
                ? $searchResponse['search_id']
                : null;
        } catch (\Exception $e) {
            Log::warning('Analytics inline search failed', ['message' => $e->getMessage()]);
            $extra['rateSearchError'] = $e->getMessage();
        }

        if (isset($validated['feedback_days'])) {
            $request->merge(['feedback_days' => $validated['feedback_days']]);
        }
        if (isset($validated['feedback_tab'])) {
            $request->merge(['feedback_tab' => $validated['feedback_tab']]);
        }
        if (array_key_exists('feedback_namespace', $validated)) {
            $request->merge(['feedback_namespace' => $validated['feedback_namespace']]);
        }

        $request->merge(['tab' => 'feedback']);

        return $this->renderAnalytics($request, $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function renderAnalytics(Request $request, array $extra = []): View
    {
        $analyticsTab = $this->resolveAnalyticsTab($request);

        try {
            $data = $this->analyticsViewData($request);
            $data['analyticsTab'] = $analyticsTab;

            return view('analytics.index', array_merge($data, $this->rateSearchDefaults(), $extra));
        } catch (\Exception $e) {
            Log::error('Failed to load analytics', ['error' => $e->getMessage()]);

            return view('analytics.index', array_merge([
                'analyticsTab' => $analyticsTab,
                'tenantInfo' => null,
                'quota' => null,
                'analytics' => null,
                'stats' => null,
                'recentQueries' => [],
                'queriesByUser' => [],
                'queryUserFilters' => ['user_email' => null, 'user' => null],
                'searchFeedback' => [],
                'searchFeedbackError' => null,
                'feedbackDays' => 30,
                'feedbackSummary' => ['up' => 0, 'down' => 0, 'total' => 0],
                'namespaces' => ProductContext::filterNamespaces(self::FALLBACK_NAMESPACES, ProductContext::id()),
                'defaultNamespace' => ProductContext::defaultNamespace(),
                'namespaceLoadNote' => null,
                'productId' => ProductContext::id(),
                'product' => ProductContext::config(),
                'analyticsIndexRoute' => ProductContext::routeName('analytics'),
                'analyticsSearchRoute' => ProductContext::routeName('analytics.search'),
                'error' => 'Unable to load analytics data.',
            ], $this->rateSearchDefaults(), $extra));
        }
    }

    private function resolveAnalyticsTab(Request $request): string
    {
        $explicit = (string) $request->query('tab', $request->input('tab', ''));
        if (in_array($explicit, ['overview', 'searches', 'feedback'], true)) {
            return $explicit;
        }

        $email = trim((string) $request->query('user_email', $request->input('user_email', '')));
        $user = trim((string) $request->query('user', $request->input('user', '')));
        if ($email !== '' || $user !== '') {
            return 'searches';
        }

        return 'overview';
    }

    /**
     * @return array<string, mixed>
     */
    private function analyticsViewData(Request $request): array
    {
        $api = $this->getApiService();

        $tenantInfo = $api->getTenantInfo();
        $quota = $api->getTenantQuota();

        $analytics = null;
        try {
            $analytics = $api->getTenantAnalytics();
        } catch (\Exception $e) {
            Log::info('Analytics endpoint not available');
        }

        $stats = null;
        try {
            $stats = $api->getWordPressStats();
        } catch (\Exception $e) {
            Log::info('Stats endpoint not available');
        }

        $queryUserFilters = $this->resolveQueryUserFilters($request);
        $recentQueries = [];
        $queriesByUser = [];
        try {
            $queriesResponse = $api->getRecentQueries(50, 7, array_filter($queryUserFilters));
            $recentQueries = $queriesResponse['queries'] ?? [];
        } catch (\Exception $e) {
            Log::warning('Queries endpoint failed', ['error' => $e->getMessage()]);
        }
        try {
            $byUserResponse = $api->getQueriesByUser(50, 7);
            $queriesByUser = $byUserResponse['users'] ?? [];
        } catch (\Exception $e) {
            Log::warning('Queries by user endpoint failed', ['error' => $e->getMessage()]);
        }

        [$namespaces, $defaultNamespace, $namespaceLoadNote] = $this->resolveNamespacesMeta($api);

        $feedbackDays = $this->resolveFeedbackDays($request);
        $feedbackTab = $this->resolveFeedbackTab($request);
        $feedbackNamespace = $this->resolveFeedbackNamespace($request);
        $searchFeedback = [];
        $searchFeedbackError = null;
        $feedbackAnalytics = null;
        $feedbackAnalyticsError = null;
        try {
            $feedbackAnalytics = $api->getSearchFeedbackAnalytics($feedbackDays, $feedbackNamespace);
            $searchFeedback = is_array($feedbackAnalytics['detail'] ?? null)
                ? $feedbackAnalytics['detail']
                : [];
        } catch (\Exception $e) {
            Log::warning('Search feedback analytics failed', ['error' => $e->getMessage()]);
            $feedbackAnalyticsError = $e->getMessage();
            try {
                $feedbackResponse = $api->getSearchFeedback(200, $feedbackDays);
                $searchFeedback = is_array($feedbackResponse['feedback'] ?? null)
                    ? $feedbackResponse['feedback']
                    : [];
            } catch (\Exception $e2) {
                $searchFeedbackError = $e2->getMessage();
            }
        }

        $summary = is_array($feedbackAnalytics['summary'] ?? null)
            ? $feedbackAnalytics['summary']
            : $this->summarizeFeedback($searchFeedback);
        if (! isset($summary['video_ratings'])) {
            $summary = array_merge(
                ['video_ratings' => 0, 'query_level_ratings' => 0, 'distinct_queries' => 0, 'distinct_videos' => 0],
                $summary
            );
        }

        return [
            'tenantInfo' => $tenantInfo,
            'quota' => $quota,
            'analytics' => $analytics,
            'stats' => $stats,
            'recentQueries' => is_array($recentQueries) ? $recentQueries : [],
            'queriesByUser' => is_array($queriesByUser) ? $queriesByUser : [],
            'queryUserFilters' => $queryUserFilters,
            'searchFeedback' => $searchFeedback,
            'searchFeedbackError' => $searchFeedbackError,
            'feedbackAnalytics' => $feedbackAnalytics,
            'feedbackAnalyticsError' => $feedbackAnalyticsError,
            'feedbackTab' => $feedbackTab,
            'feedbackDays' => $feedbackDays,
            'feedbackNamespace' => $feedbackNamespace,
            'feedbackSummary' => $summary,
            'namespaces' => $namespaces,
            'defaultNamespace' => $defaultNamespace,
            'namespaceLoadNote' => $namespaceLoadNote,
            'productId' => ProductContext::id(),
            'product' => ProductContext::config(),
            'analyticsIndexRoute' => ProductContext::routeName('analytics'),
            'analyticsSearchRoute' => ProductContext::routeName('analytics.search'),
            'feedbackUrl' => route(ProductContext::id() === ProductContext::MOW_ROW ? 'mow-row.search.feedback' : 'ai-search.playground.feedback'),
            'error' => null,
        ];
    }

    /**
     * @return array{user_email: ?string, user: ?string}
     */
    private function resolveQueryUserFilters(Request $request): array
    {
        $email = trim((string) $request->query('user_email', $request->input('user_email', '')));
        $user = trim((string) $request->query('user', $request->input('user', '')));

        return [
            'user_email' => $email !== '' ? $email : null,
            'user' => $user !== '' ? $user : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function rateSearchDefaults(): array
    {
        $defaultNs = ProductContext::defaultNamespace();

        return [
            'rateSearchQuery' => '',
            'rateSearchNamespace' => $defaultNs,
            'rateSearchVideos' => [],
            'rateSearchId' => null,
            'rateSearchResponse' => null,
            'rateSearchError' => null,
        ];
    }

    private function resolveFeedbackDays(Request $request): int
    {
        $days = (int) $request->query('feedback_days', $request->input('feedback_days', 30));

        return in_array($days, [7, 30, 90], true) ? $days : 30;
    }

    private function resolveFeedbackTab(Request $request): string
    {
        $tab = (string) $request->query('feedback_tab', $request->input('feedback_tab', 'overview'));

        return in_array($tab, ['overview', 'by_query', 'by_video', 'detail'], true) ? $tab : 'overview';
    }

    private function resolveFeedbackNamespace(Request $request): ?string
    {
        $raw = $request->query('feedback_namespace', $request->input('feedback_namespace'));
        if ($raw === null) {
            return null;
        }
        $ns = trim((string) $raw);

        return $ns !== '' ? $ns : null;
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

    /**
     * @return array{0: list<string>, 1: string, 2: ?string}
     */
    private function resolveNamespacesMeta(BackendApiService $api): array
    {
        $productId = ProductContext::id();
        $productDefault = ProductContext::defaultNamespace($productId);

        try {
            $meta = $api->getSearchNamespaces();
            $list = $meta['namespaces'] ?? [];
            if (! is_array($list)) {
                $list = [];
            }
            $list = array_values(array_unique(array_filter($list, fn ($n) => is_string($n) && $n !== '')));
            $list = ProductContext::filterNamespaces($list, $productId);

            $default = is_string($meta['default'] ?? null) ? (string) $meta['default'] : $productDefault;
            if (! in_array($default, $list, true)) {
                $default = $productDefault;
            }

            if ($list === []) {
                $fallback = ProductContext::filterNamespaces(self::FALLBACK_NAMESPACES, $productId);

                return [$fallback !== [] ? $fallback : [$productDefault], $productDefault, 'Namespace list empty from API — using fallback.'];
            }

            $note = null;
            $source = is_string($meta['namespace_source'] ?? null) ? (string) $meta['namespace_source'] : '';
            if ($source === 'pinecone') {
                $note = 'Namespace list synced from Pinecone index.';
            } elseif ($source === 'embedding_schemes') {
                $note = 'Pinecone namespace list unavailable — showing configured embedding schemes only.';
            }

            return [$list, $default, $note];
        } catch (\Exception $e) {
            $fallback = ProductContext::filterNamespaces(self::FALLBACK_NAMESPACES, $productId);

            return [$fallback !== [] ? $fallback : [$productDefault], $productDefault, 'Could not load namespaces from API ('.$e->getMessage().') — using fallback.'];
        }
    }
}
