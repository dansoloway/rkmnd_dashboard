@php
    use App\Support\Navigation;

    $navUser = Navigation::user();
@endphp

@auth
    <div class="hidden md:flex md:items-center">
        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                @click="open = !open"
                class="inline-flex items-center px-3 py-2 text-sm font-medium {{ Navigation::userMenuActive() ? 'text-blue-700' : 'text-gray-700' }} hover:text-gray-900 rounded-md hover:bg-gray-50"
                :aria-expanded="open"
            >
                {{ Auth::user()->name }}
                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div
                x-show="open"
                x-transition
                x-cloak
                class="absolute right-0 z-50 mt-1 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
            >
                @foreach ($navUser as $userItem)
                    <a
                        href="{{ route($userItem['route']) }}"
                        class="block px-4 py-2 text-sm {{ Navigation::isActive($userItem['active'] ?? [$userItem['route']]) ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}"
                        @click="open = false"
                    >
                        {{ $userItem['label'] }}
                    </a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100 mt-1 pt-1">
                    @csrf
                    <button
                        type="submit"
                        class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                    >
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
@endauth
