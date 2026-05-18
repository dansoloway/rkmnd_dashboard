{{-- Reconcile gap buckets (problem rows only). Requires parent Alpine namespaceStudio scope. --}}
<div id="reconcile-gaps" class="bg-white shadow rounded-lg p-6 mb-8">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Reconcile gaps</h2>
            <p class="text-sm text-gray-600 mt-1">Problem rows only — not the full namespace catalog. From the last saved reconcile for this namespace.</p>
        </div>
        <a href="{{ route('videos.embeddings-reconcile', ['namespace' => $selectedNamespace, 'run' => 1]) }}"
           class="text-sm text-blue-600 hover:text-blue-800 whitespace-nowrap">Full reconcile page →</a>
    </div>

    <div x-show="!reconcileSummary && !reconcileLoading && issuesRows.length === 0"
         class="text-sm text-gray-500 py-6 text-center border border-dashed border-gray-200 rounded-lg">
        Run <strong>Reconcile</strong> in the overview above to list missing, orphan, and unexpected vectors.
    </div>

    <div x-show="reconcileLoading" class="text-sm text-gray-500 py-6 text-center">Reconciling…</div>

    <template x-if="reconcileSummary || issuesRows.length > 0">
        <div>
            <p x-show="gapListTruncated()"
               class="mb-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded px-3 py-2">
                Table lists are capped per bucket; tab counts use full reconcile totals.
                <span x-show="reconcileSummary?.missing_from_pinecone_truncated"> Missing list truncated.</span>
                <span x-show="reconcileSummary?.pinecone_not_in_db_truncated"> Orphans list truncated.</span>
                <span x-show="reconcileSummary?.pinecone_unexpected_truncated"> Unexpected list truncated.</span>
            </p>

            <div class="flex flex-wrap gap-2 mb-4" role="tablist">
                <button type="button"
                        x-on:click="issuesBucketFilter = 'all'"
                        x-bind:class="issuesBucketFilter === 'all' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium">
                    All gaps
                    <span class="opacity-80" x-text="'(' + gapBucketCounts().all + ')'"></span>
                </button>
                <button type="button"
                        x-on:click="issuesBucketFilter = 'missing_from_pinecone'"
                        x-bind:class="issuesBucketFilter === 'missing_from_pinecone' ? 'bg-amber-700 text-white' : 'bg-amber-50 text-amber-900 hover:bg-amber-100 border border-amber-200'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium">
                    Missing from Pinecone
                    <span class="opacity-80" x-text="'(' + gapBucketCounts().missing + ')'"></span>
                </button>
                <button type="button"
                        x-on:click="issuesBucketFilter = 'pinecone_not_in_db'"
                        x-bind:class="issuesBucketFilter === 'pinecone_not_in_db' ? 'bg-red-700 text-white' : 'bg-red-50 text-red-900 hover:bg-red-100 border border-red-200'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium">
                    Pinecone not in DB
                    <span class="opacity-80" x-text="'(' + gapBucketCounts().orphans + ')'"></span>
                </button>
                <button type="button"
                        x-on:click="issuesBucketFilter = 'unexpected_in_index'"
                        x-bind:class="issuesBucketFilter === 'unexpected_in_index' ? 'bg-purple-700 text-white' : 'bg-purple-50 text-purple-900 hover:bg-purple-100 border border-purple-200'"
                        class="px-3 py-1.5 rounded-md text-sm font-medium">
                    Unexpected in index
                    <span class="opacity-80" x-text="'(' + gapBucketCounts().unexpected + ')'"></span>
                </button>
            </div>

            <div x-show="filteredIssuesRows().length === 0"
                 class="text-sm text-gray-500 py-8 text-center border border-gray-100 rounded-lg">
                <span x-show="gapBucketCounts().all === 0">No gap rows for this namespace — catalog and Pinecone align.</span>
                <span x-show="gapBucketCounts().all > 0 && filteredIssuesRows().length === 0">No rows in this bucket in the loaded list (may be truncated).</span>
            </div>

            <div x-show="filteredIssuesRows().length > 0" class="hidden md:block overflow-x-auto border border-gray-100 rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Issue</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">jwp_id</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title / WP</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">category_for_ai</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Open</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                    <template x-for="row in filteredIssuesRows()" :key="row.key">
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 text-sm whitespace-nowrap" x-text="row.issueLabel"></td>
                            <td class="px-3 py-2 text-xs font-mono text-gray-900" x-text="row.jwp_id || '—'"></td>
                            <td class="px-3 py-2 text-sm">
                                <span x-text="row.title || '—'"></span>
                                <span class="block text-xs text-gray-500" x-show="row.wp_post_id" x-text="'WP #' + row.wp_post_id"></span>
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-700" x-text="row.category_for_ai || '—'"></td>
                            <td class="px-3 py-2 text-sm whitespace-nowrap">
                                <a :href="row.openUrl" class="text-blue-600 hover:text-blue-800" x-text="row.openLabel"></a>
                            </td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>

            <div class="md:hidden space-y-3 mt-4" x-show="filteredIssuesRows().length > 0">
                <template x-for="row in filteredIssuesRows()" :key="row.key + '-m'">
                    <div class="border border-gray-100 rounded-lg p-4 text-sm">
                        <div class="font-medium text-gray-900" x-text="row.issueLabel"></div>
                        <div class="font-mono text-xs text-gray-600 mt-1" x-text="row.jwp_id || '—'"></div>
                        <div class="mt-1" x-text="row.title || '—'"></div>
                        <div class="text-xs text-gray-500 mt-1" x-show="row.category_for_ai" x-text="row.category_for_ai"></div>
                        <a :href="row.openUrl" class="inline-block mt-2 text-blue-600" x-text="row.openLabel"></a>
                    </div>
                </template>
            </div>

            <p class="mt-3 text-xs text-gray-500" x-show="filteredIssuesRows().length > 0">
                Showing <span x-text="filteredIssuesRows().length"></span> row(s) in this view
                <span x-show="issuesBucketFilter === 'all'"> (loaded from reconcile; buckets may be capped).</span>
            </p>
        </div>
    </template>
</div>
