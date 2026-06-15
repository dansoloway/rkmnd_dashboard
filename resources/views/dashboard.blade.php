@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-heading font-bold text-gray-900">
                    Welcome, {{ Auth::user()->name }}
                </h1>
                <p class="mt-2 text-gray-600">
                    Choose a product to manage search, catalog, and indexing.
                </p>
            </div>
            <form method="POST" action="{{ route('dashboard.clear-cache') }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Clear cache
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-800">{{ session('error') }}</div>
        @endif
        @if(!empty($error))
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md text-sm text-red-800">{{ $error }}</div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach(($products ?? []) as $productCard)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-heading font-bold text-gray-900">{{ $productCard['label'] }}</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ $productCard['consumer'] }}</p>
                    @if(!empty($productCard['error']))
                        <p class="mt-2 text-sm text-red-600">{{ $productCard['error'] }}</p>
                    @endif
                </div>
                <div class="p-6 space-y-4">
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">In catalog</dt>
                            <dd class="text-2xl font-semibold text-gray-900">
                                {{ $productCard['catalogCount'] !== null ? number_format($productCard['catalogCount']) : '—' }}
                            </dd>
                        </div>
                        @if($productCard['id'] === 'ai_search')
                            <div>
                                <dt class="text-gray-500">Search pool (v6)</dt>
                                <dd class="text-2xl font-semibold text-gray-900">
                                    {{ $productCard['searchPoolCount'] !== null ? number_format($productCard['searchPoolCount']) : '—' }}
                                </dd>
                            </div>
                        @else
                            <div>
                                <dt class="text-gray-500">Namespace</dt>
                                <dd class="text-sm font-mono text-gray-800 mt-1">mow_row_v6_title_tags</dd>
                            </div>
                        @endif
                    </dl>

                    @if($productCard['id'] === 'mow_row')
                        <div class="text-sm text-gray-600 space-y-1 border-t border-gray-100 pt-4">
                            <p><strong>Move:</strong> {{ data_get($productCard, 'featuredMove.title', '—') }}</p>
                            <p><strong>Rollout:</strong> {{ data_get($productCard, 'featuredWeekly.title', '—') }}</p>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2 pt-2">
                        <a href="{{ $productCard['routes']['library'] }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            {{ $productCard['id'] === 'mow_row' ? 'Catalog' : 'Library' }}
                        </a>
                        <a href="{{ $productCard['routes']['search'] }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Semantic search
                        </a>
                        <a href="{{ $productCard['routes']['namespaceStudio'] }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Namespace studio
                        </a>
                        @if(!empty($productCard['routes']['analytics']))
                            <a href="{{ $productCard['routes']['analytics'] }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Analytics
                            </a>
                        @endif
                        @if(!empty($productCard['routes']['featured']))
                            <a href="{{ $productCard['routes']['featured'] }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Featured this week
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-lg font-heading font-semibold text-gray-900 mb-4">Platform — WordPress sync</h2>
        @if(!empty($latestSync))
            <div class="border-l-4 {{ $latestSync['status'] === 'completed' ? 'border-green-500' : ($latestSync['status'] === 'failed' ? 'border-red-500' : 'border-yellow-500') }} pl-4">
                <p class="text-sm text-gray-600">
                    Last sync {{ \Carbon\Carbon::parse($latestSync['started_at'])->diffForHumans() }}
                    — found <strong>{{ number_format($latestSync['total_videos_found'] ?? 0) }}</strong>,
                    added <strong class="text-green-600">+{{ number_format($latestSync['new_videos_added'] ?? 0) }}</strong>,
                    updated <strong class="text-blue-600">{{ number_format($latestSync['videos_updated'] ?? 0) }}</strong>
                </p>
                <div class="mt-3 flex gap-2">
                    <a href="{{ route('sync-logs.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Sync logs</a>
                    <form method="POST" action="{{ route('sync-logs.trigger') }}" class="inline" onsubmit="return confirm('Start a new sync from WordPress?');">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Sync now</button>
                    </form>
                </div>
            </div>
        @else
            <p class="text-sm text-gray-600 mb-3">No sync history yet. One WordPress dump feeds both products.</p>
            <form method="POST" action="{{ route('sync-logs.trigger') }}" class="inline" onsubmit="return confirm('Start a new sync from WordPress?');">
                @csrf
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">Trigger sync</button>
            </form>
        @endif
    </div>
</div>
@endsection
