<?php

namespace App\Http\Controllers;

use App\Services\BackendApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CatalogTermsController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $error = null;
        $catalogVersion = null;
        $notes = null;
        $terms = [];
        $properNouns = [];

        try {
            $data = $this->api()->getCatalogTerms();
            $catalogVersion = $data['catalog_version'] ?? null;
            $notes = $data['notes'] ?? null;
            $terms = is_array($data['terms'] ?? null) ? $data['terms'] : [];
            $properNouns = is_array($data['proper_nouns'] ?? null) ? $data['proper_nouns'] : [];
        } catch (\Exception $e) {
            Log::warning('Failed to load catalog terms', ['message' => $e->getMessage()]);
            $error = $e->getMessage();
        }

        $filtered = $terms;
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $filtered = array_values(array_filter($terms, fn ($t) => str_contains(mb_strtolower((string) $t), $needle)));
        }

        return view('ai-search.catalog-terms.index', [
            'terms' => $filtered,
            'termCount' => count($terms),
            'properNouns' => $properNouns,
            'catalogVersion' => $catalogVersion,
            'notes' => $notes,
            'q' => $q,
            'error' => $error,
        ]);
    }

    public function storeTerm(Request $request)
    {
        $validated = $request->validate([
            'term' => 'required|string|max:255',
        ]);
        try {
            $result = $this->api()->addCatalogTerm(trim($validated['term']));
            $ver = $result['catalog_version'] ?? '?';
            $added = ! empty($result['added']);

            return redirect()
                ->route('ai-search.catalog-terms.index', ['q' => trim($validated['term'])])
                ->with('success', ($added ? 'Added term.' : 'Term already present.')." Catalog version is now {$ver}.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroyTerm(Request $request)
    {
        $validated = $request->validate([
            'term' => 'required|string|max:255',
        ]);
        $q = trim((string) $request->input('q', $request->query('q', '')));
        try {
            $result = $this->api()->removeCatalogTerm(trim($validated['term']));
            $ver = $result['catalog_version'] ?? '?';

            return redirect()
                ->route('ai-search.catalog-terms.index', array_filter(['q' => $q !== '' ? $q : null]))
                ->with('success', "Removed term. Catalog version is now {$ver}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function updateProperNouns(Request $request)
    {
        $validated = $request->validate([
            'proper_nouns' => 'nullable|string',
        ]);
        $list = preg_split('/[\n,]+/', (string) ($validated['proper_nouns'] ?? '')) ?: [];
        $proper = [];
        foreach ($list as $p) {
            $p = trim($p);
            if ($p !== '') {
                $proper[] = $p;
            }
        }
        $proper = array_values(array_unique($proper));

        try {
            $result = $this->api()->updateCatalogProperNouns($proper);
            $ver = $result['catalog_version'] ?? '?';

            return redirect()
                ->route('ai-search.catalog-terms.index')
                ->with('success', "Updated proper nouns. Catalog version is now {$ver}.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    private function api(): BackendApiService
    {
        $apiKey = session('tenant_api_key') ?? config('backend.default_api_key');

        return new BackendApiService($apiKey);
    }
}
