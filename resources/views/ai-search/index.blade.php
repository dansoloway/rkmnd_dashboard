@extends('layouts.app')

@section('content')
@php
    $prefill = $prefill ?? ['query' => ''];
    $searchFormAction = $searchFormAction ?? route('ai-search.playground.search');
    $feedbackUrl = $feedbackUrl ?? route('ai-search.playground.feedback');
@endphp

<div class="space-y-6 max-w-5xl mx-auto">
    <div>
        @if(!empty($product['label']))
            <p class="text-sm text-blue-700 font-medium">{{ $product['label'] }}</p>
        @endif
        <h1 class="text-3xl font-heading font-bold text-gray-900">Semantic search</h1>
        <p class="mt-2 text-gray-600">
            Same backend as the consumer app: <code class="bg-gray-100 px-1 rounded text-sm">POST /api/v1/search</code>
            using your tenant API key. Pick an embedding namespace to control which Pinecone vectors are queried.
        </p>
        @if(!empty($namespaceLoadNote))
            <p class="mt-2 text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded px-3 py-2">{{ $namespaceLoadNote }}</p>
        @endif
    </div>

    @if(isset($searchError))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ $searchError }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ $searchFormAction }}" class="space-y-4">
            @csrf

            <div>
                <label for="namespace" class="block text-sm font-medium text-gray-700 mb-1">Namespace</label>
                <select
                    id="namespace"
                    name="namespace"
                    class="w-full md:w-2/3 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                >
                    @foreach(($namespaces ?? []) as $nsVal)
                        <option value="{{ $nsVal }}" @selected(($selectedNamespace ?? $defaultNamespace) === $nsVal)>
                            {{ $nsVal }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    Backend default when omitted: <code class="bg-gray-100 px-1">{{ $defaultNamespace ?? 'v6_title_tags' }}</code>.
                    The form always sends the selected namespace.
                </p>
            </div>

            <div>
                <label for="query" class="sr-only">Search</label>
                <textarea
                    id="query"
                    name="query"
                    rows="2"
                    required
                    placeholder="What are you looking for?"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm"
                >{{ $prefill['query'] ?? '' }}</textarea>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-100 text-red-700 px-3 py-2 rounded text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium">
                    Search
                </button>
            </div>
        </form>
    </div>

    @if(($searchResponse !== null) || count($videos ?? []) > 0)
        <div
            class="bg-white rounded-lg shadow-sm p-6 space-y-3"
            @if(!empty($searchId))
                x-data="searchFeedbackPanel({
                    searchId: @js($searchId),
                    feedbackUrl: @js($feedbackUrl),
                    csrf: @js(csrf_token()),
                    source: 'dashboard',
                })"
            @endif
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold text-gray-900">Results</h2>
                @if(!empty($searchId))
                    <p class="text-xs text-gray-500">
                        Rate results to improve search quality.
                        <span class="font-mono text-gray-400" title="Search session ID">{{ Str::limit($searchId, 12, '…') }}</span>
                    </p>
                @endif
            </div>
            @if($searchResponse && ($searchResponse['status'] ?? '') === 'success' && ((($searchResponse['message'] ?? null) !== null) || (($searchResponse['no_recommendation_reason'] ?? null) !== null)))
                <div class="text-sm border-l-4 border-amber-400 pl-3 py-2 bg-amber-50 text-gray-800">
                    @if(($searchResponse['no_recommendation_reason'] ?? null) === 'off_topic')
                        <p><strong>No recommendations</strong> (off-topic / relevance gate / below score threshold).</p>
                    @endif
                    @if(!empty($searchResponse['message']))
                        <p class="mt-1 whitespace-pre-wrap">{{ $searchResponse['message'] }}</p>
                    @endif
                </div>
            @endif
            @if(count($videos ?? []) === 0)
                <p class="text-gray-600 text-sm">No rows in <code class="bg-gray-100 px-1">videos</code> for this response.</p>
                @if(!empty($searchId))
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <span class="text-sm text-gray-600">Was this search helpful?</span>
                        <button type="button" @click="submit(1, null, null, null)" :disabled="busy"
                            class="px-2 py-1 rounded border text-sm"
                            :class="voteFor(null) === 1 ? 'bg-green-100 border-green-400 text-green-800' : 'border-gray-300 hover:bg-gray-50'"
                            title="Thumbs up for this search">👍</button>
                        <button type="button" @click="submit(-1, null, null, null)" :disabled="busy"
                            class="px-2 py-1 rounded border text-sm"
                            :class="voteFor(null) === -1 ? 'bg-red-100 border-red-400 text-red-800' : 'border-gray-300 hover:bg-gray-50'"
                            title="Thumbs down for this search">👎</button>
                        <span x-show="error" x-text="error" class="text-xs text-red-600 ml-2"></span>
                    </div>
                @endif
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach(($videos ?? []) as $idx => $row)
                        @php
                            $meta = isset($row['metadata']) && is_array($row['metadata']) ? $row['metadata'] : [];
                            $score = $row['score'] ?? $row['_score'] ?? $row['similarity'] ?? null;
                            $wpPostId = isset($meta['wp_post_id']) && is_numeric($meta['wp_post_id']) ? (int) $meta['wp_post_id'] : null;
                            $numericScore = ($score !== null && is_numeric($score)) ? (float) $score : null;
                        @endphp
                        <li class="py-4 flex flex-col sm:flex-row sm:justify-between sm:items-start gap-3">
                            <div class="min-w-0 flex-1">
                            <div class="font-medium text-gray-900">{{ $meta['title'] ?? '(No title)' }}</div>
                            <div class="text-sm text-gray-600 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                                <span>WP post ID: {{ $meta['wp_post_id'] ?? '—' }}</span>
                                @if(($meta['slug'] ?? '') !== '')
                                    <span>Slug: {{ $meta['slug'] }}</span>
                                @endif
                                @if(($meta['instructor'] ?? '') !== '')
                                    <span>Instructor: {{ $meta['instructor'] }}</span>
                                @endif
                                @if($score !== null)
                                    <span>Score: {{ is_numeric($score) ? number_format((float) $score, 4, '.', '') : $score }}</span>
                                @endif
                                <span>#{{ $idx + 1 }}</span>
                            </div>
                            @if(($meta['run_time'] ?? '') !== '')
                                <p class="text-xs text-gray-500 mt-1">Runtime: {{ $meta['run_time'] }}</p>
                            @endif
                            @if(!empty($meta['audio_file']))
                                <p class="text-xs mt-2">
                                    Audio:
                                    @if(filter_var($meta['audio_file'], FILTER_VALIDATE_URL))
                                        <a href="{{ $meta['audio_file'] }}" class="text-blue-600 hover:underline" target="_blank" rel="noopener">open</a>
                                    @elseif(is_string($meta['audio_file']))
                                        <code>{{ $meta['audio_file'] }}</code>
                                    @endif
                                </p>
                            @endif
                            </div>
                            @if(!empty($searchId))
                                <div class="flex items-center gap-1 shrink-0">
                                    <span class="text-xs text-gray-500 mr-1 hidden sm:inline">Relevant?</span>
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
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if(($searchResponse ?? null) !== null)
                    <details class="mt-4 text-sm">
                        <summary class="cursor-pointer text-blue-700">Raw JSON response</summary>
                        <pre class="mt-2 text-xs bg-gray-50 p-3 rounded overflow-auto max-h-96">{{ json_encode($searchResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                @endif
            @endif
        </div>
    @endif
</div>

@include('partials.search-feedback-alpine')
@endsection
