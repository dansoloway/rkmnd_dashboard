@extends('layouts.app')

@section('content')
@php
    use App\Models\User;
@endphp
<div class="space-y-6 max-w-5xl mx-auto">
    <div>
        <h1 class="text-3xl font-heading font-bold text-gray-900">Users</h1>
        <p class="mt-2 text-gray-600 text-sm">
            Manage dashboard logins for your tenant. Analytics only can view queries and results. Managers get the full dashboard (including anatomy terms). Admins can also manage logins.
        </p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-2 font-medium">Name</th>
                    <th class="px-4 py-2 font-medium">Email</th>
                    <th class="px-4 py-2 font-medium">Role</th>
                    <th class="px-4 py-2 font-medium"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $u)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">
                            {{ $u->name }}
                            @if($u->id === auth()->id())
                                <span class="text-xs text-gray-500 font-normal">(you)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $u->email }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $u->role === User::ROLE_ANALYTICS ? 'bg-amber-100 text-amber-800' : '' }}
                                {{ $u->isAdmin() ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $u->role === User::ROLE_USER ? 'bg-gray-100 text-gray-700' : '' }}
                            ">
                                {{ User::roleLabel($u->role) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                            @if(! $u->isSuperAdmin() || auth()->user()->isSuperAdmin())
                                @if($u->id !== auth()->id() && ! session('impersonator_id'))
                                    <form method="POST" action="{{ route('users.impersonate', $u) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:underline">Impersonate</button>
                                    </form>
                                @endif
                                <a href="{{ route('users.edit', $u) }}" class="text-blue-600 hover:underline">Edit</a>
                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $u) }}" class="inline"
                                          onsubmit="return confirm('Delete {{ $u->email }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            @else
                                <span class="text-xs text-gray-400">Protected</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">No users yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-heading font-medium text-gray-900 mb-4">Add user</h2>

        <form method="POST" action="{{ route('users.store') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select id="role" name="role" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('role') border-red-500 @enderror">
                        @foreach($assignableRoles as $role)
                            <option value="{{ $role }}" @selected(old('role', User::ROLE_ANALYTICS) === $role)>
                                {{ User::roleLabel($role) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Analytics only = queries &amp; results. Manager = full dashboard. Admin = full + user management.</p>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                    Create user
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
