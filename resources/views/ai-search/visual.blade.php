@extends('layouts.app')

@section('content')
@php
    $prefill = $prefill ?? ['query' => ''];
    $searchFormAction = $searchFormAction ?? route('ai-search.visual.search');
    $feedbackUrl = $feedbackUrl ?? route('ai-search.playground.feedback');

    $formatClock = static function (?int $seconds): string {
        if ($seconds === null || $seconds < 0) {
            return '—';
        }
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        if ($h > 0) {
            return sprintf('%d:%02d:%02d', $h, $m, $s);
        }

        return sprintf('%d:%02d', $m, $s);
    };
@endphp

@push('head')
    <script src="https://cdn.jwplayer.com/libraries/RN3VdtUF.js"></script>
@endpush

<div class="space-y-6 max-w-6xl mx-auto">
    <div>
        @if(!empty($product['label']))
            <p class="text-sm text-blue-700 font-medium">{{ $product['label'] }}</p>
        @endif
        <h1 class="text-3xl font-heading font-bold text-gray-900">Visual search</h1>
        <p class="mt-2 text-gray-600">
            Browse search hits as thumbnails. Click a card to play; click a segment to jump to that timestamp.
            Defaults to <code class="bg-gray-100 px-1 rounded text-sm">v6_title_tags_exercises</code> so timelines are included.
        </p>
        <p class="mt-1 text-sm text-gray-500">
            Ops / pipeline trace:
            <a href="{{ route('ai-search.playground.index') }}" class="text-blue-600 hover:underline">Semantic search</a>
        </p>
        @if(!empty($namespaceLoadNote))
            <p class="mt-2 text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded px-3 py-2">{{ $namespaceLoadNote }}</p>
        @endif
    </div>

    @if(!empty($searchError))
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
            </div>

            <div>
                <span class="block text-sm font-medium text-gray-700 mb-1">Search mode</span>
                <div class="flex flex-wrap gap-4 text-sm">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            name="search_mode"
                            value="classic"
                            class="text-blue-600 focus:ring-blue-500"
                            @checked(($searchMode ?? 'literal') === 'classic')
                        >
                        <span>Classic</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            name="search_mode"
                            value="literal"
                            class="text-blue-600 focus:ring-blue-500"
                            @checked(($searchMode ?? 'literal') === 'literal')
                        >
                        <span>Literal</span>
                    </label>
                </div>
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
            class="space-y-4"
            @if(!empty($searchId))
                x-data="searchFeedbackPanel({
                    searchId: @js($searchId),
                    feedbackUrl: @js($feedbackUrl),
                    csrf: @js(csrf_token()),
                    source: 'dashboard',
                })"
                @vs-feedback="submit($event.detail.vote, $event.detail.wpPostId, $event.detail.rank, $event.detail.pineconeScore)"
            @endif
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold text-gray-900">
                    Results
                    <span class="text-base font-normal text-gray-500">({{ count($videos ?? []) }})</span>
                </h2>
                @if(!empty($searchId))
                    <p class="text-xs text-gray-500 font-mono">{{ Str::limit($searchId, 12, '…') }}</p>
                @endif
            </div>

            @if(count($videos ?? []) === 0)
                <p class="text-gray-600 text-sm bg-white rounded-lg shadow-sm p-6">No videos returned for this query.</p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    @foreach(($videos ?? []) as $idx => $row)
                        @php
                            $meta = isset($row['metadata']) && is_array($row['metadata']) ? $row['metadata'] : [];
                            $score = $row['score'] ?? $row['_score'] ?? $row['similarity'] ?? null;
                            $jwpId = (string) ($meta['jwp_id'] ?? $row['id'] ?? '');
                            $thumb = (string) ($meta['thumbnail'] ?? $meta['thumbnail_url'] ?? '');
                            if ($thumb === '' && $jwpId !== '') {
                                $thumb = 'https://cdn.jwplayer.com/v2/media/'.$jwpId.'/poster.jpg?width=720';
                            }
                            $seekTo = isset($meta['seek_to']) && is_numeric($meta['seek_to']) ? (int) $meta['seek_to'] : null;
                            $segments = isset($meta['segments']) && is_array($meta['segments']) ? $meta['segments'] : [];
                            $wpPostId = isset($meta['wp_post_id']) && is_numeric($meta['wp_post_id']) ? (int) $meta['wp_post_id'] : null;
                            $numericScore = ($score !== null && is_numeric($score)) ? (float) $score : null;
                            $cardKey = $jwpId !== '' ? $jwpId.'-'.$idx : ('idx-'.$idx);
                        @endphp
                        <article
                            class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100 flex flex-col"
                            x-data="visualSearchCard(@js($jwpId), @js($cardKey))"
                            :class="isActive ? 'ring-2 ring-blue-500' : ''"
                        >
                            <div class="relative aspect-video bg-gray-200">
                                <div
                                    x-show="isActive"
                                    x-cloak
                                    class="absolute inset-0 bg-black"
                                >
                                    <div
                                        class="w-full h-full"
                                        :id="'jw-visual-' + cardKey"
                                    ></div>
                                    <div
                                        x-show="useIframeFallback"
                                        class="absolute inset-0"
                                        x-cloak
                                    >
                                        <iframe
                                            class="w-full h-full border-0"
                                            :src="iframeSrc"
                                            allowfullscreen
                                            title="{{ $meta['title'] ?? 'Video' }}"
                                        ></iframe>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    x-show="!isActive"
                                    class="absolute inset-0 group w-full text-left"
                                    @click="play(@js($seekTo))"
                                    @disabled($jwpId === '')
                                >
                                    @if($thumb !== '')
                                        <img
                                            src="{{ $thumb }}"
                                            alt="{{ $meta['title'] ?? '' }}"
                                            class="w-full h-full object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-500 text-sm">
                                            No thumbnail
                                        </div>
                                    @endif
                                    <span
                                        class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/35 transition-colors"
                                    >
                                        <span class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white/90 text-gray-900 text-xl shadow">
                                            ▶
                                        </span>
                                    </span>
                                </button>
                            </div>

                            <div class="p-4 flex-1 flex flex-col gap-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="font-medium text-gray-900 leading-snug">
                                            {{ $meta['title'] ?? '(No title)' }}
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-1 flex flex-wrap gap-x-3 gap-y-0.5">
                                            <span>#{{ $idx + 1 }}</span>
                                            @if($score !== null)
                                                <span>Score {{ is_numeric($score) ? number_format((float) $score, 4, '.', '') : $score }}</span>
                                            @endif
                                            @if(($meta['run_time'] ?? '') !== '')
                                                <span>{{ $meta['run_time'] }}</span>
                                            @endif
                                            @if($jwpId !== '')
                                                <span class="font-mono">{{ $jwpId }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    @if(!empty($searchId))
                                        <div class="flex items-center gap-1 shrink-0"
                                             x-data="{ wpId: {{ $wpPostId ?? 'null' }}, rank: {{ $idx + 1 }}, score: @js($numericScore) }">
                                            <button type="button"
                                                @click="$dispatch('vs-feedback', { vote: 1, wpPostId: wpId, rank, pineconeScore: score })"
                                                class="px-2 py-1 rounded border text-sm border-gray-300 hover:bg-gray-50"
                                                title="Good match">👍</button>
                                            <button type="button"
                                                @click="$dispatch('vs-feedback', { vote: -1, wpPostId: wpId, rank, pineconeScore: score })"
                                                class="px-2 py-1 rounded border text-sm border-gray-300 hover:bg-gray-50"
                                                title="Poor match">👎</button>
                                        </div>
                                    @endif
                                </div>

                                @if($seekTo !== null)
                                    <button
                                        type="button"
                                        class="text-left text-xs font-medium text-emerald-800 bg-emerald-50 border border-emerald-100 rounded px-2 py-1.5 hover:bg-emerald-100"
                                        @click="play(@js($seekTo))"
                                        @disabled($jwpId === '')
                                    >
                                        Suggested seek: {{ $formatClock($seekTo) }}
                                        <span class="font-normal text-emerald-700">({{ $seekTo }}s)</span>
                                    </button>
                                @endif

                                @if(count($segments) > 0)
                                    <div class="mt-1 border border-gray-100 rounded-md overflow-hidden">
                                        <p class="text-xs font-medium text-gray-700 px-2 py-1.5 bg-gray-50 border-b border-gray-100">
                                            Exercise timeline ({{ count($segments) }})
                                        </p>
                                        <ul class="max-h-48 overflow-y-auto divide-y divide-gray-50 text-xs">
                                            @foreach($segments as $seg)
                                                @php
                                                    $start = isset($seg['start_seconds']) && is_numeric($seg['start_seconds'])
                                                        ? (int) $seg['start_seconds']
                                                        : null;
                                                    $end = isset($seg['end_seconds']) && is_numeric($seg['end_seconds'])
                                                        ? (int) $seg['end_seconds']
                                                        : null;
                                                    $segType = (string) ($seg['type'] ?? $seg['exercise_type'] ?? '');
                                                    $segName = (string) ($seg['name'] ?? '(unnamed)');
                                                    $isSeekMatch = $seekTo !== null && $start !== null && $start === $seekTo;
                                                @endphp
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="w-full text-left px-2 py-1.5 hover:bg-blue-50 flex gap-2 items-start {{ $isSeekMatch ? 'bg-emerald-50' : '' }}"
                                                        @click="play(@js($start))"
                                                        @disabled($jwpId === '' || $start === null)
                                                    >
                                                        <span class="font-mono text-gray-500 shrink-0 w-[4.5rem]">
                                                            {{ $formatClock($start) }}@if($end !== null)–{{ $formatClock($end) }}@endif
                                                        </span>
                                                        <span class="text-gray-800 min-w-0">
                                                            {{ $segName }}
                                                            @if($segType !== '')
                                                                <span class="text-gray-500">({{ $segType }})</span>
                                                            @endif
                                                        </span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @elseif(str_contains((string) ($selectedNamespace ?? ''), 'exercises'))
                                    <p class="text-xs text-gray-400">No spreadsheet segments for this video.</p>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>

@include('partials.search-feedback-alpine')

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('visualPlayer', {
        activeKey: null,
        playerElId: null,
        destroyActive() {
            if (this.playerElId && typeof window.jwplayer === 'function') {
                try {
                    const inst = window.jwplayer(this.playerElId);
                    if (inst && typeof inst.remove === 'function') {
                        inst.remove();
                    }
                } catch (e) { /* ignore */ }
            }
            this.playerElId = null;
            this.activeKey = null;
        },
    });

    Alpine.data('visualSearchCard', (jwpId, cardKey) => ({
        jwpId,
        cardKey,
        useIframeFallback: false,
        iframeSrc: '',
        get isActive() {
            return Alpine.store('visualPlayer').activeKey === this.cardKey;
        },
        play(startSeconds) {
            if (!this.jwpId) {
                return;
            }
            const start = (startSeconds === null || startSeconds === undefined || startSeconds === '')
                ? 0
                : Math.max(0, Number(startSeconds) || 0);
            const store = Alpine.store('visualPlayer');
            const already = store.activeKey === this.cardKey;

            if (!already) {
                store.destroyActive();
                this.useIframeFallback = false;
                this.iframeSrc = '';
                store.activeKey = this.cardKey;
            }

            this.$nextTick(() => this.mountOrSeek(start));
        },
        mountOrSeek(start) {
            const elId = 'jw-visual-' + this.cardKey;
            const mount = document.getElementById(elId);
            if (!mount) {
                return;
            }
            const store = Alpine.store('visualPlayer');

            if (typeof window.jwplayer !== 'function') {
                this.fallbackIframe();
                return;
            }

            try {
                if (store.playerElId === elId && !this.useIframeFallback) {
                    const inst = window.jwplayer(elId);
                    if (inst && typeof inst.seek === 'function') {
                        inst.seek(start);
                        if (typeof inst.play === 'function') {
                            inst.play(true);
                        }
                        return;
                    }
                }

                if (store.playerElId && store.playerElId !== elId) {
                    try {
                        const prev = window.jwplayer(store.playerElId);
                        if (prev && typeof prev.remove === 'function') {
                            prev.remove();
                        }
                    } catch (e) { /* ignore */ }
                }

                this.useIframeFallback = false;
                mount.innerHTML = '';
                store.playerElId = elId;
                store.activeKey = this.cardKey;

                const setup = {
                    playlist: 'https://cdn.jwplayer.com/v2/media/' + encodeURIComponent(this.jwpId),
                    width: '100%',
                    height: '100%',
                    autostart: true,
                };
                if (start > 0) {
                    setup.starttime = start;
                }
                const player = window.jwplayer(elId).setup(setup);
                player.on('ready', () => {
                    if (start > 0 && typeof player.seek === 'function') {
                        player.seek(start);
                    }
                });
                player.on('setupError', () => this.fallbackIframe());
                player.on('error', () => this.fallbackIframe());
            } catch (e) {
                this.fallbackIframe();
            }
        },
        fallbackIframe() {
            this.useIframeFallback = true;
            this.iframeSrc = 'https://content.jwplatform.com/players/'
                + encodeURIComponent(this.jwpId)
                + '.html?autostart=true';
            Alpine.store('visualPlayer').playerElId = null;
        },
    }));
});
</script>
@endpush
@endsection
