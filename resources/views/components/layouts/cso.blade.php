<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $header ?? 'My organization' }} | Lingayen CivicLink</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-sticky
              focus:rounded-md focus:bg-navy-900 focus:px-4 focus:py-2 focus:text-sm
              focus:font-semibold focus:text-paper">
        Skip to main content
    </a>

    <header class="bg-navy-900 text-paper">
        <div class="mx-auto flex max-w-5xl items-center gap-4 px-4 py-3 sm:px-6">
            <a href="{{ route('cso.dashboard') }}" class="flex items-center gap-2.5 rounded-sm focus-visible:outline-amber-400">
                <x-civic-mark class="h-8 w-8 text-sm" />
                <span class="text-sm font-semibold">CivicLink</span>
            </a>

            <nav aria-label="Organization" class="ml-4 flex items-center gap-1 overflow-x-auto">
                @php
                    $nav = [
                        'cso.dashboard' => 'Dashboard',
                        'cso.profile.edit' => 'Organization profile',
                        'cso.applications.index' => 'Applications',
                        'cso.activities.index' => 'Activities',
                    ];
                @endphp
                @foreach ($nav as $route => $label)
                    <a href="{{ Route::has($route) ? route($route) : '#' }}"
                       @if (request()->routeIs($route)) aria-current="page" @endif
                       class="whitespace-nowrap rounded-sm px-3 py-2 text-sm font-medium
                              transition-colors duration-fast ease-out-strong hover:bg-navy-800
                              focus-visible:outline-amber-400
                              {{ request()->routeIs($route)
                                  ? 'text-paper shadow-[inset_0_-2px_0_0_theme(colors.amber.500)]'
                                  : 'text-navy-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('profile.edit') }}"
                   class="hidden rounded-sm px-3 py-2 text-sm text-navy-200 hover:bg-navy-800
                          focus-visible:outline-amber-400 sm:inline-block">
                    {{ auth()->user()->name }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-sm px-3 py-2 text-sm text-navy-200 hover:bg-navy-800 focus-visible:outline-amber-400">
                        Log out
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main id="main" class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-ink">{{ $header ?? 'Dashboard' }}</h1>
            @isset($subheader)
                <p class="mt-1 text-sm text-muted">{{ $subheader }}</p>
            @endisset
        </div>

        <x-flash />
        {{ $slot }}
    </main>
</body>
</html>
