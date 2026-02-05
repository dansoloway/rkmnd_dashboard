@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Video Database</h1>
            <p class="mt-2 text-gray-600">
                Total: {{ $total }} videos | 
                Valid Thumbnails: <span class="text-green-600 font-semibold">{{ $thumbnail_valid }}</span> | 
                Valid Audio: <span class="text-green-600 font-semibold">{{ $audio_valid }}</span>
            </p>
        </div>
        <a href="{{ route('videos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
            Back to Videos
        </a>
    </div>

    @if(isset($error))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ $error }}
        </div>
    @endif

    <!-- Videos Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WP ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AI Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image URL</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Audio URL</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($videos as $video)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $video['wp_post_id'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $video['title'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $video['video_category'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($video['thumbnail'])
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $video['thumbnail_exists'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $video['thumbnail_exists'] ? '✓ Valid' : '✗ Invalid' }}
                                        </span>
                                        <a href="{{ $video['thumbnail'] }}" target="_blank" class="text-blue-600 hover:text-blue-800 truncate max-w-xs" title="{{ $video['thumbnail'] }}">
                                            {{ Str::limit($video['thumbnail'], 50) }}
                                        </a>
                                    </div>
                                @else
                                    <span class="text-gray-400">No image</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($video['audio_file'])
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $video['audio_exists'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $video['audio_exists'] ? '✓ Valid' : '✗ Invalid' }}
                                        </span>
                                        <a href="{{ $video['audio_file'] }}" target="_blank" class="text-blue-600 hover:text-blue-800 truncate max-w-xs" title="{{ $video['audio_file'] }}">
                                            {{ Str::limit($video['audio_file'], 50) }}
                                        </a>
                                    </div>
                                @else
                                    <span class="text-gray-400">No audio</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No videos found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
