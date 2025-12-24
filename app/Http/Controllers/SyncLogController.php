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

    /**
     * Clear all sync logs (reset statistics)
     */
    public function clear(Request $request)
    {
        try {
            // Get API service with tenant's API key
            $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');
            $api = new BackendApiService($apiKey);

            // Clear sync logs via backend API
            $result = $api->clearSyncLogs();
            
            // Clear the cache for sync logs endpoint to ensure fresh data
            $api->clearEndpointCache('/api/v1/wordpress/sync/logs', ['limit' => 50]);
            
            // Also clear all cache to be safe
            $api->clearCache();
            
            $message = $result['message'] ?? 'All sync logs cleared successfully!';
            return redirect()->route('sync-logs.index')->with('success', $message);
            
        } catch (\Exception $e) {
            Log::error('Clear sync logs failed', [
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('sync-logs.index')->with('error', 'Failed to clear sync logs: ' . $e->getMessage());
        }
    }

    /**
     * Trigger a new WordPress sync
     */
    public function trigger(Request $request)
    {
        Log::info('Sync trigger request received', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'unknown',
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String()
        ]);

        try {
            // Get API service with tenant's API key
            $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');
            
            Log::info('Preparing to trigger sync', [
                'has_api_key' => !empty($apiKey),
                'api_key_length' => strlen($apiKey ?? ''),
                'api_key_preview' => substr($apiKey ?? '', 0, 10) . '...',
                'backend_url' => config('backend.api_url'),
                'backend_timeout' => config('backend.timeout')
            ]);

            $api = new BackendApiService($apiKey);

            Log::info('Calling BackendApiService->triggerSync()');
            
            // Trigger sync via backend API
            $result = $api->triggerSync();
            
            Log::info('Sync trigger successful', [
                'result' => $result,
                'sync_log_id' => $result['sync_log_id'] ?? null,
                'status' => $result['status'] ?? null
            ]);
            
            $message = $result['message'] ?? 'Sync started successfully!';
            return redirect()->route('sync-logs.index')->with('success', $message);
            
        } catch (\Exception $e) {
            Log::error('Trigger sync failed - Controller level', [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'has_api_key' => !empty(session('tenant_api_key') ?? config('backend.default_api_key')),
                'backend_url' => config('backend.api_url')
            ]);
            
            $errorMessage = 'Failed to trigger sync: ' . $e->getMessage();
            return redirect()->route('sync-logs.index')->with('error', $errorMessage);
        }
    }
}

