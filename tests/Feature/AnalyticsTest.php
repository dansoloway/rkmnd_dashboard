<?php

namespace Tests\Feature;

use Tests\Support\BackendApiFake;

class AnalyticsTest extends FeatureTestCase
{
    public function test_analytics_default_tab_is_overview(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics'))
            ->assertOk()
            ->assertViewIs('analytics.index')
            ->assertViewHas('analyticsTab', 'overview')
            ->assertSee('Account &', false)
            ->assertSee('Search Queries');
    }

    public function test_analytics_searches_tab_shows_recent_queries(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics', ['tab' => 'searches']))
            ->assertOk()
            ->assertViewHas('analyticsTab', 'searches')
            ->assertSee('Recent Search Queries')
            ->assertSee('shoulder pain')
            ->assertSee('Jane Member')
            ->assertSee('Searches by user');
    }

    public function test_analytics_user_email_auto_selects_searches_tab(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics', ['user_email' => 'jane@example.com']))
            ->assertOk()
            ->assertViewHas('analyticsTab', 'searches')
            ->assertSee('Showing searches for')
            ->assertSee('jane@example.com')
            ->assertSee('Clear filter');
    }

    public function test_analytics_user_filter_auto_selects_searches_tab(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics', ['user' => 'jane']))
            ->assertOk()
            ->assertViewHas('analyticsTab', 'searches')
            ->assertSee('Showing searches for')
            ->assertSee('jane');
    }

    public function test_analytics_feedback_tab_shows_feedback_section(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics', ['tab' => 'feedback']))
            ->assertOk()
            ->assertViewHas('analyticsTab', 'feedback')
            ->assertSee('Search feedback')
            ->assertSee('hip mobility for runners')
            ->assertSee('Thumbs up')
            ->assertSee('Thumbs down')
            ->assertSee('Rate a search')
            ->assertSee('Search &amp; rate', false);
    }

    public function test_analytics_feedback_period_query_param(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics', ['tab' => 'feedback', 'feedback_days' => 7]))
            ->assertOk()
            ->assertViewHas('feedbackDays', 7);
    }

    public function test_analytics_inline_search_and_rate_form(): void
    {
        $this->actingAsTenantUser();

        $this->post(route('ai-search.analytics.search'), [
            'query' => 'hip mobility',
            'namespace' => 'v6_title_tags',
            'feedback_days' => 30,
        ])
            ->assertOk()
            ->assertViewHas('analyticsTab', 'feedback')
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

        $this->get(route('ai-search.analytics', ['tab' => 'searches']))
            ->assertOk()
            ->assertSee('shoulder pain')
            ->assertSee('Whole search', false)
            ->assertSee($expectedTime, false)
            ->assertSee(route('videos.show', 81), false);
    }

    public function test_analytics_shows_search_user_attribution(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics', ['tab' => 'searches']))
            ->assertOk()
            ->assertSee('Searches by user')
            ->assertSee('Jane Member')
            ->assertSee('jane@example.com');
    }

    public function test_analytics_feedback_manager_views(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics', ['tab' => 'feedback']))
            ->assertOk()
            ->assertSee('By search query')
            ->assertSee('hip mobility for runners')
            ->assertSee('Distinct queries')
            ->assertSee('All namespaces');

        $this->get(route('ai-search.analytics', ['tab' => 'feedback', 'feedback_tab' => 'detail']))
            ->assertOk()
            ->assertSee(route('videos.show', 81), false)
            ->assertSee('Hip Mobility Flow');
    }

    public function test_analytics_feedback_namespace_filter(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics', [
            'tab' => 'feedback',
            'feedback_namespace' => 'v6_title_tags',
            'feedback_tab' => 'overview',
            'feedback_days' => 30,
        ]))
            ->assertOk()
            ->assertSee('Showing ratings for namespace')
            ->assertSee('v6_title_tags')
            ->assertSee('feedback_namespace=v6_title_tags', false);
    }

    public function test_analytics_tab_nav_preserves_params(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics', ['user_email' => 'jane@example.com']))
            ->assertOk()
            ->assertSee('tab=overview', false)
            ->assertSee('tab=searches', false)
            ->assertSee('tab=feedback', false);
    }

    public function test_analytics_view_searches_link_includes_tab(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.analytics', ['tab' => 'searches']))
            ->assertOk()
            ->assertSee('tab=searches', false)
            ->assertSee('user_email=jane', false);
    }
}
