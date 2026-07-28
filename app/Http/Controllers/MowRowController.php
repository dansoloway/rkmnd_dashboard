<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use App\Support\ProductContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class MowRowController extends Controller
{
    private const CATALOG_FIELDS = [
        'id',
        'wp_post_id',
        'jwp_id',
        'title',
        'post_type',
        'post_status',
        'scheduled_content_type',
        'scheduled_acf',
        'mbr_pwa',
        'mbr_related_products',
        'mbr_props',
        'video_category',
        'mow_row_content_pillar',
        'content_label',
        'content_pillar',
        'instructor',
        'body_area',
        'helps_with',
        'props',
        'content_tags',
        'video_topic',
        'short_description',
        'long_description',
        'run_time',
        'video_time',
        'embedding_namespaces',
    ];

    protected function getApiService(): BackendApiService
    {
        $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');

        return new BackendApiService($apiKey);
    }

    public function catalog(Request $request): View
    {
        try {
            $api = $this->getApiService();

            $filters = array_merge(ProductContext::catalogFilters(ProductContext::MOW_ROW), [
                'limit' => (int) $request->input('limit', 50),
                'offset' => (int) $request->input('offset', 0),
                'sort_by' => $request->input('sort_by', 'wp_created'),
                'sort_order' => $request->input('sort_order', 'desc'),
                'fields' => implode(',', self::CATALOG_FIELDS),
            ]);

            if ($request->filled('search')) {
                $filters['search'] = $request->input('search');
            }

            $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');

            $response = $api->getVideos($filters);
            $videos = $response['videos'] ?? (is_array($response) && ! isset($response['total']) ? $response : []);
            $total = (int) ($response['total'] ?? count($videos));

            $contentType = trim((string) $request->input('content_type', ''));
            if ($contentType !== '' && in_array($contentType, ['move', 'roll', 'breathe', 'weekly'], true)) {
                $filterPillar = $contentType === 'weekly' ? 'roll' : $contentType;
                $videos = array_values(array_filter($videos, function (array $v) use ($filterPillar) {
                    $pillar = strtolower(trim((string) ($v['content_pillar'] ?? '')));
                    if ($pillar !== '') {
                        return $pillar === $filterPillar;
                    }
                    $sct = strtolower(trim((string) ($v['scheduled_content_type'] ?? '')));
                    if ($filterPillar === 'move') {
                        return $sct === 'move';
                    }
                    if ($filterPillar === 'roll') {
                        return $sct === 'weekly';
                    }
                    if ($filterPillar === 'breathe') {
                        return ($v['post_type'] ?? '') === 'video';
                    }

                    return false;
                }));
            }

            $perPage = (int) ($filters['limit'] ?? 50);
            $offset = (int) ($filters['offset'] ?? 0);
            $currentPage = $perPage > 0 ? (int) floor($offset / $perPage) + 1 : 1;
            $totalPages = $perPage > 0 ? (int) max(1, ceil($total / $perPage)) : 1;

            return view('mow-row.catalog', [
                'videos' => $videos,
                'total' => $total,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'filters' => [
                    'search' => $request->input('search', ''),
                    'content_type' => $contentType,
                    'sort_by' => $filters['sort_by'] ?? 'wp_created',
                    'sort_order' => $filters['sort_order'] ?? 'desc',
                    'limit' => $perPage,
                    'offset' => $offset,
                ],
                'product' => ProductContext::config(ProductContext::MOW_ROW),
            ]);
        } catch (\Exception $e) {
            Log::error('MOW/ROW catalog load failed', ['error' => $e->getMessage()]);

            return view('mow-row.catalog', [
                'videos' => [],
                'total' => 0,
                'currentPage' => 1,
                'totalPages' => 1,
                'filters' => [],
                'product' => ProductContext::config(ProductContext::MOW_ROW),
                'error' => 'Unable to load MOW/ROW catalog. Please try again later.',
            ]);
        }
    }

    public function updateContentPillar(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'mow_row_content_pillar' => 'required|in:move,roll,breathe',
        ]);

        try {
            $api = $this->getApiService();
            $result = $api->updateMowRowContentPillar($id, $validated['mow_row_content_pillar']);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('MOW/ROW content pillar update failed', [
                'video_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function featured(): View
    {
        $featured = ['move' => null, 'weekly' => null, 'as_of' => null];
        $error = null;

        try {
            $api = $this->getApiService();
            $featured = $api->getMowRowFeaturedWeekly();
        } catch (\Exception $e) {
            Log::warning('MOW/ROW featured weekly load failed', ['error' => $e->getMessage()]);
            $error = $e->getMessage();
        }

        return view('mow-row.featured', [
            'featured' => $featured,
            'product' => ProductContext::config(ProductContext::MOW_ROW),
            'error' => $error,
        ]);
    }
}
