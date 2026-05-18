<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;

class AiSearchTest extends FeatureTestCase
{
    public function test_semantic_search_returns_results_with_search_id(): void
    {
        $this->actingAsTenantUser();

        $response = $this->post(route('ai-search.search'), [
            'query' => 'hip mobility',
            'namespace' => 'v6_title_tags',
        ]);

        $response->assertOk();
        $response->assertSee('Hip Mobility Flow');
        $response->assertSee('test-search-session-001', false);
        $response->assertSee('Relevant?', false);
    }

    public function test_search_feedback_proxies_to_pipeline(): void
    {
        $this->actingAsTenantUser();

        $response = $this->postJson(route('ai-search.feedback'), [
            'search_id' => 'test-search-session-001',
            'vote' => 1,
            'wp_post_id' => 5001,
            'rank' => 1,
            'pinecone_score' => 0.91,
        ]);

        $response->assertOk();
        $response->assertJson(['ok' => true, 'feedback_id' => 1]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/v1/search/feedback')
                && $request['search_id'] === 'test-search-session-001'
                && (int) $request['vote'] === 1
                && (int) $request['wp_post_id'] === 5001
                && $request['source'] === 'dashboard';
        });
    }

    public function test_search_feedback_requires_search_id(): void
    {
        $this->actingAsTenantUser();

        $this->postJson(route('ai-search.feedback'), [
            'vote' => 1,
        ])->assertStatus(422);
    }
}
