@extends('layouts.guest')

@section('title', 'Forgot password')
@section('subtitle', 'We’ll email you a link to reset your password')

@section('content')
<form class="mt-8 space-y-6" method="POST" action="{{ route('password.email') }}">
    @csrf

    <div>
        <label for="email" class="sr-only">Email address</label>
        <input
            id="email"
            name="email"
            type="email"
            autocomplete="email"
            required
            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Email address"
            value="{{ old('email') }}"
        >
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
            Send reset link
        </button>
    </div>

    <p class="text-center text-sm text-gray-600">
        <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-500">Back to sign in</a>
    </p>
</form>
@endsection
