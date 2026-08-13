<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictAnalyticsRole
{
    /**
     * Route name patterns analytics-only users may access.
     *
     * @var list<string>
     */
    private const ALLOWED = [
        'ai-search.analytics',
        'ai-search.analytics.search',
        'mow-row.analytics',
        'mow-row.analytics.search',
        'analytics.index',
        'analytics.search',
        'analytics.redirect',
        // Feedback posts from the analytics UI reuse these route names
        'ai-search.feedback',
        'ai-search.playground.feedback',
        'mow-row.search.feedback',
        'account.index',
        'account.update',
        'account.password',
        'impersonate.stop',
    ];

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAnalyticsOnly()) {
            return $next($request);
        }

        if ($request->routeIs(...self::ALLOWED)) {
            return $next($request);
        }

        return redirect()
            ->route('ai-search.analytics')
            ->with('error', 'Your account only has access to Analytics.');
    }
}
