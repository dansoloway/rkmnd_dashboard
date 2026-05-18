@php
    $searchFeedback = $searchFeedback ?? [];
    $feedbackSummary = $feedbackSummary ?? ['up' => 0, 'down' => 0, 'total' => 0];
    $feedbackDays = $feedbackDays ?? 30;
    $feedbackUrl = route('ai-search.feedback');
    $feedbackCsrf = csrf_token();
    $rateSearchQuery = $rateSearchQuery ?? '';
    $rateSearchNamespace = $rateSearchNamespace ?? ($defaultNamespace ?? 'v6_title_tags');
    $rateSearchVideos = $rateSearchVideos ?? [];
    $rateSearchId = $rateSearchId ?? null;
@endphp

<div class="bg-white rounded-lg shadow-sm p-6" id="search-feedback">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div>
            <h3 class="text-lg font-heading font-medium text-gray-900">Search feedback</h3>
            <p class="text-sm text-gray-500 mt-1">
                Run a search here and rate results with 👍/👎, or rate recent queries below. History is listed at the bottom.
            </p>
        </div>
        <form method="GET" action="{{ route('analytics.index') }}" class="flex items-center gap-2">
            <label for="feedback_days" class="text-sm text-gray-600">History</label>
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

    <div class="border border-blue-100 bg-blue-50/40 rounded-lg p-4 mb-6">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">Rate a search</h4>
        <form method="POST" action="{{ route('analytics.search') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="feedback_days" value="{{ $feedbackDays }}">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-1">
                    <label for="rate_namespace" class="block text-xs font-medium text-gray-600 mb-1">Namespace</label>
                    <select id="rate_namespace" name="namespace" class="w-full text-sm border border-gray-300 rounded-md px-2 py-2">
                        @foreach(($namespaces ?? []) as $nsVal)
                            <option value="{{ $nsVal }}" @selected($rateSearchNamespace === $nsVal)>{{ $nsVal }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label for="rate_query" class="block text-xs font-medium text-gray-600 mb-1">Query</label>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            id="rate_query"
                            name="query"
                            value="{{ $rateSearchQuery }}"
                            required
                            placeholder="e.g. hip mobility for runners"
                            class="flex-1 text-sm border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        >
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 shrink-0">
                            Search &amp; rate
                        </button>
                    </div>
                </div>
            </div>
        </form>

        @if(!empty($rateSearchError))
            <p class="mt-3 text-sm text-red-700">{{ $rateSearchError }}</p>
        @endif

        @if($rateSearchQuery !== '' || !empty($rateSearchError))
            <div
                class="mt-4"
                x-data="searchFeedbackPanel({
                    searchId: @js($rateSearchId),
                    feedbackUrl: @js($feedbackUrl),
                    csrf: @js($feedbackCsrf),
                    source: 'analytics',
                })"
            >
                @if($rateSearchId)
                    <p class="text-sm text-gray-700 mb-2">
                        Results for <span class="font-medium">{{ e($rateSearchQuery) }}</span>
                        — click 👍 or 👎 on each hit.
                    </p>
                    @include('partials.search-feedback-results', [
                        'videos' => $rateSearchVideos,
                        'searchId' => $rateSearchId,
                    ])
                @endif
            </div>
        @endif
    </div>

    @if(!empty($searchFeedbackError))
        <div class="bg-amber-50 border border-amber-200 text-amber-900 px-3 py-2 rounded text-sm mb-4">
            Could not load feedback history: {{ $searchFeedbackError }}
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

    <h4 class="text-sm font-semibold text-gray-900 mb-3">Feedback history</h4>
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
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($isUp)
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">👍 Up</span>
                                @elseif($vote === -1)
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">👎 Down</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 max-w-xs font-medium">{{ e($fb['query'] ?? '—') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 font-mono text-xs">{{ $fb['namespace'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                @if(!empty($fb['wp_post_id']))
                                    WP #{{ $fb['wp_post_id'] }}
                                @else
                                    Whole search
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $fb['rank'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm font-mono text-xs text-gray-600">
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
        <p class="mt-3 text-sm text-gray-500">{{ count($searchFeedback) }} rating(s) in the last {{ $feedbackDays }} days.</p>
    @else
        <p class="text-sm text-gray-500">No ratings yet in this period. Use <strong>Rate a search</strong> above or the recent-query table below.</p>
    @endif
</div>
