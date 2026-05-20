{{-- Expects: $embeddings, $defaultSearchNamespace, $defaultEmbeddingIndex, $computedV6EmbeddingText, $computedV6EmbeddingFields --}}
@php
    $searchNs = $defaultSearchNamespace ?? config('backend.default_search_namespace', 'v6_title_tags');
    $hasEmbeddings = !empty($embeddings) && count($embeddings) > 0;
    $hasSearchRow = false;
    if ($hasEmbeddings) {
        foreach ($embeddings as $e) {
            if (($e['namespace'] ?? '') === $searchNs) {
                $hasSearchRow = true;
                break;
            }
        }
    }
@endphp

<div class="bg-white rounded-lg shadow-sm p-6" id="embedding-section">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
        <div>
            <h2 class="text-lg font-heading font-medium text-gray-900">Search indexing</h2>
            <p class="text-sm text-gray-500 mt-1">
                Public search uses namespace <span class="font-mono text-gray-700">{{ $searchNs }}</span>.
                Stored rows are from the pipeline DB (<code class="text-xs">video_embeddings</code>), not a live Pinecone listing.
            </p>
        </div>
        @if($hasEmbeddings)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Indexed in DB</span>
        @else
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">No stored rows</span>
        @endif
    </div>

    @if(!$hasSearchRow && $hasEmbeddings)
        <p class="text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-md px-3 py-2 mb-4">
            No <span class="font-mono">{{ $searchNs }}</span> row for this video—only other namespaces below.
            Eligible videos get that row after sync/processing on the AI pipeline server.
        </p>
    @endif

    @include('videos.partials.computed-v6-embedding-preview', [
        'openByDefault' => !$hasSearchRow,
    ])

    @if($hasEmbeddings)
        <div class="mt-4">
            <label for="embedding-picker" class="block text-sm font-medium text-gray-700 mb-1">Stored embedding</label>
            <select id="embedding-picker" name="embedding-picker"
                    class="w-full max-w-md text-sm border border-gray-300 rounded-md px-3 py-2 mb-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                @foreach($embeddings as $idx => $embedding)
                    <option value="{{ $idx }}" @if($idx === ($defaultEmbeddingIndex ?? 0)) selected @endif>
                        {{ $embedding['namespace'] ?? 'unknown' }}
                    </option>
                @endforeach
            </select>

            @foreach($embeddings as $idx => $embedding)
                <div class="embedding-detail-panel {{ $idx === ($defaultEmbeddingIndex ?? 0) ? '' : 'hidden' }}"
                     data-embedding-panel="{{ $idx }}">
                    @if(!empty($embedding['embedding_fields']))
                        <div class="mb-3 flex flex-wrap gap-1.5">
                            @php
                                $fields = $embedding['embedding_fields'];
                                if (is_string($fields)) {
                                    $decoded = json_decode($fields, true);
                                    $fields = is_array($decoded) ? $decoded : [$fields];
                                }
                            @endphp
                            @if(is_array($fields))
                                @foreach($fields as $field)
                                    <span class="inline-flex rounded-full bg-blue-50 text-blue-800 px-2 py-0.5 text-xs border border-blue-100">{{ is_scalar($field) ? $field : json_encode($field) }}</span>
                                @endforeach
                            @endif
                        </div>
                    @endif

                    @php
                        $storedTxt = trim((string) ($embedding['embedding_text'] ?? ''));
                        $computedTxt = trim((string) ($computedV6EmbeddingText ?? ''));
                        $showDrift = (($embedding['namespace'] ?? '') === $searchNs)
                            && $storedTxt !== ''
                            && $computedTxt !== ''
                            && $storedTxt !== $computedTxt;
                    @endphp
                    @if($showDrift)
                        <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded px-2 py-1.5 mb-2">
                            Stored text differs from the preview above—metadata may have changed since the last upsert.
                        </p>
                    @endif
                    @if(!empty($embedding['embedding_text']))
                        <pre class="whitespace-pre-wrap break-words text-xs text-gray-900 bg-gray-50 border border-gray-200 rounded p-3 max-h-80 overflow-auto font-mono">{{ $embedding['embedding_text'] }}</pre>
                    @else
                        <p class="text-sm text-gray-500 italic">No stored embedding text.</p>
                    @endif

                    <details class="mt-3 text-xs text-gray-500">
                        <summary class="cursor-pointer text-gray-600 hover:text-gray-900">Technical details</summary>
                        <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 font-mono">
                            <div><dt class="text-gray-400">Scheme</dt><dd class="text-gray-800">{{ $embedding['embedding_scheme'] ?? '—' }}</dd></div>
                            <div><dt class="text-gray-400">Pinecone ID</dt><dd class="text-gray-800 break-all">{{ $embedding['pinecone_id'] ?? '—' }}</dd></div>
                            <div><dt class="text-gray-400">Updated</dt><dd class="text-gray-800">{{ !empty($embedding['updated_at']) ? date('M j, Y g:i A', strtotime($embedding['updated_at'])) : '—' }}</dd></div>
                        </dl>
                    </details>
                </div>
            @endforeach
        </div>

        <script>
            (function () {
                var sel = document.getElementById('embedding-picker');
                if (!sel) return;
                function showPanel(index) {
                    document.querySelectorAll('#embedding-section [data-embedding-panel]').forEach(function (el) {
                        el.classList.toggle('hidden', String(el.getAttribute('data-embedding-panel')) !== String(index));
                    });
                }
                sel.addEventListener('change', function () { showPanel(sel.value); });
                showPanel(sel.value);
            })();
        </script>
    @endif
</div>
