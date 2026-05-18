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

    <!-- Recent Search Queries -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-heading font-medium text-gray-900 mb-4">Recent Search Queries</h3>
        <p class="text-sm text-gray-500 mb-4">User searches from the video library (last 7 days)</p>
        @if(!empty($recentQueries))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Query</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Count</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stop reason</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Response</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Top 6 Results (Confidence)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($recentQueries as $item)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <span class="font-medium">{{ e($item['query'] ?? '-') }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    @if(!empty($item['timestamp']))
                                        {{ \Carbon\Carbon::parse($item['timestamp'])->diffForHumans() }}
                                        <span class="text-gray-400">({{ \Carbon\Carbon::parse($item['timestamp'])->format('M j, g:i A') }})</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $item['result_count'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @php $sr = $item['search_stop_reason'] ?? null; @endphp
                                    @if($sr === 'llm_gate')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-900">LLM gate</span>
                                    @elseif($sr === 'below_score_threshold')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-900">Score cutoff</span>
                                    @elseif(($item['result_count'] ?? null) === 0 || ($item['result_count'] ?? null) === '0')
                                        <span class="text-gray-400" title="Recorded before stop-reason logging, or empty result set">Unknown</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    @if(!empty($item['response_time_ms']))
                                        {{ number_format($item['response_time_ms']) }} ms
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @if(!empty($item['results']))
                                        @php $top6 = array_slice($item['results'], 0, 6); @endphp
                                        <ol class="list-decimal list-inside space-y-0.5 max-w-md">
                                            @foreach($top6 as $r)
                                                <li>
                                                    <span class="font-medium">{{ e($r['title'] ?? '-') }}</span>
                                                    @if(isset($r['score']))
                                                        <span class="text-blue-600 font-mono text-xs"> — {{ number_format((float)$r['score'] * 100, 1) }}%</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ol>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-sm text-gray-500">Showing up to {{ count($recentQueries) }} most recent searches</p>
        @else
            <p class="text-sm text-gray-500">No search queries recorded in the last 7 days.</p>
        @endif

    @php
        $searchFeedback = $searchFeedback ?? [];
        $feedbackSummary = $feedbackSummary ?? ['up' => 0, 'down' => 0, 'total' => 0];
        $feedbackDays = $feedbackDays ?? 30;
    @endphp
    <div class="bg-white rounded-lg shadow-sm p-6" id="search-feedback">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <div>
                <h3 class="text-lg font-heading font-medium text-gray-900">Search feedback</h3>
                <p class="text-sm text-gray-500 mt-1">
                    Thumbs up/down from Semantic search (dashboard). Use this to spot weak queries and bad matches.
                </p>
            </div>
            <form method="GET" action="{{ route('analytics.index') }}" class="flex items-center gap-2">
                <label for="feedback_days" class="text-sm text-gray-600">Period</label>
                <select
                    id="feedback_days"
                    name="feedback_days"
                    onchange="this.form.submit()"
                    class="text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                >
                    @foreach([7 => '7 days', 30 => '30 days', 90 => '90 days'] as $val => $label)
                        <option value="{{ $val }}" @selected($feedbackDays === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if(!empty($searchFeedbackError))
            <div class="bg-amber-50 border border-amber-200 text-amber-900 px-3 py-2 rounded text-sm mb-4">
                Could not load feedback: {{ $searchFeedbackError }}
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total ratings</p>
                <p class="mt-1 text-2xl font-heading font-bold text-gray-900">{{ number_format($feedbackSummary['total']) }}</p>
            </div>
            <div class="rounded-lg border border-green-100 bg-green-50 px-4 py-3">
                <p class="text-xs font-medium text-green-800 uppercase tracking-wide">Thumbs up</p>
                <p class="mt-1 text-2xl font-heading font-bold text-green-900">{{ number_format($feedbackSummary['up']) }}</p>
            </div>
            <div class="rounded-lg border border-red-100 bg-red-50 px-4 py-3">
                <p class="text-xs font-medium text-red-800 uppercase tracking-wide">Thumbs down</p>
                <p class="mt-1 text-2xl font-heading font-bold text-red-900">{{ number_format($feedbackSummary['down']) }}</p>
            </div>
        </div>

        @if(count($searchFeedback) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">When</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vote</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Query</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Namespace</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($searchFeedback as $fb)
                            @php
                                $vote = (int) ($fb['vote'] ?? 0);
                                $isUp = $vote === 1;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                                    @if(!empty($fb['updated_at']))
                                        {{ \Carbon\Carbon::parse($fb['updated_at'])->diffForHumans() }}
                                        <span class="block text-xs text-gray-400">{{ \Carbon\Carbon::parse($fb['updated_at'])->format('M j, g:i A') }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($isUp)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800" title="Relevant">👍 Up</span>
                                    @elseif($vote === -1)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800" title="Not relevant">👎 Down</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 max-w-xs">
                                    <span class="font-medium">{{ e($fb['query'] ?? '—') }}</span>
                                    @if(!empty($fb['search_id']))
                                        <span class="block text-xs text-gray-400 font-mono mt-0.5" title="{{ $fb['search_id'] }}">{{ Str::limit($fb['search_id'], 14, '…') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 font-mono text-xs">{{ $fb['namespace'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    @if(!empty($fb['wp_post_id']))
                                        WP #{{ $fb['wp_post_id'] }}
                                    @else
                                        <span class="text-gray-500">Whole search</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $fb['rank'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 font-mono text-xs">
                                    @if(isset($fb['pinecone_score']) && is_numeric($fb['pinecone_score']))
                                        {{ number_format((float) $fb['pinecone_score'], 4, '.', '') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $fb['source'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-sm text-gray-500">
                Showing {{ count($searchFeedback) }} rating(s) from the last {{ $feedbackDays }} days.
                <a href="{{ route('ai-search.index') }}" class="text-blue-600 hover:underline">Run semantic search</a> to add more.
            </p>
        @else
            <p class="text-sm text-gray-500">
                No feedback in the last {{ $feedbackDays }} days.
                <a href="{{ route('ai-search.index') }}" class="text-blue-600 hover:underline">Rate search results</a> on the Semantic search page.
            </p>
        @endif
    </div>
    </div>
</div>
@endsection


