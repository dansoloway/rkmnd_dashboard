{{-- Expects $isoTimestamp (ISO 8601 string). Optional $showRelative (default true). --}}
@php
    $showRelative = $showRelative ?? true;
@endphp
@if(!empty($isoTimestamp))
    @php $dt = \Carbon\Carbon::parse($isoTimestamp)->timezone(config('app.timezone')); @endphp
    <span class="text-gray-700">{{ $dt->format('M j, Y g:i A') }}</span>
    @if($showRelative)
        <span class="block text-xs text-gray-400">{{ $dt->diffForHumans() }}</span>
    @endif
@else
    <span class="text-gray-400">—</span>
@endif
