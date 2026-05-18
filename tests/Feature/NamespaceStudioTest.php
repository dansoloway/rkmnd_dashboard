<?php

namespace Tests\Feature;

use App\Models\EmbeddingReconcileSnapshot;
use App\Models\Tenant;

class NamespaceStudioTest extends FeatureTestCase
{
    public function test_guest_cannot_access_namespace_studio(): void
    {
        $this->get(route('videos.namespace-studio'))
            ->assertRedirect(route('login'));
    }

    public function test_namespace_studio_page_renders_for_authenticated_user(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('videos.namespace-studio'))
            ->assertOk()
            ->assertViewIs('videos.namespace-studio')
            ->assertSee('Namespace studio')
            ->assertSee('Select a namespace above');
    }

    public function test_catalog_is_empty_until_namespace_is_selected(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('videos.namespace-studio'))
            ->assertOk()
            ->assertViewHas('hasNamespace', false)
            ->assertViewHas('rows', [])
            ->assertDontSee('Namespace overview')
            ->assertDontSee('Test Video One');
    }

    public function test_overview_shows_namespace_scoped_counts(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('videos.namespace-studio', ['namespace' => 'v6_title_tags']))
            ->assertOk()
            ->assertSee('Namespace overview')
            ->assertSee('Videos in namespace')
            ->assertViewHas('namespaceCatalogCount', 2)
            ->assertDontSee('Videos in catalog (WP sync)')
            ->assertDontSee('All videos (tenant catalog)');
    }

    public function test_overview_shows_reconcile_summary_when_saved(): void
    {
        $tenant = Tenant::factory()->create();
        $this->actingAsTenantUser(null, $tenant);

        EmbeddingReconcileSnapshot::query()->create([
            'tenant_id' => $tenant->id,
            'namespace' => 'v6_title_tags',
            'payload' => \Tests\Support\BackendApiFake::reconcilePayload(),
            'reconciled_at' => now()->subHour(),
        ]);

        $this->get(route('videos.namespace-studio', ['namespace' => 'v6_title_tags']))
            ->assertOk()
            ->assertSee('42')
            ->assertSee('Reconcile gaps')
            ->assertSee('Missing from Pinecone');
    }

    public function test_catalog_requests_videos_for_selected_namespace(): void
    {
        $seenNamespace = null;
        \Tests\Support\BackendApiFake::register([
            '/api/v1/wordpress/videos' => function (\Illuminate\Http\Client\Request $request) use (&$seenNamespace) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
                $seenNamespace = $query['embedding_namespace'] ?? null;

                return \Illuminate\Support\Facades\Http::response(
                    \Tests\Support\BackendApiFake::videosListPayload()
                );
            },
        ]);

        $this->actingAsTenantUser();

        $this->get(route('videos.namespace-studio', ['namespace' => 'v6_title_tags']))
            ->assertOk()
            ->assertSee('Test Video One');

        $this->assertSame('v6_title_tags', $seenNamespace);
    }

    public function test_reconcile_persists_snapshot_for_tenant_and_namespace(): void
    {
        $tenant = Tenant::factory()->create();
        $user = $this->actingAsTenantUser(null, $tenant);

        $this->postJson(route('videos.namespace-studio.reconcile'), [
            'namespace' => 'v6_title_tags',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure([
                'payload' => ['summary', 'missing_from_pinecone'],
                'reconciled_at',
                'reconciled_at_display',
            ]);

        $this->assertDatabaseHas('embedding_reconcile_snapshots', [
            'tenant_id' => $tenant->id,
            'namespace' => 'v6_title_tags',
        ]);

        $snapshot = EmbeddingReconcileSnapshot::query()
            ->where('tenant_id', $tenant->id)
            ->where('namespace', 'v6_title_tags')
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertIsArray($snapshot->payload);
        $this->assertEquals(42, $snapshot->payload['summary']['pinecone_vector_count'] ?? null);
        $this->assertNotNull($snapshot->reconciled_at);
    }

    public function test_saved_snapshot_is_shown_on_page_load(): void
    {
        $tenant = Tenant::factory()->create();
        $this->actingAsTenantUser(null, $tenant);

        EmbeddingReconcileSnapshot::query()->create([
            'tenant_id' => $tenant->id,
            'namespace' => 'v6_title_tags',
            'payload' => \Tests\Support\BackendApiFake::reconcilePayload(),
            'reconciled_at' => now()->subHour(),
        ]);

        $this->get(route('videos.namespace-studio', ['namespace' => 'v6_title_tags']))
            ->assertOk()
            ->assertSee('Last saved reconcile');
    }

    public function test_embedding_text_endpoint_returns_text_for_namespace(): void
    {
        $this->actingAsTenantUser();

        $this->getJson(route('videos.embedding-text', ['id' => 1]).'?namespace=v6_title_tags')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('text', 'Title: Test Video One | Tags: yoga');
    }

    public function test_embedding_text_requires_namespace_query_param(): void
    {
        $this->actingAsTenantUser();

        $this->getJson(route('videos.embedding-text', ['id' => 1]))
            ->assertStatus(400)
            ->assertJsonPath('ok', false);
    }
}
