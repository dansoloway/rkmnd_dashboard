@extends('layouts.guest')

@section('title', 'Login')
@section('subtitle', 'Sign in to your account')

@section('content')
<form class="mt-8 space-y-6" method="POST" action="{{ route('login') }}">
    @csrf

    <div class="rounded-md shadow-sm -space-y-px">
        <div>
            <label for="email" class="sr-only">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="email"
                required
                class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                placeholder="Email address"
                value="{{ old('email') }}"
            >
        </div>
        <div>
            <label for="password" class="sr-only">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                placeholder="Password"
            >
        </div>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <input
                id="remember"
                name="remember"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <label for="remember" class="ml-2 block text-sm text-gray-900">
                Remember me
            </label>
        </div>
        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-500">
            Forgot password?
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-md bg-red-50 p-4">
            <h3 class="text-sm font-medium text-red-800">
                {{ $errors->first() }}
            </h3>
        </div>
    @endif

    <div>
        <button
            type="submit"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition"
        >
            Sign in
        </button>
    </div>
</form>
@endsection
