@extends('layouts.app')

@section('head')
<!-- Chart.js for analytics visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-heading font-bold text-gray-900">Analytics & Usage</h1>
        <p class="mt-2 text-gray-600">Monitor your usage and quota limits</p>
    </div>

    @if(isset($error))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-800">{{ $error }}</p>
        </div>
    @endif

    <!-- Quota Overview -->
    @if($quota)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Queries Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-heading font-medium text-gray-900 mb-4">Search Queries</h3>
                
                @php
                    $quotaStatus = $quota['quota_status'] ?? [];
                    $limits = $quota['limits'] ?? [];
                    $queriesRemaining = $quotaStatus['queries_remaining'] ?? 0;
                    $queriesLimit = $limits['monthly_queries'] ?? 10000;
                    $queriesUsed = $queriesLimit - $queriesRemaining;
                    $queriesPercent = $queriesLimit > 0 ? ($queriesUsed / $queriesLimit) * 100 : 0;
                @endphp

                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-3xl font-heading font-bold text-gray-900">{{ number_format($queriesRemaining) }}</span>
                        <span class="text-sm text-gray-500">of {{ number_format($queriesLimit) }} remaining</span>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div 
                            class="h-2 rounded-full {{ $queriesPercent > 80 ? 'bg-red-600' : ($queriesPercent > 50 ? 'bg-yellow-600' : 'bg-green-600') }}" 
                            style="width: {{ $queriesPercent }}%"
                        ></div>
                    </div>
                    
                    <p class="text-sm text-gray-600">
                        {{ number_format($queriesUsed) }} queries used ({{ number_format($queriesPercent, 1) }}%)
                    </p>
                </div>
            </div>

            <!-- Embeddings Card -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-heading font-medium text-gray-900 mb-4">Embeddings</h3>
                
                @php
                    $embeddingsRemaining = $quotaStatus['embeddings_remaining'] ?? 0;
                    $embeddingsLimit = $limits['monthly_embeddings'] ?? 100000;
                    $embeddingsUsed = $embeddingsLimit - $embeddingsRemaining;
                    $embeddingsPercent = $embeddingsLimit > 0 ? ($embeddingsUsed / $embeddingsLimit) * 100 : 0;
                @endphp

                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-3xl font-heading font-bold text-gray-900">{{ number_format($embeddingsRemaining) }}</span>
                        <span class="text-sm text-gray-500">of {{ number_format($embeddingsLimit) }} remaining</span>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div 
                            class="h-2 rounded-full {{ $embeddingsPercent > 80 ? 'bg-red-600' : ($embeddingsPercent > 50 ? 'bg-yellow-600' : 'bg-green-600') }}" 
                            style="width: {{ $embeddingsPercent }}%"
                        ></div>
                    </div>
                    
                    <p class="text-sm text-gray-600">
                        {{ number_format($embeddingsUsed) }} embeddings used ({{ number_format($embeddingsPercent, 1) }}%)
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Current Usage Period -->
    @if(isset($quota['current_usage']))
        @php
            $usage = $quota['current_usage'];
        @endphp
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-heading font-medium text-gray-900 mb-4">Current Usage Period</h3>
            <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Queries</dt>
                    <dd class="mt-1 text-2xl font-heading font-bold text-gray-900">{{ $usage['total_queries'] ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ isset($usage['start_date']) ? date('M d, Y', strtotime($usage['start_date'])) : 'N/A' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">End Date</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ isset($usage['end_date']) ? date('M d, Y', strtotime($usage['end_date'])) : 'N/A' }}
                    </dd>
                </div>
            </dl>
        </div>
    @endif

    <!-- Account Information -->
    @if($tenantInfo)
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-heading font-medium text-gray-900 mb-4">Account Information</h3>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Tenant Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $tenantInfo['display_name'] ?? 'N/A' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Plan Type</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 capitalize">
                            {{ $tenantInfo['plan_type'] ?? 'N/A' }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                        @if($tenantInfo['is_active'] ?? false)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Inactive
                            </span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    @endif

    <!-- WordPress Stats -->
    @if($stats)
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-heading font-medium text-gray-900 mb-4">Content Statistics</h3>
            <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Videos</dt>
                    <dd class="mt-1 text-2xl font-heading font-bold text-gray-900">{{ $stats['total_videos'] ?? 0 }}</dd>
                </div>
                @if(!empty($stats['categories']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Categories</dt>
                        <dd class="mt-1 text-2xl font-heading font-bold text-gray-900">{{ count($stats['categories']) }}</dd>
                    </div>
                @endif
                @if(!empty($stats['instructors']))
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Instructors</dt>
                        <dd class="mt-1 text-2xl font-heading font-bold text-gray-900">{{ count($stats['instructors']) }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif
</div>
@endsection


