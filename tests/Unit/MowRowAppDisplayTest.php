<?php

namespace Tests\Unit;

use App\Support\MowRowAppDisplay;
use PHPUnit\Framework\TestCase;

class MowRowAppDisplayTest extends TestCase
{
    public function test_move_video_card_and_details(): void
    {
        $sections = MowRowAppDisplay::previewSections([
            'title' => 'Hip Flow',
            'content_label' => 'Move',
            'content_pillar' => 'move',
            'run_time' => '00:12:30',
            'long_description' => 'A guided hip mobility flow.',
            'scheduled_acf' => [
                'scheduled_content_type' => 'move',
                'move' => [
                    'move_type' => 'Mobility',
                    'therapeutic_application' => 'Opens the hips for better gait',
                ],
            ],
        ]);

        $this->assertSame('Move', $sections['card'][0]['value']);
        $this->assertSame('Opens the hips for better gait.', $sections['card'][2]['value']);
        $this->assertSame('Mobility', $sections['details'][0]['value']);
    }

    public function test_breathe_video_uses_short_description_and_taxonomy(): void
    {
        $sections = MowRowAppDisplay::previewSections([
            'title' => 'Breath Practice',
            'post_type' => 'video',
            'video_category' => 'Body By Breath',
            'content_pillar' => 'breathe',
            'short_description' => 'Calm the nervous system',
            'instructor' => 'Jill Miller',
            'body_area' => 'Core',
        ]);

        $this->assertSame('Calm the nervous system.', $sections['card'][2]['value']);
        $this->assertSame('Jill Miller', $sections['details'][0]['value']);
        $this->assertSame('From the Book: Body By Breath', $sections['details'][count($sections['details']) - 1]['value']);
    }

    public function test_breathe_video_prefers_mbr_pwa_fields(): void
    {
        $sections = MowRowAppDisplay::previewSections([
            'title' => 'Vocalize Your Vagus',
            'post_type' => 'video',
            'video_category' => 'Body By Breath',
            'content_pillar' => 'breathe',
            'short_description' => 'Legacy short',
            'instructor' => 'Jill Miller',
            'props' => 'Massage ball',
            'mbr_pwa' => [
                'focus_area' => 'Voice',
                'book_location' => '89',
                'great_for' => 'Activate the vagus nerve',
                'props' => 'Bolster',
                'related_products' => 'coregeous_ball',
            ],
            'mbr_related_products' => 'coregeous_ball',
        ]);

        $byLabel = [];
        foreach ($sections['details'] as $row) {
            $byLabel[$row['label']] = $row['value'];
        }

        $this->assertSame('Voice', $byLabel['Focus area'] ?? null);
        $this->assertSame('89', $byLabel['Book location'] ?? null);
        $this->assertSame('Bolster', $byLabel['Props'] ?? null);
        $this->assertSame('coregeous_ball', $byLabel['Related products'] ?? null);
        $this->assertNotContains('Massage ball', array_column($sections['details'], 'value'));
    }

    public function test_pillar_override_prefers_roll_acf_group(): void
    {
        $sections = MowRowAppDisplay::previewSections([
            'title' => 'BBB segment',
            'mow_row_content_pillar' => 'roll',
            'scheduled_content_type' => 'move',
            'scheduled_acf' => [
                'scheduled_content_type' => 'move',
                'mbr_related_products' => 'alpha_ball',
                'mbr_props' => 'Wall',
                'weekly' => [
                    'techniques' => 'Cross-fiber',
                    'therapeutic_application' => 'Releases shoulder tension',
                ],
            ],
            'mbr_related_products' => 'alpha_ball',
            'mbr_props' => 'Wall',
        ]);

        $this->assertSame('Releases shoulder tension.', $sections['card'][2]['value']);
        $this->assertSame('Cross-fiber', $sections['details'][0]['value']);

        $byLabel = [];
        foreach ($sections['details'] as $row) {
            $byLabel[$row['label']] = $row['value'];
        }
        $this->assertSame('Wall', $byLabel['Props'] ?? null);
        $this->assertSame('alpha_ball', $byLabel['Related products'] ?? null);
    }
}
