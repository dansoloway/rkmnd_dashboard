@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-sm text-purple-700 font-medium">MOW/ROW PWA</p>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Featured this week</h1>
            <p class="mt-2 text-sm text-gray-600">Same data as the PWA homepage (<code class="bg-gray-100 px-1 rounded text-xs">GET /api/v1/mow-row/featured-weekly</code>).</p>
        </div>
        <a href="{{ route('mow-row.catalog') }}" class="text-sm text-blue-600 hover:text-blue-800">← Catalog</a>
    </div>

    @if(!empty($error))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ $error }}</div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach(['move' => 'Move of the Week', 'weekly' => 'Rollout of the Week'] as $key => $label)
            @php $item = $featured[$key] ?? null; @endphp
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ $label }}</h2>
                @if(is_array($item) && !empty($item['title']))
                    <p class="mt-3 font-medium text-gray-900">{{ $item['title'] }}</p>
                    <dl class="mt-3 text-sm text-gray-600 space-y-1">
                        <div><dt class="inline font-medium">WP post:</dt> {{ $item['wp_post_id'] ?? '—' }}</div>
                        <div><dt class="inline font-medium">Runtime:</dt> {{ $item['run_time'] ?? '—' }}</div>
                        <div><dt class="inline font-medium">Published:</dt> {{ $item['wp_created'] ?? '—' }}</div>
                    </dl>
                    @if(!empty($item['thumbnail_url']))
                        <img src="{{ $item['thumbnail_url'] }}" alt="" class="mt-4 w-full max-h-48 object-cover rounded-md">
                    @endif
                @else
                    <p class="mt-3 text-gray-500">No featured {{ strtolower($label) }} returned from API.</p>
                @endif
            </div>
        @endforeach
    </div>

    @if(!empty($featured['as_of']))
        <p class="text-xs text-gray-500">As of {{ $featured['as_of'] }}</p>
    @endif
</div>
@endsection
