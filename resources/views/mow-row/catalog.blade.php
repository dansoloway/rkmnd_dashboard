@extends('layouts.app')

@php
    use App\Support\MowRowAppDisplay;
@endphp

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-sm text-purple-700 font-medium">MOW/ROW PWA</p>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Catalog</h1>
            <p class="mt-2 text-gray-600 text-sm max-w-3xl">
                Videos indexed in Pinecone namespace <code class="bg-gray-100 px-1 rounded text-xs">mow_row_v6_title_tags</code>
                for Move, Breathe, and Roll content. Type changes re-index search metadata automatically.
            </p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:text-blue-800">← Home</a>
    </div>

    @if(!empty($error))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">{{ $error }}</div>
    @endif

    <form method="GET" action="{{ route('mow-row.catalog') }}" class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search title</label>
            <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
        </div>
        <div>
            <label for="content_type" class="block text-sm font-medium text-gray-700 mb-1">Content type</label>
            <select name="content_type" id="content_type" class="px-3 py-2 border border-gray-300 rounded-md">
                <option value="">All MOW/ROW</option>
                <option value="move" @selected(($filters['content_type'] ?? '') === 'move')>Move</option>
                <option value="breathe" @selected(($filters['content_type'] ?? '') === 'breathe')>Breathe</option>
                <option value="roll" @selected(in_array($filters['content_type'] ?? '', ['roll', 'weekly'], true))>Roll</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">Apply</button>
    </form>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap justify-between items-center gap-3">
            <p class="text-sm text-gray-600"><strong>{{ number_format($total) }}</strong> videos in namespace</p>
            <button type="button" id="mow-row-toggle-all-previews" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                Expand all app previews
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Title</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">WP ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">JWP ID</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Namespaces</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 w-28">App display</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($videos as $video)
                        @php
                            $pillar = MowRowAppDisplay::resolvePillar($video);
                            $previewRows = MowRowAppDisplay::flatPreviewRows($video);
                            $previewId = 'mow-row-preview-' . ($video['id'] ?? $loop->index);
                            $selectClass = match ($pillar) {
                                'move' => 'border-green-300 bg-green-50 text-green-800',
                                'roll' => 'border-purple-300 bg-purple-50 text-purple-800',
                                'breathe' => 'border-sky-300 bg-sky-50 text-sky-800',
                                default => 'border-gray-300 bg-white text-gray-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50" data-video-id="{{ $video['id'] ?? '' }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('videos.show', ['id' => $video['id'], 'product' => 'mow_row']) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                    {{ $video['title'] ?? 'Untitled' }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <select
                                        class="mow-row-pillar-select text-xs font-medium rounded-full px-2 py-1 border {{ $selectClass }}"
                                        data-video-id="{{ $video['id'] ?? '' }}"
                                        data-update-url="{{ route('mow-row.catalog.content-pillar', ['id' => $video['id'] ?? 0]) }}"
                                        aria-label="Content type for {{ $video['title'] ?? 'video' }}"
                                    >
                                        <option value="move" @selected($pillar === 'move')>Move</option>
                                        <option value="breathe" @selected($pillar === 'breathe')>Breathe</option>
                                        <option value="roll" @selected($pillar === 'roll')>Roll</option>
                                    </select>
                                    <span class="mow-row-pillar-status text-xs text-gray-500 hidden" aria-live="polite"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $video['wp_post_id'] ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $video['jwp_id'] ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $video['embedding_namespaces'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <button
                                    type="button"
                                    class="mow-row-preview-toggle text-xs font-medium text-blue-600 hover:text-blue-800"
                                    aria-expanded="false"
                                    aria-controls="{{ $previewId }}"
                                    data-preview-id="{{ $previewId }}"
                                >
                                    Show
                                </button>
                            </td>
                        </tr>
                        <tr id="{{ $previewId }}" class="mow-row-app-preview hidden bg-slate-50">
                            <td colspan="6" class="px-4 py-4">
                                @if(count($previewRows) === 0)
                                    <p class="text-sm text-gray-500">No app display fields available for this video yet.</p>
                                @else
                                    <p class="text-xs text-gray-500 mb-3">Fields shown to users in the Move Breathe Roll app (search cards, video page, and details).</p>
                                    <div class="grid gap-4 lg:grid-cols-3">
                                        @foreach(collect($previewRows)->groupBy('section') as $section => $rows)
                                            <div class="rounded-md border border-gray-200 bg-white overflow-hidden">
                                                <div class="px-3 py-2 bg-gray-100 border-b border-gray-200 text-xs font-semibold uppercase tracking-wide text-gray-600">
                                                    {{ $section }}
                                                </div>
                                                <dl class="divide-y divide-gray-100 text-sm">
                                                    @foreach($rows as $row)
                                                        <div class="px-3 py-2">
                                                            @if(($row['label'] ?? '') !== '')
                                                                <dt class="text-xs font-medium text-gray-500">{{ $row['label'] }}</dt>
                                                            @endif
                                                            <dd @class(['mt-0.5 text-gray-900 whitespace-pre-wrap', 'mt-0' => ($row['label'] ?? '') === ''])>
                                                                {{ $row['value'] }}
                                                            </dd>
                                                        </div>
                                                    @endforeach
                                                </dl>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">No videos found in this namespace.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var csrf = @json(csrf_token());
    var pillarClasses = {
        move: 'border-green-300 bg-green-50 text-green-800',
        breathe: 'border-sky-300 bg-sky-50 text-sky-800',
        roll: 'border-purple-300 bg-purple-50 text-purple-800'
    };

    function applyPillarStyle(select, pillar) {
        select.className = 'mow-row-pillar-select text-xs font-medium rounded-full px-2 py-1 border ' + (pillarClasses[pillar] || 'border-gray-300 bg-white text-gray-700');
    }

    function setPreviewOpen(toggle, open) {
        var previewId = toggle.getAttribute('data-preview-id');
        var panel = previewId ? document.getElementById(previewId) : null;
        if (!panel) {
            return;
        }
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.textContent = open ? 'Hide' : 'Show';
        panel.classList.toggle('hidden', !open);
    }

    document.querySelectorAll('.mow-row-preview-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            var open = toggle.getAttribute('aria-expanded') !== 'true';
            setPreviewOpen(toggle, open);
        });
    });

    var expandAllBtn = document.getElementById('mow-row-toggle-all-previews');
    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function () {
            var toggles = document.querySelectorAll('.mow-row-preview-toggle');
            var anyClosed = Array.prototype.some.call(toggles, function (toggle) {
                return toggle.getAttribute('aria-expanded') !== 'true';
            });
            toggles.forEach(function (toggle) {
                setPreviewOpen(toggle, anyClosed);
            });
            expandAllBtn.textContent = anyClosed ? 'Collapse all app previews' : 'Expand all app previews';
        });
    }

    document.querySelectorAll('.mow-row-pillar-select').forEach(function (select) {
        var previous = select.value;
        var status = select.parentElement.querySelector('.mow-row-pillar-status');

        select.addEventListener('change', function () {
            var pillar = select.value;
            var url = select.getAttribute('data-update-url');
            if (!url || pillar === previous) {
                return;
            }

            select.disabled = true;
            if (status) {
                status.textContent = 'Saving…';
                status.classList.remove('hidden', 'text-red-600', 'text-green-700');
                status.classList.add('text-gray-500');
            }

            fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({ mow_row_content_pillar: pillar })
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) {
                            throw new Error(data.message || 'Save failed');
                        }
                        return data;
                    });
                })
                .then(function () {
                    previous = pillar;
                    applyPillarStyle(select, pillar);
                    if (status) {
                        status.textContent = 'Saved';
                        status.classList.remove('text-gray-500', 'text-red-600');
                        status.classList.add('text-green-700');
                        setTimeout(function () {
                            status.classList.add('hidden');
                            status.textContent = '';
                        }, 2000);
                    }
                })
                .catch(function (err) {
                    select.value = previous;
                    applyPillarStyle(select, previous);
                    if (status) {
                        status.textContent = 'Error';
                        status.classList.remove('hidden', 'text-gray-500', 'text-green-700');
                        status.classList.add('text-red-600');
                    }
                    console.error(err);
                })
                .finally(function () {
                    select.disabled = false;
                });
        });
    });
})();
</script>
@endpush
@endsection
