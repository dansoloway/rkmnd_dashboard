@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto" x-data="{ filter: @js($q) }">
    <div>
        <h1 class="text-3xl font-heading font-bold text-gray-900">Catalog terms</h1>
        <p class="mt-2 text-gray-600 text-sm">
            Auto-extracted surfaces used for <strong>fuzzy typo correction</strong> in Literal mode.
            Abbreviation expansion (e.g. QL → quadratus lumborum) is edited in
            <a href="{{ route('ai-search.vocabulary.index') }}" class="text-blue-600 hover:underline">Anatomy dictionary</a>.
        </p>
        @if($catalogVersion !== null)
            <p class="mt-1 text-xs text-gray-500">
                Version <code class="bg-gray-100 px-1 rounded">{{ $catalogVersion }}</code>
                · {{ number_format($termCount) }} terms
                · <a href="{{ route('ai-search.playground.index') }}" class="text-blue-600 hover:underline">Test in Literal mode</a>
            </p>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error') || !empty($error))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">{{ session('error') ?? $error }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Add term</h2>
        <form method="POST" action="{{ route('ai-search.catalog-terms.store') }}" class="flex flex-wrap gap-2">
            @csrf
            <input type="text" name="term" value="{{ old('term') }}" required maxlength="255"
                   placeholder="e.g. hamstring"
                   class="flex-1 min-w-[12rem] px-3 py-2 border border-gray-300 rounded-md text-sm">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">Add</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Proper nouns</h2>
        <p class="text-xs text-gray-500">Never fuzzy-correct these (product / instructor names).</p>
        <form method="POST" action="{{ route('ai-search.catalog-terms.proper-nouns') }}">
            @csrf
            @method('PUT')
            <textarea name="proper_nouns" rows="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono">{{ old('proper_nouns', implode("\n", $properNouns ?? [])) }}</textarea>
            <button type="submit" class="mt-2 px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Save proper nouns</button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-gray-900">Terms</h2>
            <form method="GET" action="{{ route('ai-search.catalog-terms.index') }}" class="flex gap-2">
                <input type="search" name="q" value="{{ $q }}" placeholder="Filter…"
                       class="px-3 py-1.5 border border-gray-300 rounded-md text-sm w-56">
                <button type="submit" class="px-3 py-1.5 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Filter</button>
            </form>
        </div>
        <ul class="divide-y divide-gray-100 max-h-[28rem] overflow-y-auto text-sm">
            @forelse($terms as $term)
                <li class="py-2 flex items-center justify-between gap-3">
                    <span class="font-mono text-gray-800">{{ $term }}</span>
                    <form method="POST" action="{{ route('ai-search.catalog-terms.destroy') }}"
                          onsubmit="return confirm('Remove this term?');">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="term" value="{{ $term }}">
                        @if($q !== '')
                            <input type="hidden" name="q" value="{{ $q }}">
                        @endif
                        <button type="submit" class="text-red-600 hover:underline text-xs">Remove</button>
                    </form>
                </li>
            @empty
                <li class="py-6 text-center text-gray-500">No terms matched.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
