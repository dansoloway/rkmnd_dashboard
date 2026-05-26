@extends('layouts.app')

@section('head')
@if(($analyticsTab ?? 'overview') === 'feedback')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endif
@endsection

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-heading font-bold text-gray-900">Analytics & Usage</h1>
        <p class="mt-2 text-gray-600">Monitor your usage and quota limits</p>
    </div>

    @if(isset($error))
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-800">{{ $error }}</p>
        </div>
    @endif

    @include('analytics.partials.tab-nav')

    @if(($analyticsTab ?? 'overview') === 'overview')
        @include('analytics.partials.tab-overview')
    @elseif(($analyticsTab ?? 'overview') === 'searches')
        @include('analytics.partials.tab-searches')
    @elseif(($analyticsTab ?? 'overview') === 'feedback')
        @include('analytics.partials.search-feedback-section')
    @endif
</div>

@include('partials.search-feedback-alpine')
@endsection
