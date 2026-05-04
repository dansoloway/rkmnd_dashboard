@php
    $computedV6Text = trim((string) ($computedV6EmbeddingText ?? ''));
    $computedV6Fields = $computedV6EmbeddingFields ?? [];
    if (! is_array($computedV6Fields)) {
        $computedV6Fields = [];
    }
@endphp
<div class="mb-4 rounded-lg border border-indigo-100 bg-indigo-50/50 p-4">
    <h5 class="text-sm font-medium text-gray-900 mb-1">Computed public search input</h5>
    <p class="text-xs text-gray-600 mb-3 leading-relaxed">
        Text is composed from current pipeline DB fields the same way as the next <code class="bg-white/80 px-1 rounded">v6_title_tags</code> upsert.
        It matches what would be sent on sync—not necessarily what Pinecone has if metadata drifted since the last upsert.
    </p>
    @if(! empty($computedV6Fields))
        <div class="mb-3">
            <span class="text-gray-500 block mb-1.5 text-xs">Fields in this composition</span>
            <div class="flex flex-wrap gap-1.5">
                @foreach($computedV6Fields as $field)
                    <span class="inline-flex items-center rounded-full bg-blue-50 text-blue-900 px-2.5 py-0.5 text-xs font-medium border border-blue-100">{{ is_scalar($field) ? $field : json_encode($field) }}</span>
                @endforeach
            </div>
        </div>
    @endif
    @if($computedV6Text !== '')
        <pre class="whitespace-pre-wrap break-words text-xs text-gray-900 bg-white border border-gray-200 rounded p-3 max-h-96 overflow-auto font-mono">{{ $computedV6EmbeddingText }}</pre>
    @else
        <p class="text-xs text-gray-500 italic">Nothing to compose (e.g. missing title).</p>
    @endif
</div>
