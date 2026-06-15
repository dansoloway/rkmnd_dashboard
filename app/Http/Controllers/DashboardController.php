<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use App\Support\ProductContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');
            $api = new BackendApiService($apiKey);

            $syncLogs = [];
            try {
                $syncLogsResponse = $api->getSyncLogs(1);
                $syncLogs = $syncLogsResponse['logs'] ?? [];
            } catch (\Exception $e) {
                Log::warning('Failed to get sync logs for dashboard', [
                    'error' => $e->getMessage(),
                ]);
            }

            $products = [
                ProductContext::AI_SEARCH => $this->productHubCard($api, ProductContext::AI_SEARCH),
                ProductContext::MOW_ROW => $this->productHubCard($api, ProductContext::MOW_ROW),
            ];

            return view('dashboard', [
                'products' => $products,
                'latestSync' => ! empty($syncLogs) ? $syncLogs[0] : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard load failed', [
                'error' => $e->getMessage(),
            ]);

            return view('dashboard', [
                'products' => [],
                'latestSync' => null,
                'error' => 'Unable to load dashboard data. Please try again later.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function productHubCard(BackendApiService $api, string $productId): array
    {
        $cfg = ProductContext::config($productId);
        $card = [
            'id' => $productId,
            'label' => $cfg['label'] ?? $productId,
            'consumer' => $cfg['consumer'] ?? '',
            'catalogCount' => null,
            'searchPoolCount' => null,
            'featuredMove' => null,
            'featuredWeekly' => null,
            'error' => null,
            'routes' => [
                'library' => route(ProductContext::libraryRoute($productId)),
                'search' => route(ProductContext::searchRoute($productId)),
                'namespaceStudio' => route(ProductContext::namespaceStudioRoute($productId)),
                'analytics' => $productId === ProductContext::AI_SEARCH
                    ? route('ai-search.analytics')
                    : null,
                'featured' => $productId === ProductContext::MOW_ROW
                    ? route('mow-row.featured')
                    : null,
            ],
        ];

        try {
            $catalogResponse = $api->getVideos(array_merge(ProductContext::catalogFilters($productId), [
                'limit' => 1,
                'offset' => 0,
            ]));
            $card['catalogCount'] = (int) ($catalogResponse['total'] ?? 0);

            if ($productId === ProductContext::AI_SEARCH) {
                $poolResponse = $api->getVideos(array_merge(ProductContext::searchPoolFilters($productId), [
                    'limit' => 1,
                    'offset' => 0,
                ]));
                $card['searchPoolCount'] = (int) ($poolResponse['total'] ?? 0);
            }

            if ($productId === ProductContext::MOW_ROW) {
                $featured = $api->getMowRowFeaturedWeekly();
                $card['featuredMove'] = $featured['move'] ?? null;
                $card['featuredWeekly'] = $featured['weekly'] ?? null;
            }
        } catch (\Exception $e) {
            $card['error'] = $e->getMessage();
        }

        return $card;
    }

    public function clearCache(Request $request)
    {
        try {
            Cache::flush();

            $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');
            $api = new BackendApiService($apiKey);
            $api->clearCache();

            return redirect()->route('dashboard')->with('success', 'Statistics cache cleared successfully!');
        } catch (\Exception $e) {
            Log::error('Cache clear failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('dashboard')->with('error', 'Failed to clear cache.');
        }
    }
}
