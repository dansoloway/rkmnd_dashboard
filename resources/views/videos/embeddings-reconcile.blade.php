@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-start">
        <div>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Pinecone vs DB reconcile</h1>
            <p class="mt-2 text-gray-600">
                Compares Pinecone vector IDs in a namespace (JW Player <code class="text-xs bg-gray-100 px-1 rounded">jwp_id</code>)
                to pipeline <code class="text-xs bg-gray-100 px-1 rounded">videos</code> rows that match the public-search
                <code class="text-xs bg-gray-100 px-1 rounded">category_for_ai</code> rules — same logic as
                <code class="text-xs bg-gray-100 px-1 rounded">scripts/pinecone/compare_pinecone_category_for_ai.py</code>.
                Scoped to your tenant. Read-only; runs on the AI pipeline server (needs Pinecone credentials there).
            </p>
            <p class="mt-2 text-sm text-gray-600">
                Other namespaces may use different Pinecone ID formats; this tool is intended primarily for
                <code class="text-xs bg-gray-100 px-1 rounded">v6_title_tags</code>.
            </p>
        </div>
        <div class="flex flex-col gap-2 items-start sm:items-end">
            <a href="{{ route('videos.database') }}" class="text-sm text-blue-700 hover:underline">Video metadata explorer</a>
            <a href="{{ route('videos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition whitespace-nowrap">
                Back to Video Library
            </a>
        </div>
    </div>

    @if(!empty($error))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ $error }}
        </div>
    @endif

    <form method="GET" action="{{ route('videos.embeddings-reconcile') }}" class="bg-white rounded-lg shadow-sm p-6 space-y-4">
        <input type="hidden" name="run" value="1">
        <h2 class="text-lg font-semibold text-gray-900">Parameters</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label for="namespace" class="block text-sm font-medium text-gray-700 mb-1">Namespace</label>
                <input type="text" id="namespace" name="namespace" value="{{ $filters['namespace'] }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
            </div>
            <div>
                <label for="list_limit" class="block text-sm font-medium text-gray-700 mb-1">Pinecone list page size</label>
                <input type="number" id="list_limit" name="list_limit" min="1" max="100" value="{{ (int) $filters['list_limit'] }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="lg:col-span-2">
                <label for="allow_categories" class="block text-sm font-medium text-gray-700 mb-1">Allow categories (optional)</label>
                <input type="text" id="allow_categories" name="allow_categories" value="{{ $filters['allow_categories'] }}"
                       placeholder="MBR Class, DVD Segment, BBB Exercise"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
                <p class="mt-1 text-xs text-gray-500">Comma-separated; leave empty for defaults.</p>
            </div>
            <div>
                <label for="post_status" class="block text-sm font-medium text-gray-700 mb-1">Post status (optional)</label>
                <input type="text" id="post_status" name="post_status" value="{{ $filters['post_status'] }}"
                       placeholder="publish"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div>
                <label for="post_type" class="block text-sm font-medium text-gray-700 mb-1">Post type (optional)</label>
                <input type="text" id="post_type" name="post_type" value="{{ $filters['post_type'] }}"
                       placeholder="video"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div>
                <label for="max_missing" class="block text-sm font-medium text-gray-700 mb-1">Max rows: missing</label>
                <input type="number" id="max_missing" name="max_missing" min="1" max="50000" value="{{ (int) $filters['max_missing'] }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="max_orphans" class="block text-sm font-medium text-gray-700 mb-1">Max rows: orphans</label>
                <input type="number" id="max_orphans" name="max_orphans" min="1" max="50000" value="{{ (int) $filters['max_orphans'] }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="max_unexpected" class="block text-sm font-medium text-gray-700 mb-1">Max rows: unexpected</label>
                <input type="number" id="max_unexpected" name="max_unexpected" min="1" max="50000" value="{{ (int) $filters['max_unexpected'] }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
        <div class="flex flex-wrap gap-3 items-center">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                Run reconcile
            </button>
            @if(!$ran)
                <span class="text-sm text-gray-500">Submit to scan Pinecone and compare (may take minutes).</span>
            @endif
        </div>
    </form>

    @if($ran && $payload && !empty($payload['summary']))
        @php $s = $payload['summary']; @endphp
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Summary</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
                <div><dt class="text-gray-500">Namespace</dt><dd class="font-mono font-medium">{{ $s['namespace'] ?? '' }}</dd></div>
                <div><dt class="text-gray-500">Pinecone vectors</dt><dd>{{ number_format((int) ($s['pinecone_vector_count'] ?? 0)) }}</dd></div>
                <div><dt class="text-gray-500">DB expected (allow-list)</dt><dd>{{ number_format((int) ($s['db_expected_by_category_count'] ?? 0)) }}</dd></div>
                <div><dt class="text-gray-500">In both</dt><dd>{{ number_format((int) ($s['in_both_count'] ?? 0)) }}</dd></div>
                <div><dt class="text-gray-500">Missing from Pinecone</dt><dd>{{ number_format((int) ($s['missing_from_pinecone_count'] ?? 0)) }}</dd></div>
                <div><dt class="text-gray-500">Pinecone not in DB</dt><dd>{{ number_format((int) ($s['pinecone_not_in_db_count'] ?? 0)) }}</dd></div>
                <div><dt class="text-gray-500">Unexpected in index</dt><dd>{{ number_format((int) ($s['pinecone_in_db_but_not_expected_count'] ?? 0)) }}</dd></div>
                <div><dt class="text-gray-500">DB allow-list but no jwp_id</dt><dd>{{ number_format((int) ($s['db_expected_but_no_jwp_count'] ?? 0)) }}</dd></div>
            </dl>
            @if(!empty($s['missing_from_pinecone_truncated']) || !empty($s['pinecone_not_in_db_truncated']) || !empty($s['pinecone_unexpected_truncated']))
                <p class="mt-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded px-3 py-2">
                    Table lists are capped per bucket; counts above are full totals.
                    @if(!empty($s['missing_from_pinecone_truncated'])) Missing list truncated. @endif
                    @if(!empty($s['pinecone_not_in_db_truncated'])) Orphans list truncated. @endif
                    @if(!empty($s['pinecone_unexpected_truncated'])) Unexpected list truncated. @endif
                </p>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Gap rows</h2>
                <p class="text-sm text-gray-500 mt-1">{{ count($tableRows) }} row(s) shown (after caps).</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">jwp_id</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WP post</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">category_for_ai</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">post_status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">post_type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($tableRows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">{{ $row['status_label'] }}</td>
                                <td class="px-4 py-2 text-sm font-mono text-gray-900">{{ $row['jwp_id'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $row['wp_post_id'] !== '' && $row['wp_post_id'] !== null ? $row['wp_post_id'] : '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 max-w-xs truncate" title="{{ $row['title'] }}">{{ $row['title'] !== '' ? $row['title'] : '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $row['category_for_ai'] !== '' ? $row['category_for_ai'] : '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $row['post_status'] !== '' ? $row['post_status'] : '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $row['post_type'] !== '' ? $row['post_type'] : '—' }}</td>
                                <td class="px-4 py-2 text-xs text-gray-600 max-w-md">{{ $row['reason'] !== '' ? $row['reason'] : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500">No gap rows in this result set.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($ran && !$payload && empty($error))
        <p class="text-gray-600 text-sm">No data returned.</p>
    @endif
</div>
@endsection
