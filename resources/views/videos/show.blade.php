@extends('layouts.app')

@section('content')
@php
    $libraryRoute = $libraryRoute ?? 'ai-search.videos.index';
    $libraryLabel = ($productId ?? 'ai_search') === 'mow_row' ? 'MOW/ROW catalog' : 'Video library';
@endphp
<div class="space-y-6">
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3 text-sm">
            <li><a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-blue-600">Home</a></li>
            <li>
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                    <a href="{{ route($libraryRoute) }}" class="ml-1 text-gray-700 hover:text-blue-600">{{ $libraryLabel }}</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                    <span class="ml-1 text-gray-500 truncate max-w-[12rem] md:max-w-md">{{ $video['title'] ?? 'Video' }}</span>
                </div>
            </li>
        </ol>
    </nav>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
    @endif

    @php
        $audioSourceText = '';
        if (!empty($audioPreviews[0]['source_text'])) {
            $audioSourceText = (string) $audioPreviews[0]['source_text'];
        } elseif (!empty($video['audio_preview_source_text'])) {
            $audioSourceText = (string) $video['audio_preview_source_text'];
        }
        $audioPlayUrl = $audioUrl;
        if ($audioPlayUrl && $audioSourceText !== '') {
            $audioCacheBuster = substr(md5($audioSourceText), 0, 12);
            $audioPlayUrl .= str_contains($audioPlayUrl, '?') ? '&' : '?';
            $audioPlayUrl .= 'v='.$audioCacheBuster;
        }
        $displayDuration = $video['run_time'] ?? $video['video_time'] ?? null;
        $displayDescription = $video['short_description'] ?? $video['long_description'] ?? null;
        $categoryLabel = $video['category_for_ai'] ?? $video['video_category'] ?? null;
        $audioStatus = !empty($audioPreviews[0]['generation_status']) ? $audioPreviews[0]['generation_status'] : (empty($audioPreviews) ? null : 'ready');
        $syncLabel = $video['sync_status'] ?? null;
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Hero: thumbnail + title + meta + audio (once) --}}
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                @if(!empty($video['thumbnail_url']))
                    <img src="{{ $video['thumbnail_url'] }}" alt="{{ $video['title'] }}" class="w-full max-h-80 object-cover" id="video-thumbnail-img">
                @else
                    <div class="w-full h-64 bg-gray-200 flex items-center justify-center" id="video-thumbnail-placeholder">
                        <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                @endif

                <div class="p-6 border-t border-gray-100">
                    <h1 class="text-2xl font-heading font-bold text-gray-900">{{ $video['title'] }}</h1>

                    <div class="flex flex-wrap items-center gap-2 mt-3 text-sm text-gray-600">
                        @if(!empty($video['instructor']))
                            <span>{{ $video['instructor'] }}</span>
                        @endif
                        @if($displayDuration)
                            <span class="text-gray-300">·</span>
                            <span>{{ $displayDuration }}</span>
                        @endif
                        @if($categoryLabel)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $categoryLabel }}</span>
                        @endif
                        @if(!empty($video['post_status']))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $video['post_status'] }}</span>
                        @endif
                        @if(!empty($video['scheduled_content_type']))
                            @php $sct = strtolower(trim((string) $video['scheduled_content_type'])); @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sct === 'move' ? 'bg-green-100 text-green-800' : ($sct === 'weekly' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-700') }}">
                                {{ $sct === 'move' ? 'Move of the Week' : ($sct === 'weekly' ? 'Rollout of the Week' : $video['scheduled_content_type']) }}
                            </span>
                        @endif
                        @if($syncLabel)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $syncLabel === 'synced' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">{{ $syncLabel }}</span>
                        @endif
                    </div>

                    @if($audioPlayUrl)
                        <div class="mt-5 pt-5 border-t border-gray-100">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <h2 class="text-sm font-medium text-gray-900">Audio preview</h2>
                                @if($audioStatus)
                                    <span class="text-xs text-gray-500">{{ $audioStatus }}</span>
                                @endif
                            </div>
                            <audio controls class="w-full" preload="none">
                                <source src="{{ $audioPlayUrl }}" type="audio/mpeg">
                            </audio>
                            <p class="mt-2 text-xs text-gray-500">
                                <a href="#edit-audio" class="text-blue-600 hover:underline">Edit script</a> in the sidebar to regenerate.
                                <a href="{{ $audioPlayUrl }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline ml-2">Open file</a>
                            </p>
                        </div>
                    @else
                        <p class="mt-4 text-sm text-gray-500">No audio preview yet. Add a script in the sidebar to generate one.</p>
                    @endif
                </div>
            </div>

            @if($displayDescription)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-heading font-medium text-gray-900 mb-2">Description</h2>
                    <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ $displayDescription }}</p>
                    @if(!empty($video['long_description']) && !empty($video['short_description']) && $video['long_description'] !== $video['short_description'])
                        <details class="mt-4">
                            <summary class="text-sm text-blue-600 cursor-pointer hover:underline">Full description</summary>
                            <p class="mt-2 text-gray-700 text-sm whitespace-pre-wrap">{{ $video['long_description'] }}</p>
                        </details>
                    @endif
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-heading font-medium text-gray-900 mb-4">Catalog &amp; IDs</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Pipeline ID</dt>
                        <dd class="mt-0.5 text-gray-900 font-mono">{{ $video['id'] ?? '—' }}</dd>
                    </div>
                    @if(!empty($video['wp_post_id']))
                        <div>
                            <dt class="text-gray-500">WordPress post</dt>
                            <dd class="mt-0.5 text-gray-900">{{ $video['wp_post_id'] }}</dd>
                        </div>
                    @endif
                    @if(!empty($video['jwp_id']))
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">JW Player ID</dt>
                            <dd class="mt-0.5 text-gray-900 font-mono text-xs break-all">{{ $video['jwp_id'] }}</dd>
                        </div>
                    @endif
                    @if(!empty($video['scheduled_content_type']))
                        <div>
                            <dt class="text-gray-500">Scheduled content type</dt>
                            <dd class="mt-0.5 text-gray-900">{{ $video['scheduled_content_type'] }}</dd>
                        </div>
                    @endif
                    @if(!empty($video['scheduled_acf']) && is_array($video['scheduled_acf']))
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">Scheduled ACF</dt>
                            <dd class="mt-0.5 text-gray-900 text-xs font-mono whitespace-pre-wrap">{{ json_encode($video['scheduled_acf'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</dd>
                        </div>
                    @endif
                    @if(!empty($video['body_area']))
                        <div><dt class="text-gray-500">Body area</dt><dd class="mt-0.5 text-gray-900">{{ $video['body_area'] }}</dd></div>
                    @endif
                    @if(!empty($video['helps_with']))
                        <div><dt class="text-gray-500">Helps with</dt><dd class="mt-0.5 text-gray-900">{{ $video['helps_with'] }}</dd></div>
                    @endif
                    @if(!empty($video['props']))
                        <div><dt class="text-gray-500">Props</dt><dd class="mt-0.5 text-gray-900">{{ $video['props'] }}</dd></div>
                    @endif
                    @if(!empty($video['video_topic']))
                        <div><dt class="text-gray-500">Topic</dt><dd class="mt-0.5 text-gray-900">{{ $video['video_topic'] }}</dd></div>
                    @endif
                    @if(!empty($video['content_tags']))
                        <div class="sm:col-span-2"><dt class="text-gray-500">Content tags</dt><dd class="mt-0.5 text-gray-900">{{ $video['content_tags'] }}</dd></div>
                    @endif
                    @if(!empty($video['created_at']))
                        <div><dt class="text-gray-500">Created</dt><dd class="mt-0.5 text-gray-900">{{ date('M j, Y', strtotime($video['created_at'])) }}</dd></div>
                    @endif
                    @if(!empty($video['updated_at']))
                        <div><dt class="text-gray-500">Updated</dt><dd class="mt-0.5 text-gray-900">{{ date('M j, Y', strtotime($video['updated_at'])) }}</dd></div>
                    @endif
                </dl>
            </div>

            @include('videos.partials.show-embedding-search')

            <details class="bg-white rounded-lg shadow-sm border border-gray-200">
                <summary class="cursor-pointer px-6 py-4 text-sm font-medium text-gray-600 hover:text-gray-900">
                    Advanced: raw video record (JSON)
                </summary>
                <div class="px-6 pb-4">
                    <pre class="text-xs overflow-auto max-h-96 font-mono bg-gray-50 p-4 rounded border border-gray-200">{{ json_encode($video, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </details>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <a href="{{ route($libraryRoute) }}" class="block w-full text-center btn-secondary text-sm">← Back to {{ $libraryLabel }}</a>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6" id="edit-audio">
                <h2 class="text-lg font-heading font-medium text-gray-900 mb-4">Audio script</h2>
                <form method="POST" action="{{ route('videos.update-audio-script', $video['id']) }}" class="space-y-3">
                    @csrf
                    <label for="source_text" class="block text-sm font-medium text-gray-700">Text-to-speech script</label>
                    <textarea
                        id="source_text"
                        name="source_text"
                        rows="10"
                        required
                        class="w-full text-sm text-gray-900 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Script read by ElevenLabs…"
                    >{{ old('source_text', $audioSourceText) }}</textarea>
                    <p class="text-xs text-gray-500">
                        Saves to the pipeline, regenerates the MP3 on S3, and refreshes search metadata when eligible.
                        <a href="{{ route('ai-search.search-visible-audio') }}" class="text-blue-600 hover:underline">Bulk audio tool</a>
                    </p>
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm font-medium">
                        Save and regenerate
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-heading font-medium text-gray-900 mb-4">Thumbnail</h2>
                <form method="POST" action="{{ route('videos.update-thumbnail', $video['id']) }}" class="space-y-3">
                    @csrf
                    <label for="thumbnail_url" class="block text-sm font-medium text-gray-700">Image URL</label>
                    <input
                        type="url"
                        name="thumbnail_url"
                        id="thumbnail_url"
                        value="{{ $video['thumbnail_url'] ?? '' }}"
                        placeholder="https://cdn.jwplayer.com/…"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                    >
                    <p class="text-xs text-gray-500">Leave empty to clear. WordPress sync may overwrite.</p>
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition text-sm">
                        Save thumbnail
                    </button>
                </form>
            </div>

            @if(!empty($relatedVideos) && count($relatedVideos) > 0)
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-heading font-medium text-gray-900 mb-4">Related videos</h2>
                    <div class="space-y-4">
                        @foreach($relatedVideos as $related)
                            <a href="{{ route('videos.show', $related['id']) }}" class="block group">
                                <div class="flex space-x-3">
                                    @if(!empty($related['thumbnail_url']))
                                        <img src="{{ $related['thumbnail_url'] }}" alt="{{ $related['title'] }}" class="w-20 h-14 object-cover rounded">
                                    @else
                                        <div class="w-20 h-14 bg-gray-200 rounded flex items-center justify-center shrink-0">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 group-hover:text-blue-600 line-clamp-2">{{ $related['title'] }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $related['instructor'] ?? 'Unknown' }}</p>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
