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

    @include('analytics.partials.search-feedback-section')

    <!-- Searches by user -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-heading font-medium text-gray-900 mb-4">Searches by user</h3>
        <p class="text-sm text-gray-500 mb-4">WordPress users grouped by email (last 7 days). Anonymous searches appear when no user was logged in.</p>
        @if(!empty($queriesByUser))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Searches</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last search</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($queriesByUser as $userRow)
                            @php
                                $uEmail = trim((string) ($userRow['user_email'] ?? ''));
                                $uName = trim((string) ($userRow['user_name'] ?? ''));
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $uName !== '' ? e($uName) : '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $uEmail !== '' ? e($uEmail) : 'Anonymous' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ (int) ($userRow['search_count'] ?? 0) }}</td>
                                <td class="px-4 py-3 text-sm whitespace-nowrap">
                                    @include('partials.analytics-datetime', ['isoTimestamp' => $userRow['last_search_at'] ?? null])
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($uEmail !== '')
                                        <a href="{{ route('analytics.index', array_filter(['user_email' => $uEmail])) }}" class="text-blue-600 hover:text-blue-800">View searches</a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No user-attributed searches in the last 7 days.</p>
        @endif
    </div>

    <!-- Recent Search Queries -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-heading font-medium text-gray-900 mb-4">Recent Search Queries</h3>
        <p class="text-sm text-gray-500 mb-4">Production searches (last 7 days). Rate results when a session id is available (searches after May 2026 deploy).</p>
        <form method="get" action="{{ route('analytics.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label for="query-user-filter" class="block text-xs font-medium text-gray-500 mb-1">Filter by name or email</label>
                <input type="text" name="user" id="query-user-filter" value="{{ e($queryUserFilters['user'] ?? '') }}" placeholder="Name or email" class="rounded-md border-gray-300 shadow-sm text-sm">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-3 py-2 text-sm rounded-md bg-gray-800 text-white hover:bg-gray-700">Filter</button>
                @if(!empty($queryUserFilters['user']) || !empty($queryUserFilters['user_email']))
                    <a href="{{ route('analytics.index') }}" class="px-3 py-2 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Clear</a>
                @endif
            </div>
        </form>
        @if(!empty($recentQueries))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Query</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Results &amp; rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentQueries as $item)
                            @php
                                $rowSearchId = $item['search_id'] ?? null;
                                $top6 = !empty($item['results']) ? array_slice($item['results'], 0, 6) : [];
                            @endphp
                            <tr @if($rowSearchId) x-data="searchFeedbackPanel({
                                searchId: @js($rowSearchId),
                                feedbackUrl: @js(route('ai-search.feedback')),
                                csrf: @js(csrf_token()),
                                source: 'analytics',
                            })" @endif>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <span class="font-medium">{{ e($item['query'] ?? '-') }}</span>
                                    @if(!empty($item['namespace']))
                                        <span class="block text-xs text-gray-400 font-mono">{{ $item['namespace'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @php
                                        $rowUserName = trim((string) ($item['user_name'] ?? ''));
                                        $rowUserEmail = trim((string) ($item['user_email'] ?? ''));
                                    @endphp
                                    @if($rowUserName !== '' || $rowUserEmail !== '')
                                        @if($rowUserName !== '')
                                            <span class="block">{{ e($rowUserName) }}</span>
                                        @endif
                                        @if($rowUserEmail !== '')
                                            <span class="block text-xs text-gray-400">{{ e($rowUserEmail) }}</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm whitespace-nowrap">
                                    @include('partials.analytics-datetime', ['isoTimestamp' => $item['timestamp'] ?? null])
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $item['result_count'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @if($rowSearchId && count($top6) > 0)
                                        <ul class="space-y-2 max-w-lg">
                                            @foreach($top6 as $idx => $r)
                                                @php
                                                    $wpId = isset($r['wp_post_id']) && is_numeric($r['wp_post_id']) ? (int) $r['wp_post_id'] : null;
                                                    $rVideoId = isset($r['video_id']) && is_numeric($r['video_id']) ? (int) $r['video_id'] : null;
                                                    $rScore = isset($r['score']) && is_numeric($r['score']) ? (float) $r['score'] : null;
                                                @endphp
                                                <li class="flex items-start justify-between gap-2">
                                                    <span class="min-w-0">
                                                        @include('partials.video-title-link', ['title' => $r['title'] ?? '-', 'videoId' => $rVideoId])
                                                        @if($rScore !== null)
                                                            <span class="text-blue-600 font-mono text-xs ml-1">{{ number_format($rScore * 100, 1) }}%</span>
                                                        @endif
                                                    </span>
                                                    <span class="flex gap-0.5 shrink-0">
                                                        <button type="button" @click.stop="submit(1, {{ $wpId ?? 'null' }}, {{ $idx + 1 }}, @js($rScore))" :disabled="busy" class="px-1.5 py-0.5 rounded border text-xs" :class="voteFor({{ $wpId ?? 'null' }}) === 1 ? 'bg-green-100 border-green-400' : 'border-gray-200'">👍</button>
                                                        <button type="button" @click.stop="submit(-1, {{ $wpId ?? 'null' }}, {{ $idx + 1 }}, @js($rScore))" :disabled="busy" class="px-1.5 py-0.5 rounded border text-xs" :class="voteFor({{ $wpId ?? 'null' }}) === -1 ? 'bg-red-100 border-red-400' : 'border-gray-200'">👎</button>
                                                    </span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="mt-2 flex items-center gap-1 text-xs text-gray-500">
                                            <span>Whole search:</span>
                                            <button type="button" @click="submit(1, null, null, null)" :disabled="busy" class="px-1.5 py-0.5 rounded border" :class="voteFor(null) === 1 ? 'bg-green-100' : ''">👍</button>
                                            <button type="button" @click="submit(-1, null, null, null)" :disabled="busy" class="px-1.5 py-0.5 rounded border" :class="voteFor(null) === -1 ? 'bg-red-100' : ''">👎</button>
                                        </div>
                                    @elseif($rowSearchId && count($top6) === 0)
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <span>No results — rate search:</span>
                                            <button type="button" @click="submit(1, null, null, null)" :disabled="busy" class="px-2 py-0.5 rounded border text-sm">👍</button>
                                            <button type="button" @click="submit(-1, null, null, null)" :disabled="busy" class="px-2 py-0.5 rounded border text-sm">👎</button>
                                        </div>
                                    @elseif(!empty($top6))
                                        <ol class="list-decimal list-inside space-y-0.5 text-gray-500">
                                            @foreach($top6 as $r)
                                                @php
                                                    $legacyVideoId = isset($r['video_id']) && is_numeric($r['video_id']) ? (int) $r['video_id'] : null;
                                                @endphp
                                                <li>
                                                    @include('partials.video-title-link', ['title' => $r['title'] ?? '-', 'videoId' => $legacyVideoId])
                                                </li>
                                            @endforeach
                                        </ol>
                                        <p class="text-xs text-gray-400 mt-1">No session id — cannot save feedback for this row.</p>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No search queries recorded in the last 7 days.</p>
        @endif
    </div>

</div>

@include('partials.search-feedback-alpine')
@endsection
