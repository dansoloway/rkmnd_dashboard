<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VocabularyController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $error = null;
        $vocabularyVersion = null;
        $source = null;
        $concepts = [];

        try {
            $data = $this->api()->getVocabulary();
            $vocabularyVersion = $data['vocabulary_version'] ?? null;
            $source = $data['source'] ?? null;
            $concepts = is_array($data['concepts'] ?? null) ? $data['concepts'] : [];
        } catch (\Exception $e) {
            Log::warning('Failed to load vocabulary', ['message' => $e->getMessage()]);
            $error = $e->getMessage();
        }

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $concepts = array_values(array_filter($concepts, function ($c) use ($needle) {
                if (! is_array($c)) {
                    return false;
                }
                $hay = mb_strtolower(implode(' ', array_filter([
                    (string) ($c['concept_id'] ?? ''),
                    (string) ($c['canonical'] ?? ''),
                    (string) ($c['body_region'] ?? ''),
                    implode(' ', $c['aliases'] ?? []),
                    implode(' ', $c['abbreviations'] ?? []),
                    implode(' ', $c['common_misspellings'] ?? []),
                ])));

                return str_contains($hay, $needle);
            }));
        }

        return view('ai-search.vocabulary.index', [
            'concepts' => $concepts,
            'vocabularyVersion' => $vocabularyVersion,
            'source' => $source,
            'q' => $q,
            'error' => $error,
        ]);
    }

    public function create()
    {
        return view('ai-search.vocabulary.edit', [
            'concept' => [
                'concept_id' => '',
                'canonical' => '',
                'aliases' => [],
                'abbreviations' => [],
                'common_misspellings' => [],
                'ambiguity_level' => 'low',
                'safe_for_automatic_expansion' => false,
                'body_region' => '',
                'active' => true,
                'notes' => '',
            ],
            'isNew' => true,
        ]);
    }

    public function edit(string $conceptId)
    {
        try {
            $data = $this->api()->getVocabulary();
            $concepts = is_array($data['concepts'] ?? null) ? $data['concepts'] : [];
            $concept = null;
            foreach ($concepts as $c) {
                if (is_array($c) && ($c['concept_id'] ?? '') === $conceptId) {
                    $concept = $c;
                    break;
                }
            }
            if ($concept === null) {
                return redirect()
                    ->route('ai-search.vocabulary.index')
                    ->with('error', "Concept not found: {$conceptId}");
            }

            return view('ai-search.vocabulary.edit', [
                'concept' => $concept,
                'isNew' => false,
                'vocabularyVersion' => $data['vocabulary_version'] ?? null,
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->route('ai-search.vocabulary.index')
                ->with('error', $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $this->validateConcept($request, true);
        try {
            $result = $this->api()->createVocabularyConcept($validated);
            $ver = $result['vocabulary_version'] ?? '?';

            return redirect()
                ->route('ai-search.vocabulary.edit', $validated['concept_id'])
                ->with('success', "Created concept. Vocabulary version is now {$ver}.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, string $conceptId)
    {
        $validated = $this->validateConcept($request, false);
        $validated['concept_id'] = $conceptId;
        try {
            $result = $this->api()->upsertVocabularyConcept($conceptId, $validated);
            $ver = $result['vocabulary_version'] ?? '?';

            return redirect()
                ->route('ai-search.vocabulary.edit', $conceptId)
                ->with('success', "Saved. Vocabulary version is now {$ver}.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(Request $request, string $conceptId)
    {
        $hard = $request->boolean('hard');
        try {
            $result = $this->api()->deleteVocabularyConcept($conceptId, $hard);
            $action = $result['action'] ?? 'updated';
            $ver = $result['vocabulary_version'] ?? '?';

            return redirect()
                ->route('ai-search.vocabulary.index')
                ->with('success', ucfirst($action)." {$conceptId}. Vocabulary version is now {$ver}.");
        } catch (\Exception $e) {
            return redirect()
                ->route('ai-search.vocabulary.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validateConcept(Request $request, bool $requireId): array
    {
        $rules = [
            'canonical' => 'required|string|max:255',
            'aliases' => 'nullable|string',
            'abbreviations' => 'nullable|string',
            'common_misspellings' => 'nullable|string',
            'ambiguity_level' => 'required|in:low,medium,high',
            'safe_for_automatic_expansion' => 'nullable|boolean',
            'body_region' => 'nullable|string|max:255',
            'active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
            'index_canonical' => 'nullable|boolean',
        ];
        if ($requireId) {
            $rules['concept_id'] = 'required|string|max:64|regex:/^[a-z][a-z0-9_]{1,63}$/';
        }

        $validated = $request->validate($rules);

        return [
            'concept_id' => $validated['concept_id'] ?? null,
            'canonical' => trim($validated['canonical']),
            'aliases' => $this->linesToList($validated['aliases'] ?? ''),
            'abbreviations' => $this->linesToList($validated['abbreviations'] ?? ''),
            'common_misspellings' => $this->linesToList($validated['common_misspellings'] ?? ''),
            'ambiguity_level' => $validated['ambiguity_level'],
            'safe_for_automatic_expansion' => $request->boolean('safe_for_automatic_expansion'),
            'body_region' => trim((string) ($validated['body_region'] ?? '')) ?: null,
            'active' => $request->boolean('active', true),
            'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            'index_canonical' => $request->has('index_canonical')
                ? $request->boolean('index_canonical')
                : null,
        ];
    }

    /**
     * @return list<string>
     */
    private function linesToList(string $raw): array
    {
        $parts = preg_split('/[\n,]+/', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return array_values(array_unique($out));
    }

    private function api(): BackendApiService
    {
        $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');

        return new BackendApiService($apiKey);
    }
}
