<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = Auth::user();

        if ($request->session()->has(self::SESSION_KEY)) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Stop impersonating before switching to someone else.');
        }

        if ($user->id === $admin->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot impersonate yourself.');
        }

        if ((int) $user->tenant_id !== (int) $admin->tenant_id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'User not found.');
        }

        if (! $admin->canManage($user)) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot impersonate this user.');
        }

        $adminId = $admin->id;
        Auth::login($user);
        $request->session()->put(self::SESSION_KEY, $adminId);
        $this->syncTenantSession($user);

        if ($user->isAnalyticsOnly()) {
            return redirect()->route('ai-search.analytics');
        }

        return redirect()->route('dashboard');
    }

    public function stop(Request $request): RedirectResponse
    {
        $adminId = $request->session()->pull(self::SESSION_KEY);

        if (! $adminId) {
            return redirect()->route('dashboard');
        }

        $admin = User::find($adminId);

        if (! $admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        Auth::login($admin);
        $this->syncTenantSession($admin);

        return redirect()
            ->route('users.index')
            ->with('success', 'Stopped impersonating. You are back to your admin account.');
    }

    private function syncTenantSession(User $user): void
    {
        if ($user->tenant) {
            session([
                'tenant_api_key' => $user->tenant->api_key,
                'tenant_name' => $user->tenant->display_name,
            ]);
        }
    }
}
