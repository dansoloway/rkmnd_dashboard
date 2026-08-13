@php
    use App\Http\Controllers\ImpersonationController;
    use App\Models\User;

    $impersonatorId = session(ImpersonationController::SESSION_KEY);
    $impersonator = $impersonatorId ? User::find($impersonatorId) : null;
@endphp

@if($impersonator && auth()->check())
    <div class="bg-amber-500 text-amber-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex flex-wrap items-center justify-between gap-2 text-sm">
            <p>
                Viewing as <strong>{{ auth()->user()->name }}</strong>
                ({{ \App\Models\User::roleLabel(auth()->user()->role) }})
                — signed in as {{ $impersonator->name }}.
            </p>
            <form method="POST" action="{{ route('impersonate.stop') }}">
                @csrf
                <button type="submit" class="px-3 py-1 bg-white text-amber-950 rounded-md font-medium hover:bg-amber-50">
                    Return to my account
                </button>
            </form>
        </div>
    </div>
@endif
