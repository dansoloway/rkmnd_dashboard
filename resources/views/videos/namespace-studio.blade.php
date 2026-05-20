@extends('layouts.app')

@section('content')
@php
    $namespaceStudioBoot = [
        'reconcileUrl' => route('videos.namespace-studio.reconcile'),
        'fixUpsertUrl' => route('videos.namespace-studio.fix-upsert'),
        'fixDeleteUrl' => route('videos.namespace-studio.fix-delete'),
        'embeddingTextBase' => url('/videos'),
        'selectedNamespace' => $selectedNamespace,
        'viewMode' => $viewMode,
        'savedSnapshot' => $reconcileSnapshotForJs,
        'reconciledAtDisplay' => data_get($reconcileSnapshotForJs, 'reconciled_at_display'),
    ];
@endphp
<script type="application/json" id="namespace-studio-boot">@json($namespaceStudioBoot)</script>
<div class="py-0 -mt-2"
     x-data="namespaceStudioFromBoot()"
     x-init="init()">

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-heading font-bold text-gray-900">Namespace studio</h1>
            <p class="mt-1 text-sm text-gray-600">
                Pick an embedding scheme namespace. Overview and catalog are scoped to that namespace only.
                Run reconcile for Pinecone vs DB counts.
            </p>
        </div>
        <a href="{{ route('videos.index') }}" class="text-sm text-blue-600 hover:text-blue-800">← Video library</a>
    </div>

    @if(!empty($namespaceNote))
        <p class="mb-4 text-sm text-amber-800 bg-amber-50 border border-amber-100 rounded px-3 py-2">{{ $namespaceNote }}</p>
    @endif

    <form method="get" action="{{ route('videos.namespace-studio') }}" class="mb-6 flex flex-wrap gap-4 items-end">
        <div>
            <label for="namespace" class="block text-sm font-medium text-gray-700 mb-1">Namespace</label>
            <select name="namespace" id="namespace"
                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    onchange="this.form.submit()">
                <option value="" @selected($selectedNamespace === '')>— Select namespace —</option>
                @foreach($namespaces as $ns)
                    <option value="{{ $ns }}" @selected($selectedNamespace === $ns)>{{ $ns }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="view" class="block text-sm font-medium text-gray-700 mb-1">View</label>
            <select name="view" id="view"
                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="all" @selected($viewMode === 'all')>Videos in this namespace</option>
                <option value="issues" @selected($viewMode === 'issues')>Reconcile gaps only</option>
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search title</label>
            <input type="text" name="search" id="search" value="{{ $search }}"
                   placeholder="Filter…"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
            Apply
        </button>
    </form>

    @if($hasNamespace)
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Namespace overview</h2>
        <p class="text-sm text-gray-700 mb-1">{{ $namespaceDefinition }}</p>
        <p class="text-sm text-gray-500 mb-4 font-mono">{{ $selectedNamespace }}</p>

        <div class="text-sm text-gray-600 mb-4">
            <strong>Data source:</strong> <code class="bg-gray-100 px-1 rounded text-xs">GET /api/v1/wordpress/videos?embedding_namespace={{ $selectedNamespace }}</code>
            and optional reconcile for Pinecone.
        </div>

        <p class="text-sm text-gray-800 mb-4 rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
            <strong>Last saved reconcile:</strong>
            <span x-text="reconciledAtDisplay || '— (none yet for this namespace)'"></span>
            <span class="text-gray-500"> — Per tenant and namespace. Change namespace above for other snapshots.</span>
        </p>

        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="border border-gray-100 rounded-lg p-4">
                <dt class="text-xs font-medium text-gray-500 uppercase">Videos in namespace</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                    @if($namespaceCountError)
                        <span class="text-red-600 text-base" title="{{ $namespaceCountError }}">Error</span>
                    @elseif($namespaceCatalogCount !== null)
                        {{ number_format($namespaceCatalogCount) }}
                    @else
                        <span class="text-gray-400 text-base">—</span>
                    @endif
                </dd>
                <p class="mt-1 text-xs text-gray-500">Catalog cohort for <code class="bg-gray-100 px-0.5">{{ $selectedNamespace }}</code> (pipeline list total).</p>
            </div>
            <div class="border border-gray-100 rounded-lg p-4">
                <dt class="text-xs font-medium text-gray-500 uppercase">In Pinecone &amp; DB</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                    @if($reconcileSummary)
                        {{ number_format((int) ($reconcileSummary['in_both_count'] ?? 0)) }}
                    @else
                        <span x-show="reconcileSummary === null && !reconcileLoading" class="text-gray-400 font-normal text-base">Run reconcile</span>
                        <span x-show="reconcileSummary !== null" x-text="reconcileSummary ? Number(reconcileSummary.in_both_count || 0).toLocaleString() : ''"></span>
                        <span x-show="reconcileLoading" class="text-gray-500 text-base">Loading…</span>
                    @endif
                </dd>
                <p class="mt-1 text-xs text-gray-500">Reconcile <code class="bg-gray-100 px-0.5">in_both_count</code> for this namespace.</p>
            </div>
            <div class="border border-gray-100 rounded-lg p-4">
                <dt class="text-xs font-medium text-gray-500 uppercase">Pinecone vectors</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">
                    @if($reconcileSummary)
                        {{ number_format((int) ($reconcileSummary['pinecone_vector_count'] ?? 0)) }}
                    @else
                        <span x-show="reconcileSummary === null && !reconcileLoading" class="text-gray-400 font-normal text-base">Run reconcile</span>
                        <span x-show="reconcileSummary !== null" x-text="reconcileSummary ? Number(reconcileSummary.pinecone_vector_count || 0).toLocaleString() : ''"></span>
                        <span x-show="reconcileLoading" class="text-gray-500 text-base">Loading…</span>
                    @endif
                </dd>
                <p class="mt-1 text-xs text-gray-500">Reconcile <code class="bg-gray-100 px-0.5">pinecone_vector_count</code> in this namespace.</p>
            </div>
        </dl>

        @if($reconcileSummary)
            <div class="mb-4 flex flex-wrap gap-2 text-sm">
                <span class="text-gray-600 mr-1">View gap rows:</span>
                <button type="button"
                        x-on:click="issuesBucketFilter = 'all'; document.getElementById('reconcile-gaps')?.scrollIntoView({ behavior: 'smooth' })"
                        class="text-blue-600 hover:text-blue-800 underline">
                    All (<span x-text="gapBucketCounts().all"></span>)
                </button>
                <button type="button"
                        x-on:click="issuesBucketFilter = 'missing_from_pinecone'; document.getElementById('reconcile-gaps')?.scrollIntoView({ behavior: 'smooth' })"
                        class="text-amber-800 hover:text-amber-900 underline">
                    Missing (<span x-text="gapBucketCounts().missing"></span>)
                </button>
                <button type="button"
                        x-on:click="issuesBucketFilter = 'pinecone_not_in_db'; document.getElementById('reconcile-gaps')?.scrollIntoView({ behavior: 'smooth' })"
                        class="text-red-800 hover:text-red-900 underline">
                    Orphans (<span x-text="gapBucketCounts().orphans"></span>)
                </button>
                <button type="button"
                        x-on:click="issuesBucketFilter = 'unexpected_in_index'; document.getElementById('reconcile-gaps')?.scrollIntoView({ behavior: 'smooth' })"
                        class="text-purple-800 hover:text-purple-900 underline">
                    Unexpected (<span x-text="gapBucketCounts().unexpected"></span>)
                </button>
            </div>
        @endif

        <div class="border-t border-gray-100 pt-4">
            <h3 class="text-sm font-medium text-gray-900 mb-2">Location badges (per video)</h3>
            <ul class="text-xs text-gray-700 space-y-1 list-disc list-inside">
                <li><span class="font-medium">WP</span> — Has WordPress post id in pipeline.</li>
                <li><span class="font-medium">DB</span> — Row in pipeline <code class="bg-gray-100 px-0.5">videos</code> table.</li>
                <li><span class="font-medium">Idx</span> — <code class="bg-gray-100 px-0.5">video_embeddings</code> row for <code class="bg-gray-100 px-0.5">{{ $selectedNamespace }}</code>.</li>
            </ul>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <button type="button"
                    x-on:click="runReconcile()"
                    x-bind:disabled="reconcileLoading || !selectedNamespace"
                    class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">
                <span x-show="!reconcileLoading">Run reconcile</span>
                <span x-show="reconcileLoading">Reconciling… (may take minutes)</span>
            </button>
            <span x-show="reconcileError" class="text-sm text-red-600" x-text="reconcileError"></span>
            <span x-show="reconcileOk" class="text-sm text-green-700">Reconcile finished.</span>
        </div>
    </div>
    @endif

    @if($hasNamespace)
        @include('videos.partials.namespace-studio-gaps')
    @endif

    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
        <div class="flex min-h-full items-end sm:items-center justify-center p-4 bg-black/40" x-on:click.self="modalOpen = false">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full p-6" x-on:click.stop>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Embedding input text</h3>
                <p class="text-sm text-gray-500 mb-2" x-text="modalTitle"></p>
                <div x-show="modalLoading" class="text-gray-600 text-sm">Loading…</div>
                <pre x-show="!modalLoading && modalText" class="text-xs whitespace-pre-wrap bg-gray-50 border rounded p-3 max-h-96 overflow-y-auto" x-text="modalText"></pre>
                <p x-show="!modalLoading && !modalText" class="text-sm text-gray-500">No text returned for this namespace.</p>
                <p x-show="modalError" class="text-sm text-red-600 mt-2" x-text="modalError"></p>
                <button type="button" x-on:click="modalOpen = false" class="mt-4 px-4 py-2 text-sm bg-gray-200 rounded-md hover:bg-gray-300">Close</button>
            </div>
        </div>
    </div>

    @if($viewMode === 'all')
        @if(!$hasNamespace)
            <div class="bg-white shadow rounded-lg p-8 text-center text-sm text-gray-600">
                Select a namespace above to list videos indexed for that embedding scheme.
            </div>
        @elseif($listError)
            <div class="mb-4 p-4 bg-red-50 text-red-800 rounded-lg text-sm">Failed to load videos: {{ $listError }}</div>
        @endif

        @if($hasNamespace)
        <div class="hidden md:block bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Badges</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Video</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thumb</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Audio</th>
                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Embedding text</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                @foreach($rows as $r)
                    <tr class="hover:bg-gray-50 cursor-pointer"
                        onclick="window.location='{{ route('videos.show', $r['id']) }}'">
                        <td class="px-3 py-2 whitespace-nowrap text-sm">
                            @include('videos.partials.namespace-studio-badges', ['r' => $r])
                        </td>
                        <td class="px-3 py-2 text-sm text-gray-900">
                            <span class="font-medium">{{ $r['title'] ?: '—' }}</span>
                            <span class="block text-xs text-gray-500">ID {{ $r['id'] }}</span>
                        </td>
                        <td class="px-3 py-2">
                            @if(!empty($r['thumbnail_url']))
                                <img src="{{ $r['thumbnail_url'] }}" alt="" class="h-14 w-24 object-cover rounded border border-gray-200">
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2" onclick="event.stopPropagation()">
                            @if(!empty($r['audio_preview_url']))
                                <audio controls preload="none" class="h-8 w-48 max-w-full">
                                    <source src="{{ $r['audio_preview_url'] }}" type="audio/mpeg">
                                </audio>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-sm" onclick="event.stopPropagation()">
                            <button type="button"
                                    class="text-blue-600 hover:text-blue-800 text-sm"
                                    x-on:click.stop="openEmbeddingModal({{ $r['id'] }})">
                                View full text
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-4">
            @foreach($rows as $r)
                <div class="bg-white rounded-lg shadow p-4 border border-gray-100 cursor-pointer"
                     onclick="window.location='{{ route('videos.show', $r['id']) }}'">
                    <div class="flex gap-3">
                        @if(!empty($r['thumbnail_url']))
                            <img src="{{ $r['thumbnail_url'] }}" alt="" class="h-20 w-28 object-cover rounded shrink-0">
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="mb-2">@include('videos.partials.namespace-studio-badges', ['r' => $r])</div>
                            <div class="font-medium text-gray-900">{{ $r['title'] ?: '—' }}</div>
                            <div class="text-xs text-gray-500">ID {{ $r['id'] }}</div>
                        </div>
                    </div>
                    @if(!empty($r['audio_preview_url']))
                        <div class="mt-3" onclick="event.stopPropagation()">
                            <audio controls preload="none" class="w-full h-9">
                                <source src="{{ $r['audio_preview_url'] }}" type="audio/mpeg">
                            </audio>
                        </div>
                    @endif
                    <div class="mt-2" onclick="event.stopPropagation()">
                        <button type="button" class="text-blue-600 text-sm"
                                x-on:click.stop="openEmbeddingModal({{ $r['id'] }})">View embedding text</button>
                    </div>
                </div>
            @endforeach
        </div>

        @if($totalPages > 1)
            <nav class="mt-6 flex justify-center gap-2 flex-wrap">
                @for($p = 1; $p <= $totalPages; $p++)
                    @if($p === $page)
                        <span class="px-3 py-1 bg-blue-600 text-white rounded text-sm">{{ $p }}</span>
                    @else
                        <a class="px-3 py-1 bg-gray-100 text-gray-800 rounded text-sm hover:bg-gray-200"
                           href="{{ route('videos.namespace-studio', ['namespace' => $selectedNamespace, 'view' => 'all', 'search' => $search, 'page' => $p]) }}">{{ $p }}</a>
                    @endif
                @endfor
            </nav>
        @endif
        @endif
    @else
        @if(!$hasNamespace)
            <div class="bg-white shadow rounded-lg p-8 text-center text-sm text-gray-600">
                Select a namespace above to view reconcile gaps for that embedding scheme.
            </div>
        @else
            <p class="text-sm text-gray-600 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 mb-2">
                Reconcile gaps only (catalog table hidden).
                <a href="{{ route('videos.namespace-studio', ['namespace' => $selectedNamespace, 'view' => 'all', 'search' => $search]) }}"
                   class="text-blue-600 hover:underline">Show videos in this namespace</a>
            </p>
        @endif
    @endif
</div>

@push('head')
<style>[x-cloak]{display:none!important;}</style>
@endpush

@push('scripts')
<script>
function namespaceStudioFromBoot() {
    const el = document.getElementById('namespace-studio-boot');
    const cfg = el ? JSON.parse(el.textContent) : {};
    return namespaceStudio(cfg);
}

function namespaceStudio(cfg) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    return {
        reconcileUrl: cfg.reconcileUrl,
        fixUpsertUrl: cfg.fixUpsertUrl,
        fixDeleteUrl: cfg.fixDeleteUrl,
        embeddingTextBase: cfg.embeddingTextBase,
        selectedNamespace: cfg.selectedNamespace,
        viewMode: cfg.viewMode,
        savedSnapshot: cfg.savedSnapshot || null,
        reconciledAtDisplay: cfg.reconciledAtDisplay || null,
        reconcileLoading: false,
        reconcileOk: false,
        reconcileError: null,
        reconcilePayload: null,
        reconcileSummary: null,
        issuesRows: [],
        issuesBucketFilter: 'all',
        modalOpen: false,
        modalLoading: false,
        modalText: '',
        modalTitle: '',
        modalError: null,
        fixBusy: false,
        fixMessage: null,
        fixMessageOk: null,

        init() {
            this.hydrateFromSavedSnapshot();
            if (this.viewMode === 'issues') {
                this.$nextTick(() => {
                    const el = document.getElementById('reconcile-gaps');
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            }
        },

        filteredIssuesRows() {
            if (this.issuesBucketFilter === 'all') {
                return this.issuesRows;
            }
            return this.issuesRows.filter((r) => r.bucket === this.issuesBucketFilter);
        },

        gapBucketCounts() {
            const s = this.reconcileSummary || {};
            const missing = Number(s.missing_from_pinecone_count || 0);
            const orphans = Number(s.pinecone_not_in_db_count || 0);
            const unexpected = Number(s.pinecone_in_db_but_not_expected_count || 0);
            return {
                all: missing + orphans + unexpected,
                missing,
                orphans,
                unexpected,
            };
        },

        gapListTruncated() {
            const s = this.reconcileSummary;
            if (!s) {
                return false;
            }
            return !!(s.missing_from_pinecone_truncated || s.pinecone_not_in_db_truncated || s.pinecone_unexpected_truncated);
        },

        hydrateFromSavedSnapshot() {
            if (!this.savedSnapshot || !this.savedSnapshot.payload) {
                return;
            }
            this.reconcilePayload = this.savedSnapshot.payload;
            this.reconcileSummary = this.savedSnapshot.payload.summary || null;
            this.reconcileOk = true;
            if (this.savedSnapshot.reconciled_at_display) {
                this.reconciledAtDisplay = this.savedSnapshot.reconciled_at_display;
            }
            this.buildIssuesRows(this.savedSnapshot.payload);
        },

        async runReconcile() {
            this.reconcileLoading = true;
            this.reconcileError = null;
            this.reconcileOk = false;
            this.reconcilePayload = null;
            this.reconcileSummary = null;
            this.issuesRows = [];

            try {
                const res = await fetch(this.reconcileUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        namespace: this.selectedNamespace,
                        list_limit: 100,
                        max_missing: 5000,
                        max_orphans: 5000,
                        max_unexpected: 5000,
                    }),
                });
                const data = await res.json();
                if (!data.ok) {
                    this.reconcileError = data.message || 'Reconcile failed';
                    return;
                }
                this.reconcilePayload = data.payload;
                this.reconcileSummary = data.payload.summary || null;
                this.reconcileOk = true;
                if (data.reconciled_at_display) {
                    this.reconciledAtDisplay = data.reconciled_at_display;
                }
                this.savedSnapshot = {
                    payload: data.payload,
                    reconciled_at: data.reconciled_at,
                    reconciled_at_display: data.reconciled_at_display,
                };
                this.buildIssuesRows(data.payload);
            } catch (e) {
                this.reconcileError = e.message || String(e);
            } finally {
                this.reconcileLoading = false;
            }
        },

        buildIssuesRows(payload) {
            const dbUrl = @json(route('videos.database'));
            const videoBase = @json(url('/videos'));
            const rows = [];
            let k = 0;
            const add = (bucket, issueLabel, r) => {
                k++;
                const jwp_id = r.jwp_id || '';
                const title = r.title || '';
                const videoId = r.video_id || null;
                let openUrl = dbUrl + '?search=' + encodeURIComponent(title || jwp_id || '');
                let openLabel = 'Search in Metadata';
                if (videoId) {
                    openUrl = videoBase + '/' + videoId;
                    openLabel = 'Open video';
                }
                rows.push({
                    key: bucket + '-' + k + '-' + jwp_id,
                    bucket,
                    issueLabel,
                    jwp_id,
                    video_id: videoId,
                    wp_post_id: r.wp_post_id || null,
                    title,
                    category_for_ai: r.category_for_ai || '',
                    post_status: r.post_status || '',
                    post_type: r.post_type || '',
                    reason: r.reason || '',
                    openUrl,
                    openLabel,
                    fixStatus: null,
                });
            };

            (payload.missing_from_pinecone || []).forEach(r => {
                add('missing_from_pinecone', 'Missing from Pinecone', r);
            });
            (payload.pinecone_not_in_db || []).forEach(r => {
                add('pinecone_not_in_db', 'Pinecone not in DB', r);
            });
            (payload.pinecone_unexpected || []).forEach(r => {
                add('unexpected_in_index', 'Unexpected in index', r);
            });
            this.issuesRows = rows;
        },

        async openEmbeddingModal(videoId) {
            this.modalOpen = true;
            this.modalLoading = true;
            this.modalText = '';
            this.modalTitle = 'Video #' + videoId;
            this.modalError = null;
            const url = this.embeddingTextBase + '/' + videoId + '/embedding-text?namespace=' + encodeURIComponent(this.selectedNamespace);
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (!data.ok) {
                    this.modalError = data.message || 'Failed to load';
                    return;
                }
                if (data.title) {
                    this.modalTitle = data.title;
                }
                this.modalText = data.text || '';
            } catch (e) {
                this.modalError = e.message || String(e);
            } finally {
                this.modalLoading = false;
            }
        },

        async fixUpsert(row) {
            if (!row || this.fixBusy) {
                return;
            }
            this.fixBusy = true;
            this.fixMessage = null;
            row.fixStatus = 'working';
            const body = { namespace: this.selectedNamespace };
            if (row.video_id) {
                body.video_ids = [row.video_id];
            } else if (row.jwp_id) {
                body.jwp_ids = [row.jwp_id];
            } else {
                row.fixStatus = 'error';
                this.fixMessage = 'No jwp_id or video_id';
                this.fixBusy = false;
                return;
            }
            try {
                const res = await fetch(this.fixUpsertUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (!data.ok) {
                    throw new Error(data.message || 'Upsert failed');
                }
                const item = (data.result?.results || [])[0];
                if (item && !item.ok) {
                    throw new Error(item.error || 'Upsert failed');
                }
                row.fixStatus = 'ok';
                this.fixMessage = 'Upserted ' + (row.jwp_id || row.video_id);
                this.fixMessageOk = true;
                await this.runReconcile();
            } catch (e) {
                row.fixStatus = 'error';
                this.fixMessage = e.message || String(e);
                this.fixMessageOk = false;
            } finally {
                this.fixBusy = false;
            }
        },

        async fixDelete(row) {
            if (!row || !row.jwp_id || this.fixBusy) {
                return;
            }
            if (!confirm('Remove vector ' + row.jwp_id + ' from Pinecone namespace ' + this.selectedNamespace + '?')) {
                return;
            }
            this.fixBusy = true;
            this.fixMessage = null;
            row.fixStatus = 'working';
            try {
                const res = await fetch(this.fixDeleteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        namespace: this.selectedNamespace,
                        jwp_ids: [row.jwp_id],
                    }),
                });
                const data = await res.json();
                if (!data.ok) {
                    throw new Error(data.message || 'Delete failed');
                }
                row.fixStatus = 'ok';
                this.fixMessage = 'Deleted ' + row.jwp_id;
                this.fixMessageOk = true;
                await this.runReconcile();
            } catch (e) {
                row.fixStatus = 'error';
                this.fixMessage = e.message || String(e);
                this.fixMessageOk = false;
            } finally {
                this.fixBusy = false;
            }
        },

        async fixBulkUpsert() {
            const rows = this.filteredIssuesRows().filter((r) => r.bucket === 'missing_from_pinecone');
            if (rows.length === 0) {
                return;
            }
            if (!confirm('Upsert ' + rows.length + ' missing video(s) into Pinecone namespace ' + this.selectedNamespace + '?')) {
                return;
            }
            this.fixBusy = true;
            this.fixMessage = null;
            const videoIds = rows.map((r) => r.video_id).filter((id) => id).slice(0, 50);
            const jwpIds = rows.filter((r) => !r.video_id).map((r) => r.jwp_id).filter((j) => j).slice(0, 50);
            const body = { namespace: this.selectedNamespace };
            if (videoIds.length) {
                body.video_ids = videoIds;
            }
            if (jwpIds.length) {
                body.jwp_ids = jwpIds;
            }
            try {
                const res = await fetch(this.fixUpsertUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify(body),
                });
                const data = await res.json();
                if (!data.ok) {
                    throw new Error(data.message || 'Bulk upsert failed');
                }
                const ok = data.result?.ok_count ?? 0;
                const failed = data.result?.failed_count ?? 0;
                this.fixMessage = 'Bulk upsert: ' + ok + ' ok, ' + failed + ' failed';
                this.fixMessageOk = failed === 0;
                await this.runReconcile();
            } catch (e) {
                this.fixMessage = e.message || String(e);
                this.fixMessageOk = false;
            } finally {
                this.fixBusy = false;
            }
        },

        async fixBulkDelete() {
            const bucket = this.issuesBucketFilter;
            if (bucket !== 'pinecone_not_in_db' && bucket !== 'unexpected_in_index') {
                return;
            }
            const rows = this.filteredIssuesRows().filter((r) => r.jwp_id);
            if (rows.length === 0) {
                return;
            }
            if (!confirm('Delete ' + rows.length + ' vector(s) from Pinecone namespace ' + this.selectedNamespace + '?')) {
                return;
            }
            this.fixBusy = true;
            this.fixMessage = null;
            const jwpIds = rows.map((r) => r.jwp_id).slice(0, 50);
            try {
                const res = await fetch(this.fixDeleteUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        namespace: this.selectedNamespace,
                        jwp_ids: jwpIds,
                    }),
                });
                const data = await res.json();
                if (!data.ok) {
                    throw new Error(data.message || 'Bulk delete failed');
                }
                const deleted = (data.result?.deleted || []).length;
                const failed = (data.result?.failed || []).length;
                this.fixMessage = 'Bulk delete: ' + deleted + ' removed, ' + failed + ' failed';
                this.fixMessageOk = failed === 0;
                await this.runReconcile();
            } catch (e) {
                this.fixMessage = e.message || String(e);
                this.fixMessageOk = false;
            } finally {
                this.fixBusy = false;
            }
        },
    };
}
</script>
@endpush
@endsection
