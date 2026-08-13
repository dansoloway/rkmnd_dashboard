@extends('layouts.app')

@section('content')
@php
    use App\Models\User;
@endphp
<div class="space-y-6 max-w-2xl mx-auto">
    <div>
        <a href="{{ route('users.index') }}" class="text-sm text-blue-600 hover:underline">&larr; Back to users</a>
        <h1 class="mt-2 text-3xl font-heading font-bold text-gray-900">Edit user</h1>
        <p class="mt-2 text-gray-600 text-sm">{{ $user->email }}</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                <select id="role" name="role" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('role') border-red-500 @enderror"
                        @if($user->id === auth()->id()) disabled @endif>
                    @foreach($assignableRoles as $role)
                        <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>
                            {{ User::roleLabel($role) }}
                        </option>
                    @endforeach
                </select>
                @if($user->id === auth()->id())
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    <p class="mt-1 text-xs text-gray-500">You cannot change your own role.</p>
                @endif
                @error('role')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-gray-100 pt-4">
                <p class="text-sm text-gray-600 mb-3">Leave password blank to keep the current password.</p>
                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                        <input type="password" id="password" name="password"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('users.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                    Save changes
                </button>
            </div>
        </form>
    </div>

    @if($user->id !== auth()->id() && auth()->user()->canManage($user) && ! session('impersonator_id'))
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-sm font-medium text-gray-900">Impersonate</h2>
        <p class="mt-1 text-sm text-gray-600">See the dashboard exactly as this person does. A banner lets you switch back.</p>
        <form method="POST" action="{{ route('users.impersonate', $user) }}" class="mt-4">
            @csrf
            <button type="submit" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                Impersonate {{ $user->name }}
            </button>
        </form>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-sm font-medium text-gray-900">Email a reset link</h2>
        <p class="mt-1 text-sm text-gray-600">Sends a password reset email. They choose a new password from the link — you do not need to set one here.</p>
        <form method="POST" action="{{ route('users.send-reset-link', $user) }}" class="mt-4">
            @csrf
            <button type="submit" class="px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                Send password reset email
            </button>
        </form>
    </div>
</div>
@endsection
