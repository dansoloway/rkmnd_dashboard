@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900">
                    Sync Logs 🔄
                </h1>
                <p class="mt-2 text-gray-600">
                    Track video synchronization operations and their results
                </p>
            </div>
            <div class="flex gap-3">
                <form method="POST" action="{{ route('sync-logs.trigger') }}" class="inline" onsubmit="return confirm('This will start a new sync operation. Continue?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-700 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        🔄 Trigger Sync Now
                    </button>
                </form>
                @if(!empty($logs))
                    <form method="POST" action="{{ route('sync-logs.clear') }}" class="inline" onsubmit="return confirm('Are you sure you want to clear all sync logs? This will reset all statistics to zero.');">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-red-700 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            🗑️ Clear All Logs
                        </button>
                    </form>
                @endif
            </div>
        </div>
        
        @if(session('success'))
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        @endif
        
        @if(session('error'))
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
                <p class="text-sm text-red-800">{{ session('error') }}</p>
            </div>
        @endif
    </div>

    @if(isset($error))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-800">{{ $error }}</p>
        </div>
    @endif

    <!-- Sync Logs Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-heading font-semibold text-gray-900">
                Recent Sync Operations
            </h2>
        </div>

        @if(empty($logs))
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="mt-4 text-gray-500">No sync logs found yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Time
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Videos Found
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Added
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Updated
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Duration
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Errors
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Changes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($log['started_at'])->format('M d, Y') }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($log['started_at'])->format('H:i:s') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        {{ ucfirst($log['sync_type']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($log['status'] === 'completed')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                            ✅ Completed
                                        </span>
                                    @elseif($log['status'] === 'failed')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                            ❌ Failed
                                        </span>
                                    @elseif($log['status'] === 'processing')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                            ⏳ Processing
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                            {{ ucfirst($log['status']) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($log['total_videos_found'] ?? 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                        +{{ number_format($log['new_videos_added'] ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        ↻ {{ number_format($log['videos_updated'] ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if(isset($log['duration_seconds']))
                                        @php
                                            $minutes = floor($log['duration_seconds'] / 60);
                                            $seconds = $log['duration_seconds'] % 60;
                                        @endphp
                                        {{ $minutes }}m {{ $seconds }}s
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if(($log['errors_encountered'] ?? 0) > 0)
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                            {{ number_format($log['errors_encountered']) }}
                                        </span>
                                        @if(!empty($log['error_message']))
                                            <button 
                                                onclick="toggleError('error-{{ $log['id'] }}')" 
                                                class="ml-2 text-red-600 hover:text-red-800 text-xs underline"
                                                title="Click to view error details">
                                                View Details
                                            </button>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if(!empty($log['change_details']) && is_array($log['change_details']))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($log['change_details'] as $field => $count)
                                                <span class="px-2 py-1 text-xs font-medium rounded bg-purple-100 text-purple-800" title="{{ ucfirst(str_replace('_', ' ', $field)) }} changed in {{ $count }} video(s)">
                                                    {{ ucfirst(str_replace('_', ' ', $field)) }}: {{ $count }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @elseif(($log['videos_updated'] ?? 0) > 0)
                                        <span class="text-gray-400 text-xs">No field changes detected</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                            @if(!empty($log['error_message']))
                            <tr id="error-{{ $log['id'] }}" class="hidden">
                                <td colspan="9" class="px-6 py-4 bg-red-50 border-t border-red-200">
                                    <div class="text-sm">
                                        <p class="font-medium text-red-900 mb-2">Error Details:</p>
                                        <pre class="text-xs text-red-800 bg-white p-3 rounded border border-red-200 overflow-x-auto whitespace-pre-wrap">{{ $log['error_message'] }}</pre>
                                    </div>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Summary Stats -->
    @if(!empty($logs))
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            @php
                $totalSyncs = count($logs);
                $successfulSyncs = collect($logs)->where('status', 'completed')->count();
                $totalVideosFound = collect($logs)->sum('total_videos_found');
                $totalAdded = collect($logs)->sum('new_videos_added');
                $totalUpdated = collect($logs)->sum('videos_updated');
            @endphp
            
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Syncs</p>
                        <p class="mt-2 text-3xl font-heading font-bold text-gray-900">
                            {{ $totalSyncs }}
                        </p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Success Rate</p>
                        <p class="mt-2 text-3xl font-heading font-bold text-green-600">
                            {{ $totalSyncs > 0 ? round(($successfulSyncs / $totalSyncs) * 100) : 0 }}%
                        </p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Added</p>
                        <p class="mt-2 text-3xl font-heading font-bold text-gray-900">
                            {{ number_format($totalAdded) }}
                        </p>
                    </div>
                    <div class="bg-purple-100 rounded-full p-3">
                        <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Updated</p>
                        <p class="mt-2 text-3xl font-heading font-bold text-gray-900">
                            {{ number_format($totalUpdated) }}
                        </p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function toggleError(errorId) {
    const errorRow = document.getElementById(errorId);
    if (errorRow) {
        errorRow.classList.toggle('hidden');
    }
}
</script>
@endpush
@endsection

