<?php

namespace Tests\Feature;

class VideosTest extends FeatureTestCase
{
    public function test_guest_cannot_access_video_library(): void
    {
        $this->get(route('videos.index'))
            ->assertRedirect(route('login'));
    }

    public function test_video_library_renders(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('videos.index'))
            ->assertOk()
            ->assertViewIs('videos.index')
            ->assertSee('Test Video One');
    }

    public function test_metadata_explorer_renders(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('videos.database'))
            ->assertOk()
            ->assertViewIs('videos.database');
    }

    public function test_embeddings_reconcile_page_renders(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('videos.embeddings-reconcile'))
            ->assertOk()
            ->assertViewIs('videos.embeddings-reconcile');
    }

    public function test_video_detail_renders_when_api_returns_video(): void
    {
        $this->actingAsTenantUser();

        $this->get(route('videos.show', ['id' => 1]))
            ->assertOk()
            ->assertViewIs('videos.show');
    }
}
