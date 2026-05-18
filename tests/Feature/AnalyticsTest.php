<?php

namespace Tests\Feature;

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
            ->assertSee('Whole search')
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
            ->assertSee('test-search-session-001', false);
    }

    public function test_analytics_recent_queries_show_rate_buttons_when_search_id_present(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('shoulder pain')
            ->assertSee('Whole search');
    }
}
