<?php

namespace App\Support;

use Illuminate\Http\Request;

class ProductContext
{
    public const AI_SEARCH = 'ai_search';

    public const MOW_ROW = 'mow_row';

    /**
     * @return list<string>
     */
    public static function ids(): array
    {
        return array_keys(config('products', []));
    }

    public static function current(?Request $request = null): ?string
    {
        $request ??= request();
        if ($request === null) {
            return null;
        }

        $route = $request->route();
        if ($route !== null) {
            $fromRoute = $route->defaults['product'] ?? null;
            if (is_string($fromRoute) && self::exists($fromRoute)) {
                return $fromRoute;
            }

            $name = $route->getName();
            if (is_string($name)) {
                if (str_starts_with($name, 'mow-row.')) {
                    return self::MOW_ROW;
                }
                if (str_starts_with($name, 'ai-search.')) {
                    return self::AI_SEARCH;
                }
            }
        }

        $query = $request->query('product');
        if (is_string($query) && self::exists($query)) {
            return $query;
        }

        return null;
    }

    public static function exists(string $id): bool
    {
        return is_array(config("products.{$id}"));
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(?string $id = null): array
    {
        $id ??= self::current() ?? self::AI_SEARCH;

        if (! self::exists($id)) {
            $id = self::AI_SEARCH;
        }

        return config("products.{$id}", []);
    }

    public static function id(?string $override = null): string
    {
        if ($override !== null && self::exists($override)) {
            return $override;
        }

        return self::current() ?? self::AI_SEARCH;
    }

    public static function label(?string $id = null): string
    {
        $cfg = self::config($id);

        return (string) ($cfg['label'] ?? 'Product');
    }

    public static function defaultNamespace(?string $id = null): string
    {
        $cfg = self::config($id);

        return (string) ($cfg['default_namespace'] ?? config('backend.default_search_namespace', 'v6_title_tags'));
    }

    /**
     * @return list<string>
     */
    public static function namespaceAllowList(?string $id = null): array
    {
        $cfg = self::config($id);
        $list = $cfg['namespace_allow_list'] ?? [];

        return is_array($list) ? array_values($list) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function catalogFilters(?string $id = null): array
    {
        $cfg = self::config($id);
        $filters = $cfg['catalog_filters'] ?? [];

        return is_array($filters) ? $filters : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function searchPoolFilters(?string $id = null): array
    {
        $cfg = self::config($id);
        $filters = $cfg['search_pool_filters'] ?? [];

        return is_array($filters) ? $filters : [];
    }

    public static function searchPostType(?string $id = null): ?string
    {
        $cfg = self::config($id);
        $pt = $cfg['search_post_type'] ?? null;

        return is_string($pt) && $pt !== '' ? $pt : null;
    }

    public static function libraryRoute(?string $id = null): string
    {
        $id ??= self::id();

        return $id === self::MOW_ROW ? 'mow-row.catalog' : 'ai-search.videos.index';
    }

    public static function searchRoute(?string $id = null): string
    {
        $id ??= self::id();

        return $id === self::MOW_ROW ? 'mow-row.search.index' : 'ai-search.playground.index';
    }

    public static function namespaceStudioRoute(?string $id = null): string
    {
        $id ??= self::id();

        return $id === self::MOW_ROW ? 'mow-row.namespace-studio' : 'ai-search.namespace-studio';
    }

    public static function analyticsRoute(?string $id = null): string
    {
        $id ??= self::id();

        return $id === self::MOW_ROW ? 'mow-row.analytics' : 'ai-search.analytics';
    }

    public static function routeName(string $suffix): string
    {
        $prefix = self::id() === self::MOW_ROW ? 'mow-row' : 'ai-search';

        return "{$prefix}.{$suffix}";
    }

    /**
     *
     * @param  array<string, mixed>  $video
     */
    public static function inferFromVideo(array $video): string
    {
        $postType = (string) ($video['post_type'] ?? '');
        $sct = strtolower(trim((string) ($video['scheduled_content_type'] ?? '')));
        if ($postType === 'scheduled' && in_array($sct, ['move', 'weekly'], true)) {
            return self::MOW_ROW;
        }

        $namespaces = (string) ($video['embedding_namespaces'] ?? '');
        if (str_contains($namespaces, 'mow_row_v6_title_tags') && $postType === 'scheduled') {
            return self::MOW_ROW;
        }

        return self::AI_SEARCH;
    }

    /**
     * @param  list<string>  $allNamespaces
     * @return list<string>
     */
    public static function filterNamespaces(array $allNamespaces, ?string $id = null): array
    {
        $allow = self::namespaceAllowList($id);
        if ($allow === []) {
            return $allNamespaces;
        }

        $filtered = array_values(array_intersect($allNamespaces, $allow));
        if ($filtered !== []) {
            return $filtered;
        }

        return $allow;
    }
}
