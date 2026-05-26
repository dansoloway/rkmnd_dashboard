<?php

namespace Tests\Feature;

use Tests\Support\BackendApiFake;

class AnalyticsTest extends FeatureTestCase
{
    public function test_analytics_page_shows_search_feedback_history(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('analytics.index'))
            ->assertOk()
            ->assertViewIs('analytics.index')
            ->assertSee('Search feedback')
            ->assertSee('hip mobility for runners')
            ->assertSee('Whole search', false)
            ->assertSee('Thumbs up')
            ->assertSee('Thumbs down');
    }

    public function test_analytics_feedback_period_query_param(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('analytics.index', ['feedback_days' => 7]))
            ->assertOk()
            ->assertViewHas('feedbackDays', 7);
    }

    public function test_analytics_inline_search_and_rate_form(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Rate a search')
            ->assertSee('Search &amp; rate', false)
            ->assertSee('Search & rate');

        $this->post(route('analytics.search'), [
            'query' => 'hip mobility',
            'namespace' => 'v6_title_tags',
            'feedback_days' => 30,
        ])
            ->assertOk()
            ->assertSee('Hip Mobility Flow')
            ->assertSee(route('videos.show', 81), false)
            ->assertSee('test-search-session-001', false);
    }

    public function test_analytics_recent_queries_show_rate_buttons_when_search_id_present(): void
    {
        $this->actingAsTenantUser();

        $expectedTime = \Carbon\Carbon::parse(
            BackendApiFake::recentQueriesPayload()['queries'][0]['timestamp']
        )
            ->timezone(config('app.timezone'))
            ->format('M j, Y g:i A');

        $this->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('shoulder pain')
            ->assertSee('Whole search', false)
            ->assertSee($expectedTime, false)
            ->assertSee(route('videos.show', 81), false);
    }

    public function test_analytics_shows_search_user_attribution(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Searches by user')
            ->assertSee('Jane Member')
            ->assertSee('jane@example.com');
    }

    public function test_analytics_feedback_manager_views(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('By search query')
            ->assertSee('hip mobility for runners')
            ->assertSee('Distinct queries')
            ->assertSee('All namespaces');

        $this->get(route('analytics.index', ['feedback_tab' => 'detail']))
            ->assertOk()
            ->assertSee(route('videos.show', 81), false)
            ->assertSee('Hip Mobility Flow');
    }

    public function test_analytics_feedback_namespace_filter(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('analytics.index', [
            'feedback_namespace' => 'v6_title_tags',
            'feedback_tab' => 'overview',
            'feedback_days' => 30,
        ]))
            ->assertOk()
            ->assertSee('Showing ratings for namespace')
            ->assertSee('v6_title_tags')
            ->assertSee('feedback_namespace=v6_title_tags', false);
    }
}
