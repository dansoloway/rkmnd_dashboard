<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncLogController extends Controller
{
    /**
     * Display sync logs
     */
    public function index(Request $request)
    {
        try {
            // Get API service with tenant's API key
            $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');
            $api = new BackendApiService($apiKey);

            // Get sync logs from backend
            $limit = $request->get('limit', 50);
            $syncLogs = $api->getSyncLogs($limit);

            return view('sync-logs', [
                'logs' => $syncLogs['logs'] ?? [],
                'limit' => $limit
            ]);

        } catch (\Exception $e) {
            Log::error('Sync logs load failed', [
                'error' => $e->getMessage()
            ]);

            return view('sync-logs', [
                'logs' => [],
                'limit' => $request->get('limit', 50),
                'error' => 'Unable to load sync logs. Please try again later.'
            ]);
        }
    }
}

