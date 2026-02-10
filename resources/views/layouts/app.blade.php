<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'TuneUp Fitness AI Portal') }}</title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Configure Tailwind to use your fonts -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['assistant', 'ui-sans-serif', 'system-ui'],
                        'heading': ['secular', 'ui-sans-serif', 'system-ui'],
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts: Assistant and Secular -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Assistant:wght@300;400;600;700&family=Secular+One&display=swap" rel="stylesheet">
    
    <!-- Alpine.js for interactivity (dropdowns, modals, etc.) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Your custom CSS (external file) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
    @stack('head')
</head>
<body class="font-sans antialiased bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-sm font-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-gray-900 hover:text-blue-600">
                            {{ config('app.name', 'TuneUp Fitness') }}
                        </a>
                    </div>
                    
                    <!-- Navigation Links -->
                    <div class="hidden md:ml-8 md:flex md:space-x-6">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('dashboard') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('videos.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('videos.*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                            Videos
                        </a>
                        <a href="{{ route('videos.database') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('videos.database') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                            Video DB
                        </a>
                        <a href="{{ route('query.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('query.*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                            Query
                        </a>
                        <a href="{{ route('analytics.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('analytics.*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                            Analytics
                        </a>
                        <a href="{{ route('sync-logs.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('sync-logs.*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                            Sync Logs
                        </a>
                        <a href="{{ route('account.index') }}" class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('account.*') ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                            Account
                        </a>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center">
                    @auth
                        <span class="text-gray-700 mr-4 hidden md:block">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-500 hover:text-gray-700 transition">
                                Logout
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="md:hidden border-t border-gray-200">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    Dashboard
                </a>
                <a href="{{ route('videos.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('videos.index') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    Videos
                </a>
                <a href="{{ route('videos.database') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('videos.database') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    Video DB
                </a>
                <a href="{{ route('query.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('query.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    Query
                </a>
                <a href="{{ route('analytics.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('analytics.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    Analytics
                </a>
                <a href="{{ route('sync-logs.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('sync-logs.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    Sync Logs
                </a>
                <a href="{{ route('account.index') }}" class="block px-3 py-2 rounded-md text-base font-medium {{ request()->routeIs('account.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                    Account
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @yield('content')
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <p class="text-center text-gray-500 text-sm">
                &copy; {{ date('Y') }} TuneUp Fitness. All rights reserved.
            </p>
        </div>
    </footer>
    
    <!-- Your custom JavaScript -->
    <script src="{{ asset('js/app.js') }}"></script>
    
    @stack('scripts')
</body>
</html>

