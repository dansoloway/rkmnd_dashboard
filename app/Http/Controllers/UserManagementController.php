<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $admin = Auth::user();

        $users = User::query()
            ->where('tenant_id', $admin->tenant_id)
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'users' => $users,
            'assignableRoles' => $admin->assignableRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = Auth::user();
        $assignable = $admin->assignableRoles();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in($assignable)],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'tenant_id' => $admin->tenant_id,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created.');
    }

    public function edit(User $user): View|RedirectResponse
    {
        $admin = Auth::user();

        if ($redirect = $this->guardTenantAccess($admin, $user)) {
            return $redirect;
        }

        if (! $this->canManage($admin, $user)) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot edit this user.');
        }

        return view('users.edit', [
            'user' => $user,
            'assignableRoles' => $admin->assignableRoles(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $admin = Auth::user();

        if ($redirect = $this->guardTenantAccess($admin, $user)) {
            return $redirect;
        }

        if (! $this->canManage($admin, $user)) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot edit this user.');
        }

        $assignable = $admin->assignableRoles();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in($assignable)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];

        $validated = $request->validate($rules);

        if ($user->id === $admin->id && $validated['role'] !== $admin->role) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'You cannot change your own role.');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $admin = Auth::user();

        if ($redirect = $this->guardTenantAccess($admin, $user)) {
            return $redirect;
        }

        if ($user->id === $admin->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        if (! $this->canManage($admin, $user)) {
            return redirect()
                ->route('users.index')
                ->with('error', 'You cannot delete this user.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted.');
    }

    private function guardTenantAccess(User $admin, User $user): ?RedirectResponse
    {
        if ((int) $user->tenant_id !== (int) $admin->tenant_id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'User not found.');
        }

        return null;
    }

    /**
     * Admins may not manage superadmins unless they are superadmin.
     */
    private function canManage(User $admin, User $target): bool
    {
        if ($target->isSuperAdmin() && ! $admin->isSuperAdmin()) {
            return false;
        }

        return true;
    }
}
