@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Video Library</h1>
            <p class="mt-2 text-gray-600">{{ $total }} videos available</p>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="GET" action="{{ route('videos.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search videos..." 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <!-- Post Type Filter -->
                <div>
                    <label for="post_type" class="block text-sm font-medium text-gray-700 mb-1">Content Type</label>
                    <select 
                        id="post_type" 
                        name="post_type" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Types</option>
                        <option value="video" {{ ($filters['post_type'] ?? '') === 'video' ? 'selected' : '' }}>Videos</option>
                        <option value="scheduled" {{ ($filters['post_type'] ?? '') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    </select>
                </div>

                <!-- Category Filter -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select 
                        id="category" 
                        name="category" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $category => $count)
                            <option value="{{ $category }}" {{ ($filters['category'] ?? '') === $category ? 'selected' : '' }}>
                                {{ $category }} ({{ $count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Category for AI Filter -->
                <div>
                    <label for="category_for_ai" class="block text-sm font-medium text-gray-700 mb-1">Category for AI</label>
                    <select 
                        id="category_for_ai" 
                        name="category_for_ai" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All AI Categories</option>
                        @foreach($categories_for_ai ?? [] as $category => $count)
                            <option value="{{ $category }}" {{ ($filters['category_for_ai'] ?? '') === $category ? 'selected' : '' }}>
                                {{ $category }} ({{ $count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort By -->
                <div>
                    <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select 
                        id="sort_by" 
                        name="sort_by" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="created_at" {{ ($filters['sort_by'] ?? 'created_at') === 'created_at' ? 'selected' : '' }}>Date</option>
                        <option value="title" {{ ($filters['sort_by'] ?? '') === 'title' ? 'selected' : '' }}>Title</option>
                        <option value="duration" {{ ($filters['sort_by'] ?? '') === 'duration' ? 'selected' : '' }}>Duration</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-between items-center">
                <button 
                    type="submit" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-6 rounded-md shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Apply Filters
                </button>
                <a 
                    href="{{ route('videos.index') }}" 
                    class="text-sm text-gray-600 hover:text-gray-900 underline"
                >
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Error Message -->
    @if(isset($error))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-800">{{ $error }}</p>
        </div>
    @endif

    <!-- Video Grid -->
    @if(count($videos) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($videos as $video)
                <div class="video-card">
                    <!-- Thumbnail -->
                    <a href="{{ route('videos.show', $video['id']) }}">
                        @if(!empty($video['thumbnail_url']))
                            <img 
                                src="{{ $video['thumbnail_url'] }}" 
                                alt="{{ $video['title'] }}"
                                class="w-full h-48 object-cover"
                            >
                        @else
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </a>

                    <!-- Content -->
                    <div class="video-card-content">
                        <a href="{{ route('videos.show', $video['id']) }}" class="block">
                            <h3 class="font-medium text-gray-900 hover:text-blue-600 line-clamp-2">
                                {{ $video['title'] }}
                            </h3>
                        </a>

                        <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
                            <span>{{ $video['instructor'] ?? 'Unknown' }}</span>
                            @if(!empty($video['duration']))
                                <span>{{ $video['duration'] }}</span>
                            @endif
                        </div>

                        <div class="mt-2 flex flex-wrap gap-2">
                            @if(!empty($video['post_type']))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $video['post_type'] === 'scheduled' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($video['post_type']) }}
                                </span>
                            @endif
                            @if(!empty($video['category']))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $video['category'] }}
                                </span>
                            @endif
                        </div>

                        @if(!empty($video['audio_s3_key']))
                            <div class="mt-3">
                                <span class="inline-flex items-center text-xs text-green-600">
                                    <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"></path>
                                    </svg>
                                    Audio Preview
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($totalPages > 1)
            <div class="bg-white rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Page {{ $currentPage }} of {{ $totalPages }}
                    </div>
                    <div class="flex space-x-2">
                        @if($currentPage > 1)
                            <a 
                                href="{{ route('videos.index', array_merge(request()->query(), ['offset' => ($currentPage - 2) * $filters['limit']])) }}" 
                                class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Previous
                            </a>
                        @endif
                        @if($currentPage < $totalPages)
                            <a 
                                href="{{ route('videos.index', array_merge(request()->query(), ['offset' => $currentPage * $filters['limit']])) }}" 
                                class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Next
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @else
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No videos found</h3>
            <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or search query.</p>
        </div>
    @endif
</div>
@endsection


