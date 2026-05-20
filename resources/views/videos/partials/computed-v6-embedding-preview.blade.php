@php
    $computedV6Text = trim((string) ($computedV6EmbeddingText ?? ''));
    $computedV6Fields = $computedV6EmbeddingFields ?? [];
    if (! is_array($computedV6Fields)) {
        $computedV6Fields = [];
    }
    $defaultNs = $defaultSearchNamespace ?? config('backend.default_search_namespace', 'v6_title_tags');
    $openByDefault = $openByDefault ?? false;
@endphp
<details class="rounded-lg border border-gray-200 bg-gray-50" @if($openByDefault) open @endif>
    <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-gray-900 select-none">
        Preview embedding text for <span class="font-mono text-indigo-700">{{ $defaultNs }}</span>
        <span class="text-gray-500 font-normal">(from current DB fields)</span>
    </summary>
    <div class="px-4 pb-4 space-y-3 border-t border-gray-200">
        @if(! empty($computedV6Fields))
            <div class="flex flex-wrap gap-1.5">
                @foreach($computedV6Fields as $field)
                    <span class="inline-flex rounded-full bg-white border border-gray-200 text-gray-800 px-2 py-0.5 text-xs">{{ is_scalar($field) ? $field : json_encode($field) }}</span>
                @endforeach
            </div>
        @endif
        @if($computedV6Text !== '')
            <pre class="whitespace-pre-wrap break-words text-xs text-gray-900 bg-white border border-gray-200 rounded p-3 max-h-64 overflow-auto font-mono">{{ $computedV6Text }}</pre>
        @else
            <p class="text-xs text-gray-500 italic">Nothing to compose (e.g. missing title).</p>
        @endif
    </div>
</details>
