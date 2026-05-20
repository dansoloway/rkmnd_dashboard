@php
    use App\Support\Navigation;

    $navPrimary = Navigation::primary();
@endphp

<div class="hidden md:ml-8 md:flex md:items-center md:space-x-1">
    @foreach ($navPrimary as $item)
        @if (! empty($item['children']))
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button
                    type="button"
                    @click="open = !open"
                    class="inline-flex items-center px-1 pt-1 border-b-2 h-16 {{ Navigation::isActive($item['active']) ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
                    :aria-expanded="open"
                >
                    {{ $item['label'] }}
                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div
                    x-show="open"
                    x-transition
                    x-cloak
                    class="absolute left-0 z-50 mt-0 w-56 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                >
                    @foreach ($item['children'] as $child)
                        <a
                            href="{{ route($child['route']) }}"
                            class="block px-4 py-2 text-sm {{ Navigation::isActive($child['active'] ?? [$child['route']]) ? 'bg-blue-50 text-blue-700' : 'text-gray-700 hover:bg-gray-50' }}"
                            @click="open = false"
                        >
                            {{ $child['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @else
            <a
                href="{{ route($item['route']) }}"
                class="inline-flex items-center px-1 pt-1 border-b-2 h-16 {{ Navigation::isActive($item['active'] ?? [$item['route']]) ? 'border-blue-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}"
            >
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</div>
