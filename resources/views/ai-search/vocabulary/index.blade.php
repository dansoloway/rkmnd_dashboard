@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-3xl font-heading font-bold text-gray-900">Anatomy dictionary</h1>
            <p class="mt-2 text-gray-600 text-sm">
                Curated concepts for Literal search: aliases, abbreviations, misspellings, and expansion safety.
                Changes apply immediately (no API restart).
            </p>
            @if($vocabularyVersion !== null)
                <p class="mt-1 text-xs text-gray-500">Version <code class="bg-gray-100 px-1 rounded">{{ $vocabularyVersion }}</code>
                    · <a href="{{ route('ai-search.playground.index') }}" class="text-blue-600 hover:underline">Test in Literal mode</a>
                </p>
            @endif
        </div>
        <a href="{{ route('ai-search.vocabulary.create') }}"
           class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
            Add concept
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error') || !empty($error))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">{{ session('error') ?? $error }}</div>
    @endif

    <form method="GET" action="{{ route('ai-search.vocabulary.index') }}" class="flex gap-2">
        <input type="search" name="q" value="{{ $q }}" placeholder="Filter by id, canonical, abbr…"
               class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
        <button type="submit" class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Filter</button>
    </form>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-2 font-medium">Concept</th>
                    <th class="px-4 py-2 font-medium">Abbr / aliases</th>
                    <th class="px-4 py-2 font-medium">Flags</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($concepts as $c)
                    @php
                        $active = ($c['active'] ?? true) !== false;
                        $abbr = implode(', ', $c['abbreviations'] ?? []);
                        $aliases = implode(', ', array_slice($c['aliases'] ?? [], 0, 3));
                    @endphp
                    <tr class="{{ $active ? '' : 'bg-gray-50 opacity-70' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $c['canonical'] ?? '—' }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ $c['concept_id'] ?? '' }}</div>
                            @if(!empty($c['body_region']))
                                <div class="text-xs text-gray-400">{{ $c['body_region'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">
                            @if($abbr !== '')
                                <div><span class="text-gray-500">abbr:</span> {{ $abbr }}</div>
                            @endif
                            @if($aliases !== '')
                                <div class="text-xs text-gray-500">{{ $aliases }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            <div>{{ $c['ambiguity_level'] ?? '—' }}</div>
                            <div>{{ !empty($c['safe_for_automatic_expansion']) ? 'auto-expand' : 'no expand' }}</div>
                            @unless($active)
                                <div class="text-amber-700">inactive</div>
                            @endunless
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('ai-search.vocabulary.edit', $c['concept_id']) }}"
                               class="text-blue-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">No concepts matched.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
