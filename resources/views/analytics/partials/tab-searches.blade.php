@php
    $queryUserFilters = $queryUserFilters ?? ['user_email' => null, 'user' => null];
    $activeFilter = $queryUserFilters['user_email'] ?? $queryUserFilters['user'] ?? null;
    $recentQueries = $recentQueries ?? [];
    $queriesByUser = $queriesByUser ?? [];
@endphp

{{-- Filter banner --}}
@if($activeFilter)
    <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 flex items-center justify-between">
        <p class="text-sm text-blue-900">
            Showing searches for <span class="font-semibold">{{ e($activeFilter) }}</span>
        </p>
        <a href="{{ route('analytics.index', ['tab' => 'searches']) }}" class="text-sm font-medium text-blue-700 hover:text-blue-900">Clear filter</a>
    </div>
@endif

{{-- Filter form --}}
<div class="bg-white rounded-lg shadow-sm p-5">
    <form method="get" action="{{ route('analytics.index') }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="searches">
        <div>
            <label for="query-user-filter" class="block text-xs font-medium text-gray-500 mb-1">Filter by name or email</label>
            <input type="text" name="user" id="query-user-filter" value="{{ e($queryUserFilters['user'] ?? $queryUserFilters['user_email'] ?? '') }}" placeholder="Name or email" class="rounded-md border-gray-300 shadow-sm text-sm">
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-3 py-2 text-sm rounded-md bg-gray-800 text-white hover:bg-gray-700">Filter</button>
            @if($activeFilter)
                <a href="{{ route('analytics.index', ['tab' => 'searches']) }}" class="px-3 py-2 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Clear</a>
            @endif
        </div>
    </form>
</div>

{{-- Recent Search Queries --}}
<div class="bg-white rounded-lg shadow-sm p-5" id="recent-queries">
    <h3 class="text-lg font-heading font-medium text-gray-900 mb-2">Recent Search Queries</h3>
    <p class="text-sm text-gray-500 mb-4">Production searches (last 7 days). Rate results when a session id is available.</p>
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
                                    <span class="text-gray-400">&mdash;</span>
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
                                        <span>No results &mdash; rate search:</span>
                                        <button type="button" @click="submit(1, null, null, null)" :disabled="busy" class="px-2 py-0.5 rounded border text-sm">👍</button>
                                        <button type="button" @click="submit(-1, null, null, null)" :disabled="busy" class="px-2 py-0.5 rounded border text-sm">👎</button>
                                    </div>
                                @elseif(!empty($top6))
                                    <ol class="list-decimal list-inside space-y-0.5 text-gray-500">
                                        @foreach($top6 as $r)
                                            @php $legacyVideoId = isset($r['video_id']) && is_numeric($r['video_id']) ? (int) $r['video_id'] : null; @endphp
                                            <li>@include('partials.video-title-link', ['title' => $r['title'] ?? '-', 'videoId' => $legacyVideoId])</li>
                                        @endforeach
                                    </ol>
                                    <p class="text-xs text-gray-400 mt-1">No session id &mdash; cannot save feedback for this row.</p>
                                @else
                                    <span class="text-gray-400">&mdash;</span>
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

{{-- Searches by user --}}
<div class="bg-white rounded-lg shadow-sm p-5">
    <h3 class="text-lg font-heading font-medium text-gray-900 mb-2">Searches by user</h3>
    <p class="text-sm text-gray-500 mb-4">WordPress users grouped by email (last 7 days).</p>
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
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $uName !== '' ? e($uName) : '&mdash;' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $uEmail !== '' ? e($uEmail) : 'Anonymous' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ (int) ($userRow['search_count'] ?? 0) }}</td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
                                @include('partials.analytics-datetime', ['isoTimestamp' => $userRow['last_search_at'] ?? null])
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($uEmail !== '')
                                    <a href="{{ route('analytics.index', ['tab' => 'searches', 'user_email' => $uEmail]) }}" class="text-blue-600 hover:text-blue-800">View searches</a>
                                @else
                                    <span class="text-gray-400">&mdash;</span>
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
