@extends('layouts.app')

@section('content')
@php
    $columnLabel = fn (string $key): string => \Illuminate\Support\Str::title(str_replace('_', ' ', $key));
@endphp
<div class="space-y-6" id="video-metadata-explorer">
    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-start">
        <div>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Video metadata explorer</h1>
            <p class="mt-2 text-gray-600">
                Rows from the AI pipeline <code class="text-sm bg-gray-100 px-1 rounded">videos</code> table for this tenant.
                Total matching filters: <strong>{{ number_format($total) }}</strong>
                (showing {{ count($videos) }} on this page).
            </p>
            <p class="mt-2 text-sm text-gray-600">
                <em>Public AI search index</em> limits rows to the same eligibility rules as the default
                <code class="text-xs bg-gray-100 px-1 rounded">v6_title_tags</code> pipeline (published <code class="text-xs bg-gray-100 px-1 rounded">video</code>, JW Player id, allowed category for AI).
                Add columns <code class="text-xs bg-gray-100 px-1 rounded">thumbnail_url</code> and <code class="text-xs bg-gray-100 px-1 rounded">audio_preview_url</code> to inspect assets.
            </p>
        </div>
        <a href="{{ route('videos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition whitespace-nowrap">
            Back to Video Library
        </a>
    </div>

    @if(isset($error))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ $error }}
        </div>
    @endif

    <form method="GET" action="{{ route('videos.database') }}" id="explorer-form" class="space-y-6">
        <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Filters</h2>
            <div class="max-w-2xl">
                <label for="category_for_ai" class="block text-sm font-medium text-gray-700 mb-1">Category for AI</label>
                <select id="category_for_ai" name="category_for_ai"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All categories</option>
                    @foreach($categories_for_ai ?? [] as $category => $count)
                        <option value="{{ $category }}" {{ ($filters['category_for_ai'] ?? '') === $category ? 'selected' : '' }}>
                            {{ $category }} ({{ $count }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">WordPress meta <code class="bg-gray-100 px-0.5 rounded">details_category_for_ai</code> (synced to pipeline column <code class="bg-gray-100 px-0.5 rounded">category_for_ai</code>).</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Title, instructor, descriptions…"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Sync status</label>
                    <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Any</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ ($filters['status'] ?? '') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="error" {{ ($filters['status'] ?? '') === 'error' ? 'selected' : '' }}>Error</option>
                    </select>
                </div>
                <div>
                    <label for="post_type" class="block text-sm font-medium text-gray-700 mb-1">Post type</label>
                    <select id="post_type" name="post_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Any</option>
                        <option value="video" {{ ($filters['post_type'] ?? '') === 'video' ? 'selected' : '' }}>video</option>
                        <option value="scheduled" {{ ($filters['post_type'] ?? '') === 'scheduled' ? 'selected' : '' }}>scheduled</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-800 cursor-pointer pb-2">
                        <input type="checkbox" name="in_ai_search_index" value="1"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                               {{ !empty($filters['in_ai_search_index']) ? 'checked' : '' }}>
                        <span>Public AI search pool only (<code class="text-xs bg-gray-100 px-0.5 rounded">v6_title_tags</code> rules)</span>
                    </label>
                </div>
                <div>
                    <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">Per page</label>
                    <select id="limit" name="limit" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        @foreach([25, 50, 100] as $n)
                            <option value="{{ $n }}" {{ (int) $limit === $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h2 class="text-lg font-semibold text-gray-900">Columns</h2>
                <p class="text-sm text-gray-500">Choices are saved in this browser (localStorage) and applied when you open the page without column parameters.</p>
            </div>
            <div class="space-y-4">
                @foreach($columnGroups as $groupLabel => $keys)
                    <fieldset class="border border-gray-200 rounded-md p-4">
                        <legend class="text-sm font-medium text-gray-800 px-1">{{ $groupLabel }}</legend>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2">
                            @foreach($keys as $key)
                                @if(in_array($key, $allowedColumns, true))
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="checkbox" name="cols[]" value="{{ $key }}"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                               {{ in_array($key, $selectedColumns, true) ? 'checked' : '' }}>
                                        <span>{{ $columnLabel($key) }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                    Apply filters and columns
                </button>
                <button type="button" id="explorer-select-default" class="px-4 py-2 bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200 transition">
                    Reset columns to default
                </button>
            </div>
        </div>

        <input type="hidden" name="offset" value="0" id="explorer-offset-reset">
    </form>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-sm text-gray-600">
                Page <strong>{{ $currentPage }}</strong> of <strong>{{ $totalPages }}</strong>
            </p>
            <div class="flex gap-2">
                @if($offset > 0)
                    <a href="{{ request()->fullUrlWithQuery(['offset' => max(0, $offset - $limit)]) }}"
                       class="px-3 py-1.5 text-sm bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200">Previous</a>
                @else
                    <span class="px-3 py-1.5 text-sm text-gray-400 rounded-md">Previous</span>
                @endif
                @if($offset + $limit < $total)
                    <a href="{{ request()->fullUrlWithQuery(['offset' => $offset + $limit]) }}"
                       class="px-3 py-1.5 text-sm bg-gray-100 text-gray-800 rounded-md hover:bg-gray-200">Next</a>
                @else
                    <span class="px-3 py-1.5 text-sm text-gray-400 rounded-md">Next</span>
                @endif
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach($selectedColumns as $col)
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                {{ $columnLabel($col) }}
                            </th>
                        @endforeach
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Detail</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($videos as $video)
                        <tr class="hover:bg-gray-50 align-top">
                            @foreach($selectedColumns as $col)
                                @php
                                    $cell = $video[$col] ?? null;
                                    $isUrl = is_string($cell) && (str_starts_with($cell, 'http://') || str_starts_with($cell, 'https://'));
                                @endphp
                                <td class="px-4 py-2 text-gray-900 max-w-xs">
                                    @if($cell === null || $cell === '')
                                        <span class="text-gray-400">—</span>
                                    @elseif(is_bool($cell))
                                        <span class="font-medium {{ $cell ? 'text-green-700' : 'text-gray-500' }}">{{ $cell ? 'Yes' : 'No' }}</span>
                                    @elseif($isUrl)
                                        <a href="{{ $cell }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline break-all" title="{{ $cell }}">{{ \Illuminate\Support\Str::limit($cell, 48) }}</a>
                                    @else
                                        @php $s = is_scalar($cell) ? (string) $cell : json_encode($cell); @endphp
                                        @if(strlen($s) > 120)
                                            <span class="explorer-short break-words">{{ \Illuminate\Support\Str::limit($s, 120) }}</span>
                                            <span class="explorer-long hidden break-words whitespace-pre-wrap">{{ $s }}</span>
                                            <button type="button" class="explorer-toggle text-xs text-blue-600 hover:underline ml-1">Show more</button>
                                        @else
                                            <span class="break-words whitespace-pre-wrap">{{ $s }}</span>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-4 py-2 whitespace-nowrap">
                                @if(!empty($video['id']))
                                    <a href="{{ route('videos.show', $video['id']) }}" class="text-blue-600 hover:underline">View</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($selectedColumns) + 1 }}" class="px-4 py-8 text-center text-gray-500">
                                No videos found for these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="application/json" id="explorer-default-cols-data">@json($defaultColumns ?? [])</script>
<script>
(function () {
    var KEY = 'videoExplorerColumns';
    var defaultEl = document.getElementById('explorer-default-cols-data');
    var DEFAULT_COLS = defaultEl ? JSON.parse(defaultEl.textContent || '[]') : [];

    var params = new URLSearchParams(window.location.search);
    var hasColsParam = false;
    params.forEach(function (_, k) {
        if (k === 'cols[]' || k === 'cols') {
            hasColsParam = true;
        }
    });
    if (!hasColsParam) {
        try {
            var raw = localStorage.getItem(KEY);
            if (raw) {
                var arr = JSON.parse(raw);
                if (Array.isArray(arr) && arr.length) {
                    var u = new URL(window.location.href);
                    arr.forEach(function (c) {
                        u.searchParams.append('cols[]', c);
                    });
                    window.location.replace(u.toString());
                    return;
                }
            }
        } catch (e) {}
    }

    var form = document.getElementById('explorer-form');
    if (form) {
        form.addEventListener('submit', function () {
            var checked = Array.prototype.slice
                .call(document.querySelectorAll('input[name="cols[]"]:checked'))
                .map(function (i) { return i.value; });
            localStorage.setItem(KEY, JSON.stringify(checked));
        });
    }

    var resetBtn = document.getElementById('explorer-select-default');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            document.querySelectorAll('input[name="cols[]"]').forEach(function (cb) {
                cb.checked = DEFAULT_COLS.indexOf(cb.value) !== -1;
            });
        });
    }

    document.querySelectorAll('.explorer-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var td = btn.closest('td');
            if (!td) return;
            var shortEl = td.querySelector('.explorer-short');
            var longEl = td.querySelector('.explorer-long');
            if (!shortEl || !longEl) return;
            var expanded = !longEl.classList.contains('hidden');
            if (expanded) {
                longEl.classList.add('hidden');
                shortEl.classList.remove('hidden');
                btn.textContent = 'Show more';
            } else {
                longEl.classList.remove('hidden');
                shortEl.classList.add('hidden');
                btn.textContent = 'Show less';
            }
        });
    });
})();
</script>
@endsection
