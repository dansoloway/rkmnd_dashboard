<?php

namespace App\Support;

use App\Models\User;

class Navigation
{
    /**
     * @param  array<int, string>|string  $patterns
     */
    public static function isActive(array|string $patterns): bool
    {
        $patterns = is_array($patterns) ? $patterns : [$patterns];

        foreach ($patterns as $pattern) {
            if (request()->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function primary(): array
    {
        $user = auth()->user();

        if ($user && $user->isAnalyticsOnly()) {
            return [
                [
                    'label' => 'Analytics',
                    'route' => 'ai-search.analytics',
                    'active' => ['ai-search.analytics*', 'mow-row.analytics*', 'analytics.*'],
                ],
                [
                    'label' => 'Account',
                    'route' => 'account.index',
                    'active' => ['account.*'],
                ],
            ];
        }

        return self::filterForUser(config('navigation.primary', []), $user);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function filterForUser(array $items, ?User $user): array
    {
        $out = [];

        foreach ($items as $item) {
            if (! empty($item['admin_only']) && (! $user || ! $user->isAdmin())) {
                continue;
            }

            if (! empty($item['children']) && is_array($item['children'])) {
                $item['children'] = self::filterForUser($item['children'], $user);
                if ($item['children'] === [] && empty($item['route'])) {
                    continue;
                }
            }

            $out[] = $item;
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function user(): array
    {
        return config('navigation.user', []);
    }

    public static function userMenuActive(): bool
    {
        foreach (self::user() as $item) {
            if (self::isActive($item['active'] ?? [$item['route']])) {
                return true;
            }
        }

        return false;
    }
}
