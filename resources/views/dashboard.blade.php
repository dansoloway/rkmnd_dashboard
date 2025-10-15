@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h1 class="text-3xl font-heading font-bold text-gray-900">
            Welcome, {{ Auth::user()->name }}! 👋
        </h1>
        <p class="mt-2 text-gray-600">
            {{ Auth::user()->tenant->display_name ?? 'Your Account' }} Dashboard
        </p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Videos Card -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Videos</p>
                    <p class="mt-2 text-3xl font-heading font-bold text-gray-900">
                        {{ $stats['total_videos'] ?? 0 }}
                    </p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-500">
                {{ $stats['completion_rate'] ?? 0 }}% fully processed
            </p>
        </div>

        <!-- Embeddings Card -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">AI Embeddings</p>
                    <p class="mt-2 text-3xl font-heading font-bold text-gray-900">
                        {{ $stats['videos_with_embeddings'] ?? 0 }}
                    </p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-500">
                Videos searchable by AI
            </p>
        </div>

        <!-- Audio Card -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Audio Previews</p>
                    <p class="mt-2 text-3xl font-heading font-bold text-gray-900">
                        {{ $stats['videos_with_audio_previews'] ?? 0 }}
                    </p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-500">
                @if(($stats['videos_with_audio_previews'] ?? 0) < ($stats['total_videos'] ?? 0))
                    <span class="text-yellow-600">⏳ Processing...</span>
                @else
                    <span class="text-green-600">✅ Complete</span>
                @endif
            </p>
        </div>
    </div>

    <!-- Recent Videos -->
    @if(!empty($recentVideos) && count($recentVideos) > 0)
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-heading font-bold text-gray-900">Recent Videos</h2>
            <a href="{{ route('videos.index') }}" class="text-sm text-blue-600 hover:text-blue-700">
                View All →
            </a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach(array_slice($recentVideos, 0, 6) as $video)
                <a href="{{ route('videos.show', $video['id']) }}" class="block border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition">
                    <h3 class="font-medium text-gray-900 line-clamp-2 mb-2">
                        {{ $video['title'] }}
                    </h3>
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span>{{ $video['instructor'] ?? 'Unknown' }}</span>
                        @if($video['has_audio_preview'] ?? false)
                            <span class="text-green-600">🎵</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-heading font-bold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('videos.index', ['search' => '']) }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition">
                <svg class="h-6 w-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <span class="font-medium text-gray-900">Search Videos</span>
            </a>

            <a href="{{ route('videos.index') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition">
                <svg class="h-6 w-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                </svg>
                <span class="font-medium text-gray-900">Browse Library</span>
            </a>

            <a href="{{ route('analytics.index') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition">
                <svg class="h-6 w-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="font-medium text-gray-900">View Analytics</span>
            </a>

            <a href="{{ route('account.index') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-blue-500 hover:shadow-md transition">
                <svg class="h-6 w-6 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="font-medium text-gray-900">Settings</span>
            </a>
        </div>
    </div>

</div>
@endsection

