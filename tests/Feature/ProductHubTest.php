<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;

class ProductHubTest extends FeatureTestCase
{
    public function test_dashboard_renders_product_hub_cards(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard')
            ->assertSee('AI Video Search')
            ->assertSee('MOW/ROW PWA')
            ->assertSee('Platform — WordPress sync')
            ->assertSee('Featured Move Week')
            ->assertSee('Featured Rollout Week');
    }

    public function test_mow_row_catalog_lists_namespace_videos(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('mow-row.catalog'))
            ->assertOk()
            ->assertViewIs('mow-row.catalog')
            ->assertSee('MOW Hip Flow')
            ->assertSee('ROW Shoulder Reset')
            ->assertSee('BBB Breath Practice')
            ->assertSee('mow_row_v6_title_tags')
            ->assertSee('mow-row-pillar-select', false)
            ->assertSee('>Roll</option>', false)
            ->assertSee('mow-row-preview-toggle', false)
            ->assertSee('Expand all app previews')
            ->assertSee('Opens the hips for better gait.', false)
            ->assertSee('Calm the nervous system with diaphragmatic breathing.', false)
            ->assertDontSee('Rollout', false);
    }

    public function test_mow_row_catalog_filters_move_vs_roll_client_side(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('mow-row.catalog', ['content_type' => 'move']))
            ->assertOk()
            ->assertSee('MOW Hip Flow')
            ->assertDontSee('ROW Shoulder Reset')
            ->assertDontSee('BBB Breath Practice');
    }

    public function test_mow_row_catalog_filters_breathe(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('mow-row.catalog', ['content_type' => 'breathe']))
            ->assertOk()
            ->assertSee('BBB Breath Practice')
            ->assertDontSee('MOW Hip Flow');
    }

    public function test_mow_row_catalog_content_pillar_patch(): void
    {
        $this->actingAsTenantUser();

        $this->patchJson(route('mow-row.catalog.content-pillar', ['id' => 10]), [
            'mow_row_content_pillar' => 'roll',
        ])
            ->assertOk()
            ->assertJsonPath('video.content_pillar', 'roll');
    }

    public function test_mow_row_featured_page_renders(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('mow-row.featured'))
            ->assertOk()
            ->assertViewIs('mow-row.featured')
            ->assertSee('Featured Move Week')
            ->assertSee('Featured Rollout Week');
    }

    public function test_legacy_video_library_redirects_to_ai_search(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('videos.index'))
            ->assertRedirect('/ai-search/videos');
    }

    public function test_mow_row_search_uses_mow_row_namespace_default(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('mow-row.search.index'))
            ->assertOk()
            ->assertViewIs('ai-search.index')
            ->assertSee('MOW/ROW PWA')
            ->assertSee('mow_row_v6_title_tags', false);
    }

    public function test_dashboard_catalog_api_uses_product_filters(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('dashboard'))->assertOk();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/api/v1/wordpress/videos')) {
                return false;
            }
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $qs);

            return ($qs['post_type'] ?? null) === 'video'
                || ($qs['embedding_namespace'] ?? null) === 'mow_row_v6_title_tags';
        });
    }
}
