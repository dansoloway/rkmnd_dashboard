@php
    $analyticsTab = $analyticsTab ?? 'overview';
    $tabLinks = [
        'overview' => 'Overview',
        'searches' => 'Search activity',
        'feedback' => 'Feedback',
    ];
    $preserveParams = array_filter(request()->only([
        'feedback_days', 'feedback_tab', 'feedback_namespace',
        'user_email', 'user',
    ]), fn ($v) => $v !== null && $v !== '');
@endphp

<nav class="sticky top-0 z-10 bg-gray-50 border-b border-gray-200 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8" aria-label="Analytics tabs">
    <div class="flex gap-1">
        @foreach($tabLinks as $key => $label)
            @php
                $params = array_merge($preserveParams, ['tab' => $key]);
            @endphp
            <a
                href="{{ route('analytics.index', $params) }}"
                class="px-4 py-3 text-sm font-medium border-b-2 -mb-px whitespace-nowrap {{ $analyticsTab === $key ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >{{ $label }}</a>
        @endforeach
    </div>
</nav>
