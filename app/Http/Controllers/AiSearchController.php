<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use App\Support\ProductContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiSearchController extends Controller
{
    /** Mirrors AI Pipeline search.EMBEDDING_SCHEMES when GET /namespaces is unavailable */
    private const FALLBACK_NAMESPACES = [
        'v6_title_only',
        'v6_title_tags',
        'v6_title_tags_short',
        'v6_title_tags_long',
        'v6_title_tags_short_long',
        'v6_title_tags_catalog',
        'mow_row_v6_title_tags',
    ];

    public function index()
    {
        $api = $this->getApiService();
        [$namespaces, $defaultNamespace, $namespaceLoadNote] = $this->resolveNamespacesMeta($api);
        $productId = ProductContext::id();

        return view('ai-search.index', $this->searchViewData(
            namespaces: $namespaces,
            defaultNamespace: $defaultNamespace,
            namespaceLoadNote: $namespaceLoadNote,
            selectedNamespace: old('namespace', $defaultNamespace),
            prefillQuery: old('query', ''),
            productId: $productId,
        ));
    }

    public function search(Request $request)
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
        ]);

        $api = $this->getApiService();
        [$namespaces, $defaultNamespace, $namespaceLoadNote] = $this->resolveNamespacesMeta($api);
        $productId = ProductContext::id();

        $videos = [];
        $searchResponse = null;
        $searchError = null;
        $searchId = null;
        $selectedNamespace = trim((string) ($validated['namespace'] ?? '')) ?: $defaultNamespace;

        try {
            $payload = [
                'query' => trim($validated['query']),
            ];
            if ($selectedNamespace !== '') {
                $payload['namespace'] = $selectedNamespace;
            }
            $postType = ProductContext::searchPostType($productId);
            if ($postType !== null) {
                $payload['post_type'] = $postType;
            }

            $searchResponse = $api->semanticSearchVideos($payload);
            $videos = $searchResponse['videos'] ?? [];
            $searchId = is_string($searchResponse['search_id'] ?? null)
                ? $searchResponse['search_id']
                : null;
        } catch (\Exception $e) {
            Log::warning('AI semantic search from dashboard failed', [
                'message' => $e->getMessage(),
                'product' => $productId,
            ]);
            $searchError = $e->getMessage();
        }

        return view('ai-search.index', $this->searchViewData(
            namespaces: $namespaces,
            defaultNamespace: $defaultNamespace,
            namespaceLoadNote: $namespaceLoadNote,
            selectedNamespace: $selectedNamespace,
            prefillQuery: trim($validated['query']),
            productId: $productId,
            videos: is_array($videos) ? $videos : [],
            searchResponse: $searchResponse,
            searchError: $searchError,
            searchId: $searchId,
        ));
    }

    public function feedback(Request $request)
    {
        $validated = $request->validate([
            'search_id' => 'required|string|max:64',
            'vote' => 'required|integer|in:1,-1',
            'wp_post_id' => 'nullable|integer|min:1',
            'rank' => 'nullable|integer|min:1|max:100',
            'pinecone_score' => 'nullable|numeric',
            'source' => 'nullable|string|in:dashboard,analytics,api,wordpress',
        ]);

        try {
            $api = $this->getApiService();
            $payload = [
                'search_id' => $validated['search_id'],
                'vote' => (int) $validated['vote'],
                'source' => $validated['source'] ?? 'dashboard',
            ];
            if (isset($validated['wp_post_id'])) {
                $payload['wp_post_id'] = (int) $validated['wp_post_id'];
            }
            if (isset($validated['rank'])) {
                $payload['rank'] = (int) $validated['rank'];
            }
            if (isset($validated['pinecone_score'])) {
                $payload['pinecone_score'] = (float) $validated['pinecone_score'];
            }

            $result = $api->submitSearchFeedback($payload);

            return response()->json([
                'ok' => true,
                'feedback_id' => $result['feedback_id'] ?? null,
                'vote' => $result['vote'] ?? (int) $validated['vote'],
            ]);
        } catch (\Exception $e) {
            Log::warning('Search feedback submit failed', ['message' => $e->getMessage()]);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function searchViewData(
        array $namespaces,
        string $defaultNamespace,
        ?string $namespaceLoadNote,
        string $selectedNamespace,
        string $prefillQuery,
        string $productId,
        array $videos = [],
        ?array $searchResponse = null,
        ?string $searchError = null,
        ?string $searchId = null,
    ): array {
        $searchRoute = $productId === ProductContext::MOW_ROW
            ? 'mow-row.search.search'
            : 'ai-search.playground.search';
        $feedbackRoute = $productId === ProductContext::MOW_ROW
            ? 'mow-row.search.feedback'
            : 'ai-search.playground.feedback';

        return [
            'namespaces' => $namespaces,
            'defaultNamespace' => $defaultNamespace,
            'selectedNamespace' => $selectedNamespace,
            'prefill' => ['query' => $prefillQuery],
            'namespaceLoadNote' => $namespaceLoadNote,
            'videos' => $videos,
            'searchResponse' => $searchResponse,
            'searchError' => $searchError,
            'searchId' => $searchId,
            'productId' => $productId,
            'product' => ProductContext::config($productId),
            'searchFormAction' => route($searchRoute),
            'feedbackUrl' => route($feedbackRoute),
        ];
    }

    protected function getApiService(): BackendApiService
    {
        $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');

        return new BackendApiService($apiKey);
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
