<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description ?? 'Accreditation and activity monitoring for civil society organizations in Lingayen, Pangasinan.' }}">
    <title>{{ isset($title) ? $title . ' | Lingayen CivicLink' : 'Lingayen CivicLink' }}</title>
    {{-- Public pages run at motion 5; set before first paint so revealed content never flashes. --}}
    <script>
        if ('IntersectionObserver' in window && ! matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('motion-ok');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-sticky
              focus:rounded-md focus:bg-navy-900 focus:px-4 focus:py-2 focus:text-sm
              focus:font-semibold focus:text-paper">
        Skip to main content
    </a>

    <header class="bg-navy-900 text-paper">
        <div class="mx-auto flex max-w-7xl items-center gap-6 px-4 py-3 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 rounded-sm focus-visible:outline-rose-400">
                <x-civic-mark />
                <span class="text-base font-semibold tracking-tight">Lingayen CivicLink</span>
            </a>

            <nav aria-label="Primary" class="ml-auto hidden items-center gap-1 md:flex">
                @php
                    $links = [
                        'home' => 'Home',
                        'about' => 'About Us',
                        'accreditation' => 'Accreditation',
                        'directory' => 'Accredited CSOs',
                        'resources' => 'Resources',
                    ];
                @endphp
                @foreach ($links as $route => $label)
                    <a href="{{ route($route) }}"
                       @if (request()->routeIs($route)) aria-current="page" @endif
                       class="rounded-sm px-3 py-2 text-sm font-medium transition-colors duration-fast ease-out-strong
                              hover:bg-navy-800 focus-visible:outline-rose-400
                              {{ request()->routeIs($route)
                                  ? 'text-paper shadow-[inset_0_-2px_0_0_theme(colors.rose.500)]'
                                  : 'text-navy-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-2 md:ml-0">
                <a href="{{ route('contact') }}"
                   class="hidden rounded-md px-3 py-2 text-sm font-medium text-navy-200
                          transition-colors duration-fast ease-out-strong hover:bg-navy-800
                          focus-visible:outline-rose-400 sm:inline-block">
                    Contact Us
                </a>
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="rounded-md bg-rose-500 px-3.5 py-2 text-sm font-semibold text-navy-900
                              transition-colors duration-fast ease-out-strong hover:bg-rose-400
                              focus-visible:outline-rose-400">
                        My dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="rounded-md px-3 py-2 text-sm font-medium text-navy-200
                              transition-colors duration-fast ease-out-strong hover:bg-navy-800
                              focus-visible:outline-rose-400">
                        Log in
                    </a>
                    <a href="{{ route('register') }}"
                       class="rounded-md bg-rose-500 px-3.5 py-2 text-sm font-semibold text-navy-900
                              transition-colors duration-fast ease-out-strong hover:bg-rose-400
                              focus-visible:outline-rose-400">
                        Apply Now
                    </a>
                @endauth
            </div>
        </div>

        <nav aria-label="Primary mobile" class="border-t border-navy-800 md:hidden">
            <div class="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 py-2">
                @foreach ($links as $route => $label)
                    <a href="{{ route($route) }}"
                       @if (request()->routeIs($route)) aria-current="page" @endif
                       class="whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium
                              focus-visible:outline-rose-400
                              {{ request()->routeIs($route) ? 'bg-navy-800 text-paper' : 'text-navy-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </nav>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-line bg-surface">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
            <div>
                <div class="flex items-center gap-2.5">
                    <x-civic-mark class="h-8 w-8 text-sm" />
                    <span class="font-semibold text-ink">Lingayen CivicLink</span>
                </div>
                <p class="mt-3 max-w-xs text-sm text-muted">
                    Accreditation and activity monitoring for civil society organizations partnering
                    with the Municipality of Lingayen, Pangasinan.
                </p>
            </div>

            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-muted">Pages</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($links as $route => $label)
                        <li><a href="{{ route($route) }}" class="text-navy-700 hover:text-navy-900 hover:underline">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-muted">Legal basis</h2>
                <p class="mt-3 text-sm text-muted">
                    RA 7160 (Local Government Code of 1991) and DILG Memorandum Circular 2022-083 on
                    CSO accreditation for Local Special Bodies.
                </p>
                <p class="mt-4 text-sm text-muted">
                    {{ config('office.office.name') }}<br>
                    {{ config('office.office.address') }}<br>
                    {{ config('office.office.hours') }}
                </p>
            </div>
        </div>
        <div class="border-t border-line">
            <p class="mx-auto max-w-7xl px-4 py-4 text-xs text-muted sm:px-6 lg:px-8">
                &copy; {{ now()->year }} Municipality of Lingayen. Demonstration build.
            </p>
        </div>
    </footer>
</body>
</html>
