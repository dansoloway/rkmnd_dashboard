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
}
