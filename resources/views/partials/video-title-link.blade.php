{{-- Expects $title, optional $videoId (pipeline Video.id for videos.show). --}}
@php
    $videoId = isset($videoId) && is_numeric($videoId) ? (int) $videoId : null;
    $label = $title ?? '(No title)';
@endphp
@if($videoId)
    <a href="{{ route('videos.show', $videoId) }}" class="font-medium text-blue-600 hover:text-blue-800 hover:underline">{{ e($label) }}</a>
@else
    <span class="font-medium text-gray-900">{{ e($label) }}</span>
@endif
