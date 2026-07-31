@extends('layouts.app')

@section('content')
@php
    $isNew = !empty($isNew);
    $c = $concept ?? [];
    $list = function ($key) use ($c) {
        $vals = $c[$key] ?? [];
        return is_array($vals) ? implode("\n", $vals) : '';
    };
@endphp
<div class="space-y-6 max-w-3xl mx-auto">
    <div>
        <p class="text-sm"><a href="{{ route('ai-search.vocabulary.index') }}" class="text-blue-600 hover:underline">&larr; Anatomy dictionary</a></p>
        <h1 class="text-3xl font-heading font-bold text-gray-900 mt-2">
            {{ $isNew ? 'Add concept' : 'Edit concept' }}
        </h1>
        @if(!empty($vocabularyVersion))
            <p class="mt-1 text-xs text-gray-500">Dictionary version <code class="bg-gray-100 px-1 rounded">{{ $vocabularyVersion }}</code></p>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-red-50 border border-red-100 text-red-700 px-3 py-2 rounded text-sm">{{ $errors->first() }}</div>
    @endif

    <form method="POST"
          action="{{ $isNew ? route('ai-search.vocabulary.store') : route('ai-search.vocabulary.update', $c['concept_id']) }}"
          class="bg-white rounded-lg shadow-sm p-6 space-y-4">
        @csrf
        @unless($isNew)
            @method('PUT')
        @endunless

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Concept ID</label>
            <input type="text" name="concept_id" value="{{ old('concept_id', $c['concept_id'] ?? '') }}"
                   @unless($isNew) readonly @endunless
                   required
                   pattern="[a-z][a-z0-9_]{1,63}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono @unless($isNew) bg-gray-50 @endunless"
                   placeholder="quadratus_lumborum">
            <p class="mt-1 text-xs text-gray-500">snake_case; immutable after create.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Canonical</label>
            <input type="text" name="canonical" value="{{ old('canonical', $c['canonical'] ?? '') }}" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                   placeholder="quadratus lumborum">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ambiguity</label>
                <select name="ambiguity_level" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    @foreach(['low','medium','high'] as $lvl)
                        <option value="{{ $lvl }}" @selected(old('ambiguity_level', $c['ambiguity_level'] ?? 'low') === $lvl)>{{ $lvl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Body region</label>
                <input type="text" name="body_region" value="{{ old('body_region', $c['body_region'] ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
            </div>
        </div>

        <div class="flex flex-wrap gap-6 text-sm">
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="safe_for_automatic_expansion" value="1"
                       @checked(old('safe_for_automatic_expansion', !empty($c['safe_for_automatic_expansion'])))>
                Safe for automatic expansion
            </label>
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="active" value="1"
                       @checked(old('active', ($c['active'] ?? true) !== false))>
                Active
            </label>
            @if(array_key_exists('index_canonical', $c) || old('index_canonical') !== null)
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="index_canonical" value="1"
                           @checked(old('index_canonical', !empty($c['index_canonical'])))>
                    Index canonical
                </label>
            @endif
        </div>
        <p class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded px-3 py-2">
            Avoid enabling auto-expansion on high-ambiguity abbreviations (e.g. bare “IT”).
        </p>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Aliases <span class="text-gray-400 font-normal">(one per line)</span></label>
            <textarea name="aliases" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono">{{ old('aliases', $list('aliases')) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Abbreviations <span class="text-gray-400 font-normal">(one per line)</span></label>
            <textarea name="abbreviations" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono">{{ old('abbreviations', $list('abbreviations')) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Common misspellings <span class="text-gray-400 font-normal">(one per line)</span></label>
            <textarea name="common_misspellings" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono">{{ old('common_misspellings', $list('common_misspellings')) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">{{ old('notes', $c['notes'] ?? '') }}</textarea>
        </div>

        <div class="flex flex-wrap gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                {{ $isNew ? 'Create' : 'Save' }}
            </button>
            <a href="{{ route('ai-search.vocabulary.index') }}" class="px-5 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Cancel</a>
        </div>
    </form>

    @unless($isNew)
        <form method="POST" action="{{ route('ai-search.vocabulary.destroy', $c['concept_id']) }}"
              onsubmit="return confirm('Deactivate this concept? (Inactive concepts are skipped by Literal search.)');"
              class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3 items-center">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-4 py-2 border border-amber-300 text-amber-800 rounded-md text-sm hover:bg-amber-50">
                Deactivate
            </button>
            <button type="submit" name="hard" value="1"
                    onclick="return confirm('Permanently delete this concept?');"
                    class="px-4 py-2 border border-red-300 text-red-700 rounded-md text-sm hover:bg-red-50">
                Delete permanently
            </button>
        </form>
    @endunless
</div>
@endsection
