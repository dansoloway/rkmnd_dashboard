@php
    $feedbackTab = $feedbackTab ?? 'overview';
    $analyticsIndexRoute = $analyticsIndexRoute ?? 'ai-search.analytics';
    $feedbackDays = $feedbackDays ?? 30;
    $feedbackSummary = $feedbackSummary ?? [];
    $feedbackAnalytics = $feedbackAnalytics ?? null;
    $searchFeedback = $searchFeedback ?? [];
    $byQuery = is_array($feedbackAnalytics['by_query'] ?? null) ? $feedbackAnalytics['by_query'] : [];
    $byVideo = is_array($feedbackAnalytics['by_video'] ?? null) ? $feedbackAnalytics['by_video'] : [];
    $feedbackNamespace = $feedbackNamespace ?? null;
    $tabParams = ['feedback_days' => $feedbackDays];
    if ($feedbackNamespace !== null && $feedbackNamespace !== '') {
        $tabParams['feedback_namespace'] = $feedbackNamespace;
    }
    $tabs = [
        'overview' => 'Overview',
        'by_query' => 'By search query',
        'by_video' => 'By video',
        'detail' => 'All ratings',
    ];
@endphp

@if(!empty($feedbackAnalyticsError))
    <div class="bg-amber-50 border border-amber-200 text-amber-900 px-3 py-2 rounded text-sm mb-4">
        Could not load feedback analytics: {{ $feedbackAnalyticsError }}
        @if(!empty($searchFeedbackError))
            <span class="block mt-1">Fallback list also failed: {{ $searchFeedbackError }}</span>
        @endif
    </div>
@endif

<nav class="flex flex-wrap gap-2 border-b border-gray-200 mb-6" aria-label="Feedback views">
    @foreach($tabs as $key => $label)
        <a
            href="{{ route($analyticsIndexRoute, array_merge($tabParams, ['feedback_tab' => $key])) }}#search-feedback"
            class="px-3 py-2 text-sm font-medium rounded-t-md border-b-2 -mb-px {{ $feedbackTab === $key ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' }}"
        >{{ $label }}</a>
    @endforeach
</nav>

<p class="text-xs text-gray-500 mb-4">
    Each rating is stored as <strong>search query</strong> → <strong>video</strong> (or whole-search) → <strong>👍/👎</strong>.
    Re-rating the same result updates the existing row.
    @if($feedbackNamespace)
        <span class="block mt-1 text-gray-600">Showing ratings for namespace: <span class="font-mono">{{ e($feedbackNamespace) }}</span></span>
    @endif
</p>

@if($feedbackTab === 'overview')
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
            <p class="text-xs text-gray-500 uppercase">Total ratings</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($feedbackSummary['total'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-green-100 bg-green-50 px-3 py-2">
            <p class="text-xs text-green-800 uppercase">Thumbs up</p>
            <p class="text-xl font-bold text-green-900">{{ number_format($feedbackSummary['up'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-red-100 bg-red-50 px-3 py-2">
            <p class="text-xs text-red-800 uppercase">Thumbs down</p>
            <p class="text-xl font-bold text-red-900">{{ number_format($feedbackSummary['down'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
            <p class="text-xs text-gray-500 uppercase">Video ratings</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($feedbackSummary['video_ratings'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
            <p class="text-xs text-gray-500 uppercase">Distinct queries</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($feedbackSummary['distinct_queries'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
            <p class="text-xs text-gray-500 uppercase">Distinct videos</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($feedbackSummary['distinct_videos'] ?? 0) }}</p>
        </div>
    </div>

    @if(count($byQuery) > 0)
        <h4 class="text-sm font-semibold text-gray-900 mb-2">Queries with thumbs down on a video</h4>
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Query</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Down</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Up</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Videos rated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach(collect($byQuery)->sortByDesc('down')->take(10) as $q)
                        @if(($q['down'] ?? 0) > 0)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900 max-w-md">{{ e($q['query'] ?? '') }}</td>
                                <td class="px-3 py-2 text-red-700">{{ $q['down'] ?? 0 }}</td>
                                <td class="px-3 py-2 text-green-700">{{ $q['up'] ?? 0 }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ count($q['video_ratings'] ?? []) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(count($byVideo) > 0)
        <h4 class="text-sm font-semibold text-gray-900 mb-2">Videos with the most thumbs down</h4>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Video</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Down</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Up</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Queries</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach(collect($byVideo)->sortByDesc('down')->take(10) as $v)
                        @if(($v['down'] ?? 0) > 0)
                            <tr>
                                <td class="px-3 py-2">
                                    @include('partials.video-title-link', [
                                        'title' => $v['video_title'] ?? ('WP #'.($v['wp_post_id'] ?? '?')),
                                        'videoId' => $v['video_id'] ?? null,
                                    ])
                                </td>
                                <td class="px-3 py-2 text-red-700">{{ $v['down'] ?? 0 }}</td>
                                <td class="px-3 py-2 text-green-700">{{ $v['up'] ?? 0 }}</td>
                                <td class="px-3 py-2 text-gray-600">{{ count($v['query_ratings'] ?? []) }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(($feedbackSummary['total'] ?? 0) === 0)
        <p class="text-sm text-gray-500">No ratings in this period yet.</p>
    @endif

@elseif($feedbackTab === 'by_query')
    <h4 class="text-sm font-semibold text-gray-900 mb-3">Ratings grouped by search query</h4>
    @if(count($byQuery) === 0)
        <p class="text-sm text-gray-500">No ratings in this period.</p>
    @else
        <div class="space-y-3">
            @foreach($byQuery as $q)
                <details class="border border-gray-200 rounded-lg bg-white group">
                    <summary class="cursor-pointer px-4 py-3 flex flex-wrap items-center gap-3 text-sm list-none">
                        <span class="font-medium text-gray-900 flex-1 min-w-[12rem]">{{ e($q['query'] ?? '') }}</span>
                        <span class="text-xs font-mono text-gray-400">{{ $q['namespace'] ?? '' }}</span>
                        <span class="text-green-700">👍 {{ $q['up'] ?? 0 }}</span>
                        <span class="text-red-700">👎 {{ $q['down'] ?? 0 }}</span>
                        <span class="text-gray-500">{{ $q['total'] ?? 0 }} total</span>
                        @if(!empty($q['last_rated_at']))
                            <span class="text-xs text-gray-400">@include('partials.analytics-datetime', ['isoTimestamp' => $q['last_rated_at'], 'showRelative' => false])</span>
                        @endif
                    </summary>
                    <div class="px-4 pb-4 border-t border-gray-100">
                        @if(!empty($q['whole_search_votes']))
                            <p class="text-xs font-medium text-gray-600 mt-2 mb-1">Whole-search ratings</p>
                            <ul class="text-sm text-gray-700 space-y-1 mb-3">
                                @foreach($q['whole_search_votes'] as $wv)
                                    <li>
                                        @if((int)($wv['vote'] ?? 0) === 1) 👍 @else 👎 @endif
                                        <span class="text-gray-400 text-xs">{{ $wv['search_id'] ?? '' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if(!empty($q['video_ratings']))
                            <p class="text-xs font-medium text-gray-600 mb-1">Per-video ratings (query → video → vote)</p>
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs text-gray-500">
                                        <th class="py-1 pr-3">Video</th>
                                        <th class="py-1 pr-3">Vote</th>
                                        <th class="py-1 pr-3">Rank</th>
                                        <th class="py-1">Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($q['video_ratings'] as $vr)
                                        <tr class="border-t border-gray-50">
                                            <td class="py-2 pr-3">
                                                @include('partials.video-title-link', [
                                                    'title' => $vr['video_title'] ?? ('WP #'.($vr['wp_post_id'] ?? '?')),
                                                    'videoId' => $vr['video_id'] ?? null,
                                                ])
                                            </td>
                                            <td class="py-2 pr-3">{{ (int)($vr['vote'] ?? 0) === 1 ? '👍' : '👎' }}</td>
                                            <td class="py-2 pr-3 text-gray-600">{{ $vr['rank'] ?? '—' }}</td>
                                            <td class="py-2 font-mono text-xs">{{ isset($vr['pinecone_score']) && is_numeric($vr['pinecone_score']) ? number_format((float)$vr['pinecone_score'], 4) : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    @endif

@elseif($feedbackTab === 'by_video')
    <h4 class="text-sm font-semibold text-gray-900 mb-3">Ratings grouped by video</h4>
    @if(count($byVideo) === 0)
        <p class="text-sm text-gray-500">No per-video ratings in this period.</p>
    @else
        <div class="space-y-3">
            @foreach($byVideo as $v)
                <details class="border border-gray-200 rounded-lg bg-white">
                    <summary class="cursor-pointer px-4 py-3 flex flex-wrap items-center gap-3 text-sm list-none">
                        <span class="flex-1 min-w-[12rem]">
                            @include('partials.video-title-link', [
                                'title' => $v['video_title'] ?? ('WP #'.($v['wp_post_id'] ?? '?')),
                                'videoId' => $v['video_id'] ?? null,
                            ])
                        </span>
                        <span class="text-green-700">👍 {{ $v['up'] ?? 0 }}</span>
                        <span class="text-red-700">👎 {{ $v['down'] ?? 0 }}</span>
                        <span class="text-gray-500">{{ $v['total'] ?? 0 }} ratings</span>
                    </summary>
                    <div class="px-4 pb-4 border-t border-gray-100">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs text-gray-500">
                                    <th class="py-1 pr-3">Search query</th>
                                    <th class="py-1 pr-3">Namespace</th>
                                    <th class="py-1 pr-3">Vote</th>
                                    <th class="py-1">Rank</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($v['query_ratings'] ?? [] as $qr)
                                    <tr class="border-t border-gray-50">
                                        <td class="py-2 pr-3 font-medium text-gray-900">{{ e($qr['query'] ?? '') }}</td>
                                        <td class="py-2 pr-3 text-xs font-mono text-gray-500">{{ $qr['namespace'] ?? '' }}</td>
                                        <td class="py-2 pr-3">{{ (int)($qr['vote'] ?? 0) === 1 ? '👍' : '👎' }}</td>
                                        <td class="py-2 text-gray-600">{{ $qr['rank'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endforeach
        </div>
    @endif

@else
    <h4 class="text-sm font-semibold text-gray-900 mb-3">All ratings (query → video → vote)</h4>
    @if(count($searchFeedback) > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Search date</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vote</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Query</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Video</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Namespace</th>
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
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @include('partials.analytics-datetime', ['isoTimestamp' => $fb['search_created_at'] ?? $fb['updated_at'] ?? null])
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($isUp)
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">👍 Up</span>
                                @elseif($vote === -1)
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">👎 Down</span>
                                @else — @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 max-w-xs font-medium">{{ e($fb['query'] ?? '—') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                @if(!empty($fb['wp_post_id']))
                                    @include('partials.video-title-link', [
                                        'title' => $fb['video_title'] ?? ('WP #'.$fb['wp_post_id']),
                                        'videoId' => $fb['video_id'] ?? null,
                                    ])
                                @else
                                    <span class="text-gray-500 italic">Whole search</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 font-mono text-xs">{{ $fb['namespace'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $fb['rank'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-xs text-gray-600">
                                @if(isset($fb['pinecone_score']) && is_numeric($fb['pinecone_score']))
                                    {{ number_format((float) $fb['pinecone_score'], 4, '.', '') }}
                                @else — @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $fb['source'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-sm text-gray-500">{{ count($searchFeedback) }} rating(s) in the last {{ $feedbackDays }} days.</p>
    @else
        <p class="text-sm text-gray-500">No ratings yet in this period.</p>
    @endif
@endif
