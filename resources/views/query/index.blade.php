@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-heading font-bold text-gray-900">AI Pipeline Database Query</h1>
            <p class="mt-2 text-gray-600">Query and explore videos in the AI Pipeline database</p>
            <div class="mt-2 flex gap-4 text-sm">
                <p class="text-gray-700">
                    <span class="font-semibold">Total in Database:</span> 
                    <strong class="text-blue-600">{{ number_format($totalInDatabase ?? 0) }}</strong> videos
                </p>
                @if(count($filters) > 2) {{-- More than just limit and offset --}}
                    <p class="text-gray-500">
                        <span class="font-medium">Filtered Results:</span> 
                        <strong>{{ number_format($total) }}</strong> videos
                    </p>
                @endif
            </div>
        </div>
    </div>

    @if(isset($error))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ $error }}
        </div>
    @endif

    <!-- Query Filters -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="GET" action="{{ route('query.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Title, instructor..." 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select 
                        id="category" 
                        name="category" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Categories</option>
                        @foreach($categories as $cat => $count)
                            <option value="{{ $cat }}" {{ ($filters['category'] ?? '') === $cat ? 'selected' : '' }}>
                                {{ $cat }} ({{ $count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Instructor -->
                <div>
                    <label for="instructor" class="block text-sm font-medium text-gray-700 mb-1">Instructor</label>
                    <select 
                        id="instructor" 
                        name="instructor" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Instructors</option>
                        @foreach($instructors as $inst => $count)
                            <option value="{{ $inst }}" {{ ($filters['instructor'] ?? '') === $inst ? 'selected' : '' }}>
                                {{ $inst }} ({{ $count }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Post Type -->
                <div>
                    <label for="post_type" class="block text-sm font-medium text-gray-700 mb-1">Post Type</label>
                    <select 
                        id="post_type" 
                        name="post_type" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Types</option>
                        <option value="video" {{ ($filters['post_type'] ?? '') === 'video' ? 'selected' : '' }}>Video</option>
                        <option value="scheduled" {{ ($filters['post_type'] ?? '') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Sync Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Sync Status</label>
                    <select 
                        id="status" 
                        name="status" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="completed" {{ ($filters['status'] ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ ($filters['status'] ?? '') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="error" {{ ($filters['status'] ?? '') === 'error' ? 'selected' : '' }}>Error</option>
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
                        <option value="wp_post_id" {{ ($filters['sort_by'] ?? 'wp_post_id') === 'wp_post_id' ? 'selected' : '' }}>WP Post ID</option>
                        <option value="title" {{ ($filters['sort_by'] ?? '') === 'title' ? 'selected' : '' }}>Title</option>
                        <option value="created_at" {{ ($filters['sort_by'] ?? '') === 'created_at' ? 'selected' : '' }}>Created At</option>
                        <option value="updated_at" {{ ($filters['sort_by'] ?? '') === 'updated_at' ? 'selected' : '' }}>Updated At</option>
                    </select>
                </div>

                <!-- Sort Order -->
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                    <select 
                        id="sort_order" 
                        name="sort_order" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="asc" {{ ($filters['sort_order'] ?? 'asc') === 'asc' ? 'selected' : '' }}>Ascending</option>
                        <option value="desc" {{ ($filters['sort_order'] ?? '') === 'desc' ? 'selected' : '' }}>Descending</option>
                    </select>
                </div>

                <!-- Limit -->
                <div>
                    <label for="limit" class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                    <select 
                        id="limit" 
                        name="limit" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="25" {{ ($filters['limit'] ?? 50) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ ($filters['limit'] ?? 50) == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ ($filters['limit'] ?? 50) == 100 ? 'selected' : '' }}>100</option>
                        <option value="200" {{ ($filters['limit'] ?? 50) == 200 ? 'selected' : '' }}>200</option>
                    </select>
                </div>

                <!-- Submit -->
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Query
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WP Post ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">JWP ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instructor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Has Embedding</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Has Audio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($videos as $video)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $video['id'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $video['wp_post_id'] ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <a href="{{ route('videos.show', $video['id']) }}" class="text-blue-600 hover:text-blue-800">
                                    {{ Str::limit($video['title'] ?? 'Untitled', 50) }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                {{ $video['jwp_id'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $video['instructor'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $video['video_category'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if(!empty($video['post_type']))
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $video['post_type'] === 'scheduled' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($video['post_type']) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if(!empty($video['sync_status']))
                                    @php
                                        $statusColors = [
                                            'completed' => 'bg-green-100 text-green-800',
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'processing' => 'bg-blue-100 text-blue-800',
                                            'error' => 'bg-red-100 text-red-800'
                                        ];
                                        $color = $statusColors[$video['sync_status']] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $color }}">
                                        {{ ucfirst($video['sync_status']) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                @if($video['has_embedding'] ?? false)
                                    <span class="text-green-600">✓</span>
                                @else
                                    <span class="text-gray-400">✗</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                @if($video['has_audio_preview'] ?? false)
                                    <span class="text-green-600">✓</span>
                                @else
                                    <span class="text-gray-400">✗</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if(!empty($video['updated_at']))
                                    {{ \Carbon\Carbon::parse($video['updated_at'])->format('M d, Y') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-4 text-center text-gray-500">
                                No videos found matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($totalPages > 1)
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        @if($currentPage > 1)
                            <a href="{{ route('query.index', array_merge($filters, ['offset' => max(0, ($currentPage - 2) * $perPage)])) }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Previous
                            </a>
                        @endif
                        @if($currentPage < $totalPages)
                            <a href="{{ route('query.index', array_merge($filters, ['offset' => $currentPage * $perPage])) }}" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Next
                            </a>
                        @endif
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ (($currentPage - 1) * $perPage) + 1 }}</span>
                                to <span class="font-medium">{{ min($currentPage * $perPage, $total) }}</span>
                                of <span class="font-medium">{{ $total }}</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                @if($currentPage > 1)
                                    <a href="{{ route('query.index', array_merge($filters, ['offset' => max(0, ($currentPage - 2) * $perPage)])) }}" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        Previous
                                    </a>
                                @endif
                                @for($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++)
                                    <a href="{{ route('query.index', array_merge($filters, ['offset' => ($i - 1) * $perPage])) }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 {{ $i === $currentPage ? 'bg-blue-50 border-blue-500 text-blue-600 z-10' : 'bg-white text-gray-700 hover:bg-gray-50' }} text-sm font-medium">
                                        {{ $i }}
                                    </a>
                                @endfor
                                @if($currentPage < $totalPages)
                                    <a href="{{ route('query.index', array_merge($filters, ['offset' => $currentPage * $perPage])) }}" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                        Next
                                    </a>
                                @endif
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
