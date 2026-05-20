<?php

namespace App\Http\Controllers;

use App\Models\EmbeddingReconcileSnapshot;
use App\Services\BackendApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Namespace-centric video catalog overview + table (embedding scheme namespaces).
 */
class NamespaceStudioController extends Controller
{
    /** @var array<string, array{label: string, keys: string[]}> */
    private const NAMESPACE_META = [
        'v6_title_only' => ['label' => 'Title only', 'keys' => ['title']],
        'v6_title_tags' => ['label' => 'Title + post tags', 'keys' => ['title', 'post_tags']],
        'v6_title_tags_short' => ['label' => 'Title + tags + short description', 'keys' => ['title', 'post_tags', 'details_short_description']],
        'v6_title_tags_long' => ['label' => 'Title + tags + long description', 'keys' => ['title', 'post_tags', 'details_long_description']],
        'v6_title_tags_short_long' => ['label' => 'Title + tags + short + long description', 'keys' => ['title', 'post_tags', 'details_short_description', 'details_long_description']],
        'v6_title_tags_catalog' => ['label' => 'Catalog (title, tags, helps_with, short)', 'keys' => ['title', 'post_tags', 'details_helps_with', 'details_short_description']],
        'v7' => ['label' => 'v7 (title, tags, post content)', 'keys' => ['title', 'post_tags', 'post_content']],
    ];

    private const PAGE_SIZE = 100;

    protected function getApiService(): BackendApiService
    {
        $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');

        return new BackendApiService($apiKey);
    }

    /**
     * @return array{0: list<string>, 1: string, 2: ?string}
     */
    private function resolveNamespaces(BackendApiService $api): array
    {
        $fallback = array_keys(self::NAMESPACE_META);
        try {
            $meta = $api->getSearchNamespaces();
            $list = $meta['namespaces'] ?? [];
            if (! is_array($list)) {
                $list = [];
            }
            $list = array_values(array_unique(array_filter($list, fn ($n) => is_string($n) && $n !== '')));
            $default = is_string($meta['default'] ?? null) ? (string) $meta['default'] : 'v6_title_tags';

            if ($list === []) {
                return [$fallback, $default, 'Namespace list empty from API — using fallback list.'];
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
            return [$fallback, 'v6_title_tags', 'Could not load namespaces: '.$e->getMessage()];
        }
    }

    public function index(Request $request): View
    {
        $api = $this->getApiService();
        [$namespaces, $defaultNs, $namespaceNote] = $this->resolveNamespaces($api);

        $namespace = trim((string) $request->input('namespace', ''));
        if ($namespace !== '' && ! in_array($namespace, $namespaces, true)) {
            $namespace = '';
        }
        $hasNamespace = $namespace !== '';

        $viewMode = $request->input('view', 'all') === 'issues' ? 'issues' : 'all';
        $search = trim((string) $request->input('search', ''));
        $page = max(1, (int) $request->input('page', 1));
        $offset = ($page - 1) * self::PAGE_SIZE;

        $fields = implode(',', [
            'id', 'wp_post_id', 'jwp_id', 'title', 'thumbnail_url',
            'audio_preview_url', 'embedding_namespaces',
        ]);

        $videos = [];
        $totalRows = 0;
        $listError = null;
        $namespaceCountError = null;
        $namespaceCatalogCount = null;
        $totalPages = 1;

        if ($hasNamespace) {
            try {
                $filters = [
                    'limit' => $viewMode === 'all' ? self::PAGE_SIZE : 1,
                    'offset' => $viewMode === 'all' ? $offset : 0,
                    'fields' => $viewMode === 'all' ? $fields : 'id',
                    'sort_by' => 'title',
                    'sort_order' => 'asc',
                    'embedding_namespace' => $namespace,
                ];
                if ($viewMode === 'all' && $search !== '') {
                    $filters['search'] = $search;
                }
                $response = $api->getVideos($filters);
                $namespaceCatalogCount = (int) ($response['total'] ?? 0);

                if ($viewMode === 'all') {
                    $videos = $response['videos'] ?? $response;
                    if (! is_array($videos)) {
                        $videos = [];
                    }
                    $totalRows = $namespaceCatalogCount;
                    $totalPages = $totalRows > 0 ? (int) max(1, ceil($totalRows / self::PAGE_SIZE)) : 1;
                }
            } catch (\Exception $e) {
                Log::error('Namespace studio: namespace video query failed', [
                    'error' => $e->getMessage(),
                    'namespace' => $namespace,
                ]);
                $namespaceCountError = $e->getMessage();
                if ($viewMode === 'all') {
                    $listError = $e->getMessage();
                }
            }
        }

        $rows = [];
        foreach ($videos as $v) {
            if (! is_array($v)) {
                continue;
            }
            $rows[] = $this->normalizeCatalogRow($v, $namespace);
        }

        $nsMeta = $hasNamespace
            ? (self::NAMESPACE_META[$namespace] ?? ['label' => $namespace, 'keys' => []])
            : ['label' => '', 'keys' => []];

        $snapshotForJs = null;
        $reconcileSnapshotDisplay = null;
        $reconcileSummary = null;
        $tenantId = Auth::user()?->tenant_id;
        if ($tenantId && $hasNamespace) {
            $row = EmbeddingReconcileSnapshot::query()
                ->where('tenant_id', $tenantId)
                ->where('namespace', $namespace)
                ->first();
            if ($row && is_array($row->payload)) {
                $reconcileSnapshotDisplay = $row->reconciled_at
                    ? $row->reconciled_at->clone()->timezone(config('app.timezone'))->format('M j, Y g:i A T')
                    : null;
                $snapshotForJs = [
                    'reconciled_at' => $row->reconciled_at?->toIso8601String(),
                    'reconciled_at_display' => $reconcileSnapshotDisplay,
                    'payload' => $row->payload,
                ];
                $summary = $row->payload['summary'] ?? null;
                $reconcileSummary = is_array($summary) ? $summary : null;
            }
        }

        return view('videos.namespace-studio', [
            'namespaces' => $namespaces,
            'selectedNamespace' => $namespace,
            'hasNamespace' => $hasNamespace,
            'defaultNamespace' => $defaultNs,
            'namespaceNote' => $namespaceNote,
            'namespaceDefinition' => $hasNamespace
                ? $this->formatNamespaceDefinition($namespace, $nsMeta)
                : 'Select a namespace above to see its definition, reconcile snapshot, and catalog rows for that embedding scheme.',
            'namespaceKeys' => $nsMeta['keys'],
            'viewMode' => $viewMode,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRows' => $totalRows,
            'pageSize' => self::PAGE_SIZE,
            'rows' => $rows,
            'namespaceCatalogCount' => $namespaceCatalogCount,
            'namespaceCountError' => $namespaceCountError,
            'reconcileSummary' => $reconcileSummary,
            'listError' => $listError,
            'reconcileSnapshotDisplay' => $reconcileSnapshotDisplay,
            'reconcileSnapshotForJs' => $snapshotForJs,
        ]);
    }

    /**
     * Run Pinecone / DB reconcile (slow); returns JSON for Alpine store.
     */
    public function reconcile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'namespace' => 'required|string|max:128',
            'list_limit' => 'nullable|integer|min:1|max:100',
            'max_missing' => 'nullable|integer|min:1|max:50000',
            'max_orphans' => 'nullable|integer|min:1|max:50000',
            'max_unexpected' => 'nullable|integer|min:1|max:50000',
        ]);

        $api = $this->getApiService();
        $query = [
            'namespace' => $validated['namespace'],
            'list_limit' => $validated['list_limit'] ?? 100,
            'max_missing' => $validated['max_missing'] ?? 5000,
            'max_orphans' => $validated['max_orphans'] ?? 5000,
            'max_unexpected' => $validated['max_unexpected'] ?? 5000,
        ];

        try {
            $payload = $api->getEmbeddingReconcile($query);

            $reconciledAt = now();
            $tenantId = Auth::user()?->tenant_id;
            if ($tenantId) {
                try {
                    EmbeddingReconcileSnapshot::query()->updateOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'namespace' => $validated['namespace'],
                        ],
                        [
                            'payload' => $payload,
                            'reconciled_at' => $reconciledAt,
                        ]
                    );
                } catch (\Exception $e) {
                    Log::warning('Namespace studio: failed to persist reconcile snapshot', [
                        'error' => $e->getMessage(),
                        'tenant_id' => $tenantId,
                    ]);
                }
            }

            $reconciledDisplay = $reconciledAt->clone()->timezone(config('app.timezone'))->format('M j, Y g:i A T');

            return response()->json([
                'ok' => true,
                'payload' => $payload,
                'reconciled_at' => $reconciledAt->toIso8601String(),
                'reconciled_at_display' => $reconciledDisplay,
            ]);
        } catch (\Exception $e) {
            Log::error('Namespace studio reconcile failed', [
                'error' => $e->getMessage(),
                'namespace' => $validated['namespace'],
            ]);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    public function fixUpsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'namespace' => 'required|string|max:128',
            'jwp_ids' => 'nullable|array|max:50',
            'jwp_ids.*' => 'string|max:64',
            'video_ids' => 'nullable|array|max:50',
            'video_ids.*' => 'integer|min:1',
        ]);

        if (empty($validated['jwp_ids']) && empty($validated['video_ids'])) {
            return response()->json(['ok' => false, 'message' => 'jwp_ids or video_ids required'], 422);
        }

        try {
            $api = $this->getApiService();
            $payload = ['namespace' => $validated['namespace']];
            if (! empty($validated['jwp_ids'])) {
                $payload['jwp_ids'] = array_values($validated['jwp_ids']);
            }
            if (! empty($validated['video_ids'])) {
                $payload['video_ids'] = array_values($validated['video_ids']);
            }
            $result = $api->upsertPineconeVectors($payload);

            return response()->json(['ok' => true, 'result' => $result]);
        } catch (\Exception $e) {
            Log::warning('Namespace studio fix upsert failed', ['message' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 502);
        }
    }

    public function fixDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'namespace' => 'required|string|max:128',
            'jwp_ids' => 'required|array|min:1|max:50',
            'jwp_ids.*' => 'string|max:64',
        ]);

        try {
            $api = $this->getApiService();
            $result = $api->deletePineconeVectors([
                'namespace' => $validated['namespace'],
                'jwp_ids' => array_values($validated['jwp_ids']),
            ]);

            return response()->json(['ok' => true, 'result' => $result]);
        } catch (\Exception $e) {
            Log::warning('Namespace studio fix delete failed', ['message' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 502);
        }
    }

    /**
     * Lazy-load embedding input text for modal (per video + namespace).
     */
    public function embeddingText(Request $request, int $id): JsonResponse
    {
        $namespace = trim((string) $request->query('namespace', ''));
        if ($namespace === '') {
            return response()->json(['ok' => false, 'message' => 'namespace required'], 400);
        }

        try {
            $api = $this->getApiService();
            $detail = $api->getVideoById($id);
            $text = $this->extractEmbeddingText($detail, $namespace);

            return response()->json([
                'ok' => true,
                'text' => $text,
                'title' => data_get($detail, 'video.title'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 502);
        }
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    private function extractEmbeddingText(array $detail, string $namespace): ?string
    {
        $embeddings = $detail['embeddings'] ?? [];
        if (is_array($embeddings)) {
            foreach ($embeddings as $e) {
                if (! is_array($e)) {
                    continue;
                }
                if (($e['namespace'] ?? '') === $namespace) {
                    $t = $e['embedding_text'] ?? null;

                    return is_string($t) && $t !== '' ? $t : null;
                }
            }
        }

        $map = [
            'v6_title_tags' => $detail['computed_v6_embedding_text'] ?? null,
            'v6_title_tags_catalog' => $detail['computed_v6_catalog_embedding_text'] ?? null,
            'v7' => $detail['computed_v7_embedding_text'] ?? null,
        ];

        if (isset($map[$namespace])) {
            $t = $map[$namespace];

            return is_string($t) && $t !== '' ? $t : null;
        }

        return null;
    }

    /**
     * @param  array{id?:mixed, wp_post_id?:mixed, jwp_id?:mixed, title?:mixed, thumbnail_url?:mixed, audio_preview_url?:mixed, embedding_namespaces?:mixed}  $v
     * @return array<string, mixed>
     */
    private function normalizeCatalogRow(array $v, string $namespace): array
    {
        $id = (int) ($v['id'] ?? 0);
        $nsList = $this->parseEmbeddingNamespaces($v['embedding_namespaces'] ?? null);

        return [
            'id' => $id,
            'wp_post_id' => $v['wp_post_id'] ?? null,
            'jwp_id' => isset($v['jwp_id']) ? (string) $v['jwp_id'] : '',
            'title' => (string) ($v['title'] ?? ''),
            'thumbnail_url' => isset($v['thumbnail_url']) ? (string) $v['thumbnail_url'] : '',
            'audio_preview_url' => isset($v['audio_preview_url']) ? (string) $v['audio_preview_url'] : '',
            'badge_wp' => ! empty($v['wp_post_id']),
            'badge_db' => $id > 0,
            'badge_index' => in_array($namespace, $nsList, true),
            'embedding_teaser' => '', // filled client-side via modal fetch; optional one-line from names only
        ];
    }

    /**
     * @return list<string>
     */
    private function parseEmbeddingNamespaces(mixed $raw): array
    {
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $parts = array_map('trim', explode(';', $raw));

        return array_values(array_filter($parts, fn ($p) => $p !== ''));
    }

    /**
     * @param  array{label: string, keys: string[]}  $nsMeta
     */
    private function formatNamespaceDefinition(string $namespace, array $nsMeta): string
    {
        $keys = $nsMeta['keys'] ?? [];
        if ($keys === []) {
            return "Embedding scheme «{$namespace}». Fields are defined in the AI Pipeline search configuration.";
        }

        return $nsMeta['label'].'. Concatenated embedding fields: '.implode(', ', $keys).'.';
    }
}
