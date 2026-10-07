<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description ?? 'Accreditation and activity monitoring for civil society organizations in Lingayen, Pangasinan.' }}">
    <title>{{ isset($title) ? $title . ' | Lingayen CivicLink' : 'Lingayen CivicLink' }}</title>
    {{-- Public pages run at motion 4; set before first paint so revealed content never flashes. --}}
    <script>
        if ('IntersectionObserver' in window && ! matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.documentElement.classList.add('motion-ok');
        }
    </script>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper">
    @php
        $links = [
            'home' => 'Home',
            'about' => 'About Us',
            'accreditation' => 'Accreditation',
            'directory' => 'Accredited CSOs',
            'resources' => 'Resources',
            'contact' => 'Contact',
        ];
    @endphp

    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-toast
              focus:rounded-md focus:bg-brand-900 focus:px-4 focus:py-2 focus:text-sm
              focus:font-semibold focus:text-paper">
        Skip to main content
    </a>

    {{-- Two tiers, after pcnc.com.ph: an ivory identity bar that scrolls away, and a navy menu bar that sticks. --}}
    {{-- The header sticks with a negative top equal to the identity bar's height (4.75rem + 1px border), so the
         identity bar scrolls away and only the maroon menu bar stays pinned. --}}
    <header x-data="{ open: false, stuck: false }" @keydown.escape.window="open = false"
            @scroll.window.passive="stuck = window.scrollY > 77" x-init="stuck = window.scrollY > 77"
            class="sticky -top-[calc(4.75rem+1px)] z-sticky">
        <div class="border-b border-line bg-paper">
            <div class="mx-auto flex h-[4.75rem] max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="brand-link flex items-center gap-3 rounded-sm">
                    <x-civic-mark class="civic-seal h-14 w-14" />
                    <span class="leading-tight">
                        <span class="brand-name block font-serif text-lg font-bold text-brand-800">Lingayen CivicLink</span>
                        <span class="block text-xs text-muted">Municipality of Lingayen, Pangasinan</span>
                    </span>
                </a>

                {{-- Social link and segmented utility buttons, as on pcnc.com.ph. --}}
                <div class="ml-auto hidden items-center gap-4 lg:flex">
                    <a href="{{ config('office.office.facebook') }}" target="_blank" rel="noopener"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-canvas text-brand-800
                              transition-colors duration-fast ease-out-strong hover:bg-facebook hover:text-on-primary
                              focus-visible:bg-facebook focus-visible:text-on-primary">
                        <x-phosphor-facebook-logo class="h-5 w-5" aria-hidden="true" />
                        <span class="sr-only">PESO Lingayen on Facebook</span>
                    </a>
                    <div class="flex divide-x divide-line rounded-sm bg-canvas text-sm">
                        @auth
                            <a href="{{ route('dashboard') }}" class="utility-link">My dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="utility-link">Log in</a>
                            <a href="{{ route('register') }}" class="utility-link utility-link-cta">Apply now</a>
                        @endauth
                    </div>
                </div>

                <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-controls="mobile-menu"
                        class="ml-auto inline-flex h-11 w-11 items-center justify-center rounded-md text-brand-800
                               hover:bg-brand-50 lg:hidden">
                    <x-phosphor-list x-show="! open" class="h-6 w-6" aria-hidden="true" />
                    <x-phosphor-x x-show="open" x-cloak class="h-6 w-6" aria-hidden="true" />
                    <span class="sr-only">Menu</span>
                </button>
            </div>
        </div>

        <nav aria-label="Primary" class="bg-brand-800 transition-shadow duration-base ease-out-strong" :class="{ 'shadow-overlay': stuck }">
            <div class="mx-auto hidden max-w-7xl px-4 sm:px-6 lg:block lg:px-8">
                <ul class="flex flex-wrap items-center justify-center">
                    @foreach ($links as $route => $label)
                        <li>
                            <a href="{{ route($route) }}"
                               @if (request()->routeIs($route, "{$route}.*")) aria-current="page" @endif
                               @class([
                                   'nav-link',
                                   'text-accent-400' => request()->routeIs($route, "{$route}.*"),
                                   'text-brand-100 hover:text-paper' => ! request()->routeIs($route, "{$route}.*"),
                               ])>{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div id="mobile-menu" x-show="open" x-cloak class="border-t border-brand-700 lg:hidden">
                <ul class="px-4 py-2">
                    @foreach ($links as $route => $label)
                        <li>
                            <a href="{{ route($route) }}"
                               @if (request()->routeIs($route, "{$route}.*")) aria-current="page" @endif
                               @class([
                                   'flex min-h-11 items-center text-sm font-semibold uppercase tracking-[0.06em]',
                                   'text-accent-400' => request()->routeIs($route, "{$route}.*"),
                                   'text-brand-100' => ! request()->routeIs($route, "{$route}.*"),
                               ])>{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
                <div class="flex gap-3 border-t border-brand-700 px-4 py-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-accent flex-1">My dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn flex-1 border border-brand-500 text-paper hover:bg-brand-700">Log in</a>
                        <a href="{{ route('register') }}" class="btn-accent flex-1">Apply now</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="bg-brand-900 text-brand-200">
        <div class="mx-auto max-w-7xl px-4 pb-12 pt-12 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <div class="inline-flex items-center gap-3">
                    <x-civic-mark class="h-20 w-20" />
                    <span class="font-serif text-2xl font-bold text-paper">Lingayen CivicLink</span>
                </div>
                <p class="mt-4 text-sm leading-relaxed">
                    Accreditation and activity monitoring for civil society organizations partnering with the
                    Municipality of Lingayen, Pangasinan, run by the {{ config('office.office.name') }} of the
                    Public Employment Service Office.
                </p>
            </div>

            <div class="mt-12 grid gap-8 border-t border-brand-700 pt-12 text-sm sm:grid-cols-3">
                <div>
                    <h2 class="font-semibold text-paper">Pages</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach ($links as $route => $label)
                            <li><a href="{{ route($route) }}" class="hover:text-paper hover:underline">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h2 class="font-semibold text-paper">Visit the office</h2>
                    <p class="mt-3 leading-relaxed">
                        {{ config('office.office.name') }}<br>
                        {{ config('office.office.address') }}<br>
                        {{ config('office.office.hours') }}
                    </p>
                    <p class="mt-3 leading-relaxed">
                        <a href="tel:{{ config('office.office.phone_link') }}" class="tabular-nums hover:text-paper hover:underline">{{ config('office.office.phone') }}</a><br>
                        <a href="mailto:{{ config('office.office.email') }}" class="break-all hover:text-paper hover:underline">{{ config('office.office.email') }}</a>
                    </p>
                </div>
                <div>
                    <h2 class="font-semibold text-paper">Legal basis</h2>
                    <p class="mt-3 leading-relaxed">
                        RA 7160 (Local Government Code of 1991) and DILG Memorandum Circular 2022-083 on CSO
                        accreditation for Local Special Bodies.
                    </p>
                </div>
            </div>

            <p class="mt-12 border-t border-brand-700 pt-6 text-xs text-brand-300">
                &copy; {{ now()->year }} Municipality of Lingayen. Demonstration build.
            </p>
        </div>
    </footer>
</body>
</html>
