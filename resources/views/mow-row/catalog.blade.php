@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-sm text-purple-700 font-medium">MOW/ROW PWA</p>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Catalog</h1>
            <p class="mt-2 text-gray-600 text-sm max-w-3xl">
                Videos indexed in Pinecone namespace <code class="bg-gray-100 px-1 rounded text-xs">mow_row_v6_title_tags</code>
                for Move of the Week and Rollout of the Week (<code class="bg-gray-100 px-1 rounded text-xs">post_type=scheduled</code>,
                <code class="bg-gray-100 px-1 rounded text-xs">scheduled_content_type</code> = move or weekly).
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:text-blue-800">← Home</a>
    </div>

    @if(!empty($error))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ $error }}</div>
    @endif

    <form method="GET" action="{{ route('mow-row.catalog') }}" class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search title</label>
            <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
        </div>
        <div>
            <label for="content_type" class="block text-sm font-medium text-gray-700 mb-1">Content type</label>
            <select name="content_type" id="content_type" class="px-3 py-2 border border-gray-300 rounded-md">
                <option value="">All MOW/ROW</option>
                <option value="move" @selected(($filters['content_type'] ?? '') === 'move')>Move of the Week</option>
                <option value="weekly" @selected(($filters['content_type'] ?? '') === 'weekly')>Rollout of the Week</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Apply</button>
    </form>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <p class="text-sm text-gray-600"><strong>{{ number_format($total) }}</strong> videos in namespace</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Title</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">WP ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">JWP ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Namespaces</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($videos as $video)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <a href="{{ route('videos.show', ['id' => $video['id'], 'product' => 'mow_row']) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $video['title'] ?? 'Untitled' }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                @php $sct = strtolower(trim((string) ($video['scheduled_content_type'] ?? ''))); @endphp
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $sct === 'move' ? 'bg-green-100 text-green-800' : ($sct === 'weekly' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-700') }}">
                                    {{ $sct === 'move' ? 'Move' : ($sct === 'weekly' ? 'Rollout' : ($sct ?: '—')) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $video['wp_post_id'] ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $video['jwp_id'] ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $video['embedding_namespaces'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">No videos found in this namespace.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
