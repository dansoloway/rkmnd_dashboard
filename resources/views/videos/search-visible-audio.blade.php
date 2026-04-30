@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start">
        <div>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Search-visible videos with audio</h1>
            <p class="mt-2 text-gray-600">
                Shows videos eligible for the public Search API pool (<code class="text-xs bg-gray-100 px-1 rounded">v6_title_tags</code> rules),
                with thumbnail, audio script text, and the audio preview file.
            </p>
            <p class="mt-2 text-sm text-gray-600">
                This page only displays rows that have an <code class="text-xs bg-gray-100 px-1 rounded">audio_preview_url</code>.
                (API returned {{ number_format($rawCount ?? 0) }} rows for this page; showing {{ number_format(count($videos ?? [])) }} with audio.)
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('videos.database', array_merge(request()->query(), ['in_ai_search_index' => 1])) }}"
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition whitespace-nowrap">
                Open metadata explorer
            </a>
            <a href="{{ route('videos.index') }}"
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition whitespace-nowrap">
                Back to Video Library
            </a>
        </div>
    </div>

    @if(isset($error))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-800">{{ $error }}</p>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="GET" action="{{ route('videos.search-visible-audio') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Title, instructor, descriptions…"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="post_type" class="block text-sm font-medium text-gray-700 mb-1">Post type</label>
                    <select id="post_type" name="post_type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Any</option>
                        <option value="video" {{ ($filters['post_type'] ?? '') === 'video' ? 'selected' : '' }}>video</option>
                        <option value="scheduled" {{ ($filters['post_type'] ?? '') === 'scheduled' ? 'selected' : '' }}>scheduled</option>
                    </select>
                </div>

                <div>
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
                    <p class="mt-1 text-xs text-gray-500">If “LT” is a category, select it here.</p>
                </div>

                <div>
                    <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">Results per page</label>
                    <select id="limit" name="limit"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        @foreach([25, 50, 100, 200] as $n)
                            <option value="{{ $n }}" {{ (int) ($limit ?? 50) === $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-between items-center">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-6 rounded-md shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Apply
                </button>
                <a href="{{ route('videos.search-visible-audio') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                    Clear
                </a>
            </div>
        </form>
    </div>

    @if(!empty($videos) && count($videos) > 0)
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Video</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Audio</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Audio script</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($videos as $video)
                            <tr class="align-top hover:bg-gray-50">
                                <td class="px-4 py-4 w-80">
                                    <div class="flex gap-3">
                                        <a href="{{ route('videos.show', $video['id']) }}" class="flex-shrink-0">
                                            @if(!empty($video['thumbnail_url']))
                                                <img src="{{ $video['thumbnail_url'] }}"
                                                     alt="{{ $video['title'] ?? 'Video' }}"
                                                     class="w-28 h-16 object-cover rounded border">
                                            @else
                                                <div class="w-28 h-16 bg-gray-200 rounded border flex items-center justify-center text-gray-500 text-xs">
                                                    No thumbnail
                                                </div>
                                            @endif
                                        </a>
                                        <div class="min-w-0">
                                            <a href="{{ route('videos.show', $video['id']) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600">
                                                {{ $video['title'] ?? ('Video #' . ($video['id'] ?? '')) }}
                                            </a>
                                            <div class="mt-1 text-xs text-gray-500">
                                                ID: <span class="font-mono">{{ $video['id'] ?? '—' }}</span>
                                                @if(!empty($video['category_for_ai']))
                                                    · AI category: <span class="font-medium">{{ $video['category_for_ai'] }}</span>
                                                @endif
                                                @if(!empty($video['post_type']))
                                                    · Type: <span class="font-medium">{{ $video['post_type'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 w-96">
                                    @if(!empty($video['audio_preview_url']))
                                        <audio controls preload="none" class="w-full">
                                            <source src="{{ $video['audio_preview_url'] }}" type="audio/mpeg">
                                            Your browser does not support the audio element.
                                        </audio>
                                        <div class="mt-2">
                                            <a href="{{ $video['audio_preview_url'] }}" target="_blank" rel="noopener"
                                               class="text-xs text-blue-600 hover:underline break-all">
                                                Open audio file
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500">No audio preview URL</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    @php
                                        $text = $video['audio_preview_source_text'] ?? '';
                                    @endphp
                                    @if(is_string($text) && trim($text) !== '')
                                        <div class="text-sm text-gray-900 whitespace-pre-wrap max-w-3xl">
                                            {{ $text }}
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-500">No audio script text</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if(($totalPages ?? 1) > 1)
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-700">
                        Page {{ $currentPage ?? 1 }} of {{ $totalPages ?? 1 }}
                    </div>
                    <div class="flex items-center gap-2">
                        @php
                            $q = request()->query();
                            $lim = (int) ($limit ?? 50);
                            $page = (int) ($currentPage ?? 1);
                            $tp = (int) ($totalPages ?? 1);
                        @endphp
                        @if($page > 1)
                            <a href="{{ route('videos.search-visible-audio', array_merge($q, ['offset' => 0])) }}"
                               class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm">First</a>
                            <a href="{{ route('videos.search-visible-audio', array_merge($q, ['offset' => max(0, ($page - 2) * $lim)])) }}"
                               class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Previous</a>
                        @endif
                        @if($page < $tp)
                            <a href="{{ route('videos.search-visible-audio', array_merge($q, ['offset' => $page * $lim])) }}"
                               class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">Next</a>
                            <a href="{{ route('videos.search-visible-audio', array_merge($q, ['offset' => max(0, ($tp - 1) * $lim)])) }}"
                               class="px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-50 text-sm">Last</a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <h3 class="text-sm font-medium text-gray-900">No search-visible videos with audio found</h3>
            <p class="mt-1 text-sm text-gray-500">Try removing filters, or confirm audio previews are generated.</p>
        </div>
    @endif
</div>
@endsection

