@if($quota)
    @php
        $quotaStatus = $quota['quota_status'] ?? [];
        $limits = $quota['limits'] ?? [];
        $queriesRemaining = $quotaStatus['queries_remaining'] ?? 0;
        $queriesLimit = $limits['monthly_queries'] ?? 10000;
        $queriesUsed = $queriesLimit - $queriesRemaining;
        $queriesPercent = $queriesLimit > 0 ? ($queriesUsed / $queriesLimit) * 100 : 0;
        $embeddingsRemaining = $quotaStatus['embeddings_remaining'] ?? 0;
        $embeddingsLimit = $limits['monthly_embeddings'] ?? 100000;
        $embeddingsUsed = $embeddingsLimit - $embeddingsRemaining;
        $embeddingsPercent = $embeddingsLimit > 0 ? ($embeddingsUsed / $embeddingsLimit) * 100 : 0;
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="text-sm font-heading font-medium text-gray-900 mb-3">Search Queries</h3>
            <div class="flex justify-between items-center mb-2">
                <span class="text-2xl font-heading font-bold text-gray-900">{{ number_format($queriesRemaining) }}</span>
                <span class="text-xs text-gray-500">of {{ number_format($queriesLimit) }} remaining</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 mb-1">
                <div class="h-1.5 rounded-full {{ $queriesPercent > 80 ? 'bg-red-600' : ($queriesPercent > 50 ? 'bg-yellow-600' : 'bg-green-600') }}" style="width: {{ $queriesPercent }}%"></div>
            </div>
            <p class="text-xs text-gray-500">{{ number_format($queriesUsed) }} used ({{ number_format($queriesPercent, 1) }}%)</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="text-sm font-heading font-medium text-gray-900 mb-3">Embeddings</h3>
            <div class="flex justify-between items-center mb-2">
                <span class="text-2xl font-heading font-bold text-gray-900">{{ number_format($embeddingsRemaining) }}</span>
                <span class="text-xs text-gray-500">of {{ number_format($embeddingsLimit) }} remaining</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 mb-1">
                <div class="h-1.5 rounded-full {{ $embeddingsPercent > 80 ? 'bg-red-600' : ($embeddingsPercent > 50 ? 'bg-yellow-600' : 'bg-green-600') }}" style="width: {{ $embeddingsPercent }}%"></div>
            </div>
            <p class="text-xs text-gray-500">{{ number_format($embeddingsUsed) }} used ({{ number_format($embeddingsPercent, 1) }}%)</p>
        </div>
    </div>
@endif

@if(isset($quota['current_usage']))
    @php $usage = $quota['current_usage']; @endphp
    <div class="bg-white rounded-lg shadow-sm p-5">
        <h3 class="text-sm font-heading font-medium text-gray-900 mb-3">Current Usage Period</h3>
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <dt class="text-xs font-medium text-gray-500">Total Queries</dt>
                <dd class="mt-1 text-xl font-heading font-bold text-gray-900">{{ $usage['total_queries'] ?? 0 }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">Start Date</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ isset($usage['start_date']) ? date('M d, Y', strtotime($usage['start_date'])) : 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">End Date</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ isset($usage['end_date']) ? date('M d, Y', strtotime($usage['end_date'])) : 'N/A' }}</dd>
            </div>
        </dl>
    </div>
@endif

<div class="bg-white rounded-lg shadow-sm p-5">
    <h3 class="text-sm font-heading font-medium text-gray-900 mb-3">Account & Content</h3>
    <dl class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @if($tenantInfo)
            <div>
                <dt class="text-xs font-medium text-gray-500">Tenant</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ $tenantInfo['display_name'] ?? 'N/A' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">Plan</dt>
                <dd class="mt-1">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">{{ $tenantInfo['plan_type'] ?? 'N/A' }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">Status</dt>
                <dd class="mt-1">
                    @if($tenantInfo['is_active'] ?? false)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                    @endif
                </dd>
            </div>
        @endif
        @if($stats)
            <div>
                <dt class="text-xs font-medium text-gray-500">Total Videos</dt>
                <dd class="mt-1 text-xl font-heading font-bold text-gray-900">{{ $stats['total_videos'] ?? 0 }}</dd>
            </div>
            @if(!empty($stats['categories']))
                <div>
                    <dt class="text-xs font-medium text-gray-500">Categories</dt>
                    <dd class="mt-1 text-xl font-heading font-bold text-gray-900">{{ count($stats['categories']) }}</dd>
                </div>
            @endif
            @if(!empty($stats['instructors']))
                <div>
                    <dt class="text-xs font-medium text-gray-500">Instructors</dt>
                    <dd class="mt-1 text-xl font-heading font-bold text-gray-900">{{ count($stats['instructors']) }}</dd>
                </div>
            @endif
        @endif
    </dl>
</div>
