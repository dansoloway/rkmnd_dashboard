@php
    $searchFeedback = $searchFeedback ?? [];
    $feedbackSummary = $feedbackSummary ?? ['up' => 0, 'down' => 0, 'total' => 0];
    $feedbackDays = $feedbackDays ?? 30;
    $feedbackUrl = $feedbackUrl ?? route('ai-search.playground.feedback');
    $analyticsIndexRoute = $analyticsIndexRoute ?? 'ai-search.analytics';
    $analyticsSearchRoute = $analyticsSearchRoute ?? 'ai-search.analytics.search';
    $feedbackCsrf = csrf_token();
    $rateSearchQuery = $rateSearchQuery ?? '';
    $rateSearchNamespace = $rateSearchNamespace ?? ($defaultNamespace ?? 'v6_title_tags');
    $rateSearchVideos = $rateSearchVideos ?? [];
    $rateSearchId = $rateSearchId ?? null;
    $feedbackTab = $feedbackTab ?? 'overview';
    $feedbackAnalytics = $feedbackAnalytics ?? null;
    $feedbackAnalyticsError = $feedbackAnalyticsError ?? null;
    $feedbackNamespace = $feedbackNamespace ?? null;
@endphp

<div class="bg-white rounded-lg shadow-sm p-6" id="search-feedback">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <div>
            <h3 class="text-lg font-heading font-medium text-gray-900">Search feedback</h3>
            <p class="text-sm text-gray-500 mt-1">
                Run a search here and rate results with 👍/👎, or rate recent queries below.
                @if(!empty($namespaceLoadNote))
                    <span class="block mt-1 text-xs text-gray-400">{{ $namespaceLoadNote }}</span>
                @endif
            </p>
        </div>
        <form method="GET" action="{{ route($analyticsIndexRoute) }}" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="tab" value="feedback">
            <input type="hidden" name="feedback_tab" value="{{ $feedbackTab }}">
            <div class="flex items-center gap-2">
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
            </div>
            <div class="flex items-center gap-2">
                <label for="feedback_namespace" class="text-sm text-gray-600">Namespace</label>
                <select
                    id="feedback_namespace"
                    name="feedback_namespace"
                    onchange="this.form.submit()"
                    class="text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:outline-none focus:ring-blue-500 focus:border-blue-500 max-w-[14rem]"
                >
                    <option value="" @selected($feedbackNamespace === null)>All namespaces</option>
                    @foreach(($namespaces ?? []) as $nsVal)
                        <option value="{{ $nsVal }}" @selected($feedbackNamespace === $nsVal)>{{ $nsVal }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <div class="border border-blue-100 bg-blue-50/40 rounded-lg p-4 mb-6">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">Rate a search</h4>
        <form method="POST" action="{{ route($analyticsSearchRoute) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="feedback_days" value="{{ $feedbackDays }}">
            <input type="hidden" name="feedback_tab" value="{{ $feedbackTab }}">
            <input type="hidden" name="feedback_namespace" value="{{ $feedbackNamespace ?? '' }}">
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

    @include('analytics.partials.search-feedback-manager')

    @include('analytics.partials.satisfaction-analytics')
</div>
