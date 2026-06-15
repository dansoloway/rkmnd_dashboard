<?php

namespace Tests\Feature;

class DashboardAndAccountTest extends FeatureTestCase
{
    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertSee('AI Video Search')
            ->assertSee('MOW/ROW PWA');
    }

    public function test_account_page_renders_for_authenticated_user(): void
    {
        $user = $this->actingAsTenantUser();

        $this->get(route('account.index'))
            ->assertOk()
            ->assertViewIs('account.index')
            ->assertSee($user->email);
    }

    public function test_ai_search_page_renders(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('ai-search.playground.index'))
            ->assertOk()
            ->assertViewIs('ai-search.index');
    }

    public function test_sync_logs_page_renders(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('sync-logs.index'))
            ->assertOk();
    }
}
