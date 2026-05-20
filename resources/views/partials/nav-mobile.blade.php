@php
    use App\Support\Navigation;

    $navPrimary = Navigation::primary();
    $navUser = Navigation::user();
@endphp

<div class="md:hidden border-t border-gray-200">
    <div class="px-2 pt-2 pb-3 space-y-1">
        @foreach ($navPrimary as $item)
            @if (! empty($item['children']))
                <div x-data="{ expanded: {{ Navigation::isActive($item['active']) ? 'true' : 'false' }} }">
                    <button
                        type="button"
                        @click="expanded = !expanded"
                        class="flex w-full items-center justify-between px-3 py-2 rounded-md text-base font-medium {{ Navigation::isActive($item['active']) ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}"
                    >
                        <span>{{ $item['label'] }}</span>
                        <svg class="h-5 w-5 transition-transform" :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="expanded" class="pl-4 space-y-1 mt-1">
                        @foreach ($item['children'] as $child)
                            <a
                                href="{{ route($child['route']) }}"
                                class="block px-3 py-2 rounded-md text-sm font-medium {{ Navigation::isActive($child['active'] ?? [$child['route']]) ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}"
                            >
                                {{ $child['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a
                    href="{{ route($item['route']) }}"
                    class="block px-3 py-2 rounded-md text-base font-medium {{ Navigation::isActive($item['active'] ?? [$item['route']]) ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}"
                >
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach

        @auth
            <div class="border-t border-gray-200 mt-2 pt-2">
                <p class="px-3 py-1 text-xs font-medium text-gray-500 uppercase tracking-wide">Account</p>
                @foreach ($navUser as $userItem)
                    <a
                        href="{{ route($userItem['route']) }}"
                        class="block px-3 py-2 rounded-md text-base font-medium {{ Navigation::isActive($userItem['active'] ?? [$userItem['route']]) ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}"
                    >
                        {{ $userItem['label'] }}
                    </a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}" class="px-3 py-2">
                    @csrf
                    <button type="submit" class="text-base font-medium text-gray-700 hover:text-gray-900">
                        Logout
                    </button>
                </form>
            </div>
        @endauth
    </div>
</div>
