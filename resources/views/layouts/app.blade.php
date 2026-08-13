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
    <style>[x-cloak] { display: none !important; }</style>
    
    @stack('head')
</head>
<body class="font-sans antialiased bg-gray-50">
    
    @include('partials.impersonation-banner')

    <!-- Navigation -->
    <nav class="bg-white shadow-sm font-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold text-gray-900 hover:text-blue-600">
                            {{ config('app.name', 'TuneUp Fitness') }}
                        </a>
                    </div>
                    @include('partials.nav-desktop')
                </div>
                @include('partials.nav-user-desktop')
            </div>
        </div>
        @include('partials.nav-mobile')
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

