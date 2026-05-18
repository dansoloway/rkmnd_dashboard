{{-- Requires parent x-data="searchFeedbackPanel(...)" --}}
@php
    $videos = $videos ?? [];
    $searchId = $searchId ?? null;
@endphp
@if(empty($searchId))
    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded px-3 py-2">No search session id — feedback cannot be saved.</p>
@elseif(count($videos) === 0)
    <div class="flex flex-wrap items-center gap-2 py-2">
        <span class="text-sm text-gray-600">No video results for this search.</span>
        <span class="text-sm text-gray-600">Was this search helpful?</span>
        <button type="button" @click="submit(1, null, null, null)" :disabled="busy"
            class="px-2 py-1 rounded border text-sm"
            :class="voteFor(null) === 1 ? 'bg-green-100 border-green-400 text-green-800' : 'border-gray-300 hover:bg-gray-50'">👍</button>
        <button type="button" @click="submit(-1, null, null, null)" :disabled="busy"
            class="px-2 py-1 rounded border text-sm"
            :class="voteFor(null) === -1 ? 'bg-red-100 border-red-400 text-red-800' : 'border-gray-300 hover:bg-gray-50'">👎</button>
    </div>
@else
    <ul class="divide-y divide-gray-100 border border-gray-100 rounded-md">
        @foreach($videos as $idx => $row)
            @php
                $meta = isset($row['metadata']) && is_array($row['metadata']) ? $row['metadata'] : [];
                $score = $row['score'] ?? $row['_score'] ?? $row['similarity'] ?? null;
                $wpPostId = isset($meta['wp_post_id']) && is_numeric($meta['wp_post_id']) ? (int) $meta['wp_post_id'] : null;
                $numericScore = ($score !== null && is_numeric($score)) ? (float) $score : null;
            @endphp
            <li class="py-3 px-3 flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">
                <div class="min-w-0 flex-1 text-sm">
                    <span class="font-medium text-gray-900">{{ $meta['title'] ?? '(No title)' }}</span>
                    <span class="text-gray-500 ml-2">#{{ $idx + 1 }}</span>
                    @if($wpPostId)
                        <span class="text-gray-400 text-xs ml-1">WP {{ $wpPostId }}</span>
                    @endif
                    @if($numericScore !== null)
                        <span class="text-blue-600 font-mono text-xs ml-1">{{ number_format($numericScore, 4) }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button type="button"
                        @click="submit(1, {{ $wpPostId ?? 'null' }}, {{ $idx + 1 }}, @js($numericScore))"
                        :disabled="busy"
                        class="px-2 py-1 rounded border text-sm"
                        :class="voteFor({{ $wpPostId ?? 'null' }}) === 1 ? 'bg-green-100 border-green-400 text-green-800' : 'border-gray-300 hover:bg-gray-50'"
                        title="Good match">👍</button>
                    <button type="button"
                        @click="submit(-1, {{ $wpPostId ?? 'null' }}, {{ $idx + 1 }}, @js($numericScore))"
                        :disabled="busy"
                        class="px-2 py-1 rounded border text-sm"
                        :class="voteFor({{ $wpPostId ?? 'null' }}) === -1 ? 'bg-red-100 border-red-400 text-red-800' : 'border-gray-300 hover:bg-gray-50'"
                        title="Poor match">👎</button>
                </div>
            </li>
        @endforeach
    </ul>
@endif
<span x-show="error" x-text="error" class="text-xs text-red-600 block mt-2"></span>
