<?php

namespace App\Support;

/**
 * Builds the key/value fields shown in the Move Breathe Roll PWA (cards + video page).
 * Mirrors mow-row-pwa scheduled-acf-details, breathe-catalog-details, and video-card.
 */
class MowRowAppDisplay
{
    private const BOOK_LINE = 'From the Book: Body By Breath';

    /**
     * @param  array<string, mixed>  $video
     * @return array{
     *     card: list<array{label: string, value: string}>,
     *     video_page: list<array{label: string, value: string}>,
     *     details: list<array{label: string, value: string, html?: bool}>
     * }
     */
    public static function previewSections(array $video): array
    {
        $pillar = self::resolvePillar($video);
        $label = self::contentLabel($video, $pillar);
        $title = trim((string) ($video['title'] ?? 'Untitled'));
        $runtime = trim((string) ($video['run_time'] ?? $video['video_time'] ?? ''));
        $instructor = trim((string) ($video['instructor'] ?? ''));
        $cardPreview = self::cardPreviewText($video, $pillar);

        $card = [
            ['label' => 'Type', 'value' => $label !== '' ? $label : '—'],
            ['label' => 'Title', 'value' => $title !== '' ? $title : 'Untitled'],
        ];

        if ($cardPreview !== '') {
            $card[] = ['label' => 'Card preview', 'value' => $cardPreview];
        }

        if ($pillar !== 'breathe' && $runtime !== '') {
            $card[] = ['label' => 'Runtime', 'value' => $runtime];
        }

        $videoPage = [];

        if ($instructor !== '') {
            $videoPage[] = ['label' => 'Instructor', 'value' => $instructor];
        }

        if ($pillar !== 'breathe' && $runtime !== '' && $instructor === '') {
            $videoPage[] = ['label' => 'Runtime', 'value' => $runtime];
        } elseif ($pillar !== 'breathe' && $runtime !== '' && $instructor !== '') {
            // Shown inline on page as "Instructor · Runtime" — include combined line
            $videoPage[] = ['label' => 'Runtime (subtitle)', 'value' => $runtime];
        }

        $description = self::plainDescription($video);
        if ($description !== '') {
            $videoPage[] = ['label' => 'Description', 'value' => $description];
        }

        $details = self::isBreathe($video, $pillar)
            ? self::breatheDetailRows($video)
            : self::scheduledDetailRows($video, $pillar);

        return [
            'card' => $card,
            'video_page' => $videoPage,
            'details' => $details,
        ];
    }

    /**
     * Flat list for compact table display (section headers as pseudo-rows).
     *
     * @param  array<string, mixed>  $video
     * @return list<array{section: string, label: string, value: string, html?: bool}>
     */
    public static function flatPreviewRows(array $video): array
    {
        $sections = self::previewSections($video);
        $rows = [];

        foreach (['card' => 'Search card', 'video_page' => 'Video page', 'details' => 'Details'] as $key => $heading) {
            $items = $sections[$key] ?? [];
            if ($items === []) {
                continue;
            }
            foreach ($items as $item) {
                $rows[] = [
                    'section' => $heading,
                    'label' => $item['label'],
                    'value' => $item['value'],
                    'html' => $item['html'] ?? false,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $video
     */
    public static function resolvePillar(array $video): string
    {
        $stored = strtolower(trim((string) ($video['mow_row_content_pillar'] ?? '')));
        if (in_array($stored, ['move', 'roll', 'breathe'], true)) {
            return $stored;
        }

        $pillar = strtolower(trim((string) ($video['content_pillar'] ?? '')));
        if (in_array($pillar, ['move', 'roll', 'breathe'], true)) {
            return $pillar;
        }

        $sct = strtolower(trim((string) ($video['scheduled_content_type'] ?? '')));
        if ($sct === 'move') {
            return 'move';
        }
        if ($sct === 'weekly') {
            return 'roll';
        }
        if (($video['post_type'] ?? '') === 'video') {
            return 'breathe';
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $video
     */
    private static function contentLabel(array $video, string $pillar): string
    {
        $label = trim((string) ($video['content_label'] ?? ''));
        if ($label !== '') {
            return $label;
        }

        return match ($pillar) {
            'move' => 'Move',
            'roll' => 'Roll',
            'breathe' => 'Breathe',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $video
     */
    private static function isBreathe(array $video, string $pillar): bool
    {
        if ($pillar === 'breathe') {
            return true;
        }

        $label = strtolower(trim((string) ($video['content_label'] ?? '')));

        return in_array($label, ['breathe', 'body by breath'], true)
            || self::hasBodyByBreathCategory($video);
    }

    /**
     * @param  array<string, mixed>  $video
     */
    private static function hasBodyByBreathCategory(array $video): bool
    {
        $category = trim((string) ($video['video_category'] ?? ''));

        return $category === 'Body By Breath' || str_contains($category, 'Body By Breath');
    }

    /**
     * @param  array<string, mixed>  $video
     */
    private static function cardPreviewText(array $video, string $pillar): string
    {
        if (self::isBreathe($video, $pillar)) {
            $short = self::normalizeText(strip_tags((string) ($video['short_description'] ?? '')));

            return self::ensureTrailingPeriod($short);
        }

        $acf = $video['scheduled_acf'] ?? null;
        if (! is_array($acf)) {
            return '';
        }

        $groupKey = self::acfGroupKey($video, $pillar, $acf);
        $group = is_array($acf[$groupKey] ?? null) ? $acf[$groupKey] : [];
        $therapeutic = self::normalizeText((string) ($group['therapeutic_application'] ?? ''));

        return self::ensureTrailingPeriod($therapeutic);
    }

    /**
     * @param  array<string, mixed>  $video
     */
    private static function plainDescription(array $video): string
    {
        $long = self::normalizeText(strip_tags((string) ($video['long_description'] ?? '')));
        if ($long !== '') {
            return $long;
        }

        return self::normalizeText(strip_tags((string) ($video['short_description'] ?? '')));
    }

    /**
     * @param  array<string, mixed>  $acf
     * @return list<array{label: string, value: string, html?: bool}>
     */
    private static function scheduledDetailRows(array $video, string $pillar): array
    {
        $acf = $video['scheduled_acf'] ?? null;
        if (! is_array($acf)) {
            return [];
        }

        $groupKey = self::acfGroupKey($video, $pillar, $acf);
        $group = is_array($acf[$groupKey] ?? null) ? $acf[$groupKey] : [];

        $labels = $groupKey === 'move'
            ? [
                'move_type' => 'Move type',
                'key_areas_of_focus' => 'Key area of focus',
                'great_for' => 'Great for',
                'therapeutic_application' => 'Application',
                'tips' => 'Tips',
                'products_used' => 'Products used',
            ]
            : [
                'techniques' => 'Technique',
                'suggested_therapy_ball' => 'Suggested therapy ball',
                'body_part' => 'Body part',
                'performance_benefits' => 'Performance benefits',
                'therapeutic_application' => 'Application',
                'tips' => 'Tips',
                'products_used' => 'Products used',
            ];

        $rows = [];
        foreach ($labels as $key => $label) {
            $value = self::normalizeText((string) ($group[$key] ?? ''));
            if ($value !== '') {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        $mbrProps = self::normalizeText((string) ($video['mbr_props'] ?? $acf['mbr_props'] ?? ''));
        if ($mbrProps !== '') {
            $rows[] = ['label' => 'Props', 'value' => $mbrProps];
        }

        $related = self::normalizeText((string) ($video['mbr_related_products'] ?? $acf['mbr_related_products'] ?? ''));
        if ($related !== '') {
            $rows[] = ['label' => 'Related products', 'value' => $related];
        }

        foreach (['video_title' => 'Video title', 'video_note' => 'Note'] as $key => $label) {
            $value = self::normalizeText((string) ($acf[$key] ?? ''));
            if ($value === '' || ($key === 'video_title' && self::isOnlineClassPrefix($value))) {
                continue;
            }
            $rows[] = ['label' => $label, 'value' => $value];
        }

        $attribution = self::sourceAttribution($video, $acf, false);
        if ($attribution !== null) {
            $rows[] = $attribution;
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $video
     * @return list<array{label: string, value: string, html?: bool}>
     */
    private static function breatheDetailRows(array $video): array
    {
        $rows = [];
        $mbr = is_array($video['mbr_pwa'] ?? null) ? $video['mbr_pwa'] : null;

        if (is_array($mbr)) {
            foreach ([
                'focus_area' => 'Focus area',
                'great_for' => 'Great for',
                'application' => 'Application',
                'tips' => 'Tips',
            ] as $key => $label) {
                $value = self::normalizeText((string) ($mbr[$key] ?? ''));
                if ($value !== '') {
                    $rows[] = ['label' => $label, 'value' => $value];
                }
            }

            $mbrProps = self::normalizeText((string) ($mbr['props'] ?? $video['mbr_props'] ?? ''));
            if ($mbrProps !== '') {
                $rows[] = ['label' => 'Props', 'value' => $mbrProps];
            }

            $related = self::normalizeText((string) ($video['mbr_related_products'] ?? $mbr['related_products'] ?? ''));
            if ($related !== '') {
                $rows[] = ['label' => 'Related products', 'value' => $related];
            }
        }

        foreach ([
            'body_area' => 'Body area',
            'helps_with' => 'Helps with',
        ] as $key => $label) {
            $value = self::normalizeText((string) ($video[$key] ?? ''));
            if ($value !== '') {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        if (! is_array($mbr) || self::normalizeText((string) ($mbr['props'] ?? $video['mbr_props'] ?? '')) === '') {
            $legacyProps = self::normalizeText((string) ($video['props'] ?? ''));
            if ($legacyProps !== '') {
                $rows[] = ['label' => 'Props', 'value' => $legacyProps];
            }
        }

        foreach ([
            'video_topic' => 'Topic',
            'content_tags' => 'Tags',
        ] as $key => $label) {
            $value = self::normalizeText((string) ($video[$key] ?? ''));
            if ($value !== '') {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        $attribution = self::sourceAttribution($video, null, true);
        if ($attribution !== null) {
            $rows[] = $attribution;
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>|null  $acf
     * @return array{label: string, value: string, html?: bool}|null
     */
    private static function sourceAttribution(array $video, ?array $acf, bool $breathe): ?array
    {
        if ($breathe) {
            $pages = self::formatBookPages(self::bookLocation($video));
            $value = $pages === ''
                ? self::BOOK_LINE
                : self::BOOK_LINE.', '.$pages;

            return ['label' => '', 'value' => $value];
        }

        if (! is_array($acf)) {
            return null;
        }

        $editor = trim((string) ($acf['video_editor'] ?? ''));
        if ($editor === '') {
            return null;
        }

        if (preg_match('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $editor, $matches)) {
            $name = self::normalizeText(strip_tags($matches[2]));
            if ($name !== '') {
                return [
                    'label' => '',
                    'value' => 'From the Class: '.$name.' ('.$matches[1].')',
                ];
            }
        }

        $plain = self::normalizeText(strip_tags($editor));
        if ($plain === '') {
            return null;
        }

        if (preg_match('/MOVE\s+BREATH(?:E)?\s+ROLL\s*[-:]\s*(.+)/i', $plain, $matches)) {
            $name = self::normalizeText($matches[1]);
            if ($name !== '') {
                return ['label' => '', 'value' => 'From the Class: '.$name];
            }
        }

        return ['label' => '', 'value' => $plain];
    }

    /**
     * @param  array<string, mixed>  $acf
     */
    private static function acfGroupKey(array $video, string $pillar, array $acf): string
    {
        if ($pillar === 'roll') {
            return 'weekly';
        }
        if ($pillar === 'move') {
            return 'move';
        }

        $sct = strtolower(trim((string) ($acf['scheduled_content_type'] ?? $video['scheduled_content_type'] ?? '')));

        return $sct === 'weekly' ? 'weekly' : 'move';
    }

    /**
     * @param  array<string, mixed>  $video
     */
    private static function bookLocation(array $video): string
    {
        $mbr = $video['mbr_pwa'] ?? null;
        if (is_array($mbr)) {
            $location = trim((string) ($mbr['book_location'] ?? ''));
            if ($location !== '') {
                return $location;
            }
        }

        return trim((string) ($video['book_location'] ?? ''));
    }

    private static function formatBookPages(string $location): string
    {
        $location = trim(html_entity_decode($location, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($location === '') {
            return '';
        }

        if (preg_match('/^pp?\.\s*/i', $location)) {
            return preg_replace('/\s+/', ' ', $location) ?? $location;
        }

        if (preg_match('/[-–,;\/]| to /i', $location)) {
            return 'pp. '.$location;
        }

        return 'p. '.$location;
    }

    private static function isOnlineClassPrefix(string $videoTitle): bool
    {
        return (bool) preg_match('/^from\s+(the\s+)?online\s+class:?\s*$/i', trim($videoTitle));
    }

    private static function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($text === '') {
            return '';
        }

        $lines = explode("\n", $text);
        $lines = array_map(static fn (string $line): string => trim(preg_replace('/[ \t]+/', ' ', $line) ?? ''), $lines);

        return implode("\n", array_filter($lines, static fn (string $line): bool => $line !== ''));
    }

    private static function ensureTrailingPeriod(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        if (preg_match('/[.!?]$/u', $text)) {
            return $text;
        }

        return $text.'.';
    }
}
