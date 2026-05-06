<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
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
    ];

    public function index()
    {
        $api = $this->getApiService();
        [$namespaces, $defaultNamespace, $namespaceLoadNote] = $this->resolveNamespacesMeta($api);

        return view('ai-search.index', [
            'namespaces' => $namespaces,
            'defaultNamespace' => $defaultNamespace,
            'selectedNamespace' => old('namespace', $defaultNamespace),
            'prefill' => [
                'query' => old('query', ''),
                'video_length' => old('video_length', ''),
                'post_type' => old('post_type', ''),
            ],
            'namespaceLoadNote' => $namespaceLoadNote,
            'videos' => [],
            'searchResponse' => null,
            'searchError' => null,
        ]);
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
            'video_length' => 'nullable|in:short,medium,long',
            'post_type' => 'nullable|string|max:64',
        ]);

        $api = $this->getApiService();
        [$namespaces, $defaultNamespace, $namespaceLoadNote] = $this->resolveNamespacesMeta($api);

        $videos = [];
        $searchResponse = null;
        $searchError = null;

        try {
            $payload = [
                'query' => trim($validated['query']),
            ];
            $ns = isset($validated['namespace']) ? trim((string) $validated['namespace']) : '';
            $selectedNamespace = ($ns !== '' ? $ns : $defaultNamespace);
            if ($ns !== '') {
                $payload['namespace'] = $ns;
            }
            if (! empty($validated['video_length'])) {
                $payload['video_length'] = $validated['video_length'];
            }
            if (! empty($validated['post_type'])) {
                $payload['post_type'] = $validated['post_type'];
            }

            $searchResponse = $api->semanticSearchVideos($payload);
            $videos = $searchResponse['videos'] ?? [];
        } catch (\Exception $e) {
            Log::warning('AI semantic search from dashboard failed', [
                'message' => $e->getMessage(),
            ]);
            $searchError = $e->getMessage();
            $selectedNamespace = trim((string) ($validated['namespace'] ?? '')) ?: $defaultNamespace;
        }

        return view('ai-search.index', [
            'namespaces' => $namespaces,
            'defaultNamespace' => $defaultNamespace,
            'selectedNamespace' => $selectedNamespace,
            'prefill' => [
                'query' => trim($validated['query']),
                'video_length' => $validated['video_length'] ?? '',
                'post_type' => $validated['post_type'] ?? '',
            ],
            'namespaceLoadNote' => $namespaceLoadNote,
            'videos' => is_array($videos) ? $videos : [],
            'searchResponse' => $searchResponse,
            'searchError' => $searchError,
        ]);
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
        try {
            $meta = $api->getSearchNamespaces();
            $list = $meta['namespaces'] ?? [];
            if (! is_array($list)) {
                $list = [];
            }
            $list = array_values(array_unique(array_filter($list, fn ($n) => is_string($n) && $n !== '')));

            $default = is_string($meta['default'] ?? null) ? (string) $meta['default'] : 'v6_title_tags';

            if ($list === []) {
                return [self::FALLBACK_NAMESPACES, $default, 'Namespace list empty from API — using fallback.'];
            }

            return [$list, $default, null];
        } catch (\Exception $e) {
            return [self::FALLBACK_NAMESPACES, 'v6_title_tags', 'Could not load namespaces from API ('.$e->getMessage().') — using fallback.'];
        }
    }
}
