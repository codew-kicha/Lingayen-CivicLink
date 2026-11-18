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
        // Dropdown submenus: a page's own sections (each has an id on the page).
        $menus = [
            'about' => [
                ['Why CivicLink exists', route('about').'#why'],
                ['What CSOs are', route('about').'#what'],
                ['Their role in governance', route('about').'#purpose'],
                ['How this came to be', route('about').'#history'],
                ['Office officials', route('about').'#officials'],
            ],
            'accreditation' => [
                ['Why get accredited', route('accreditation').'#why'],
                ['Who can apply', route('accreditation').'#who'],
                ['How the review works', route('accreditation').'#how'],
                ['What you need to submit', route('accreditation').'#submit'],
                ['Register and apply', route('register')],
            ],
            'resources' => [
                ['News and updates', route('resources').'#news'],
                ['Annual reports', route('resources').'#reports'],
                ['Regulations', route('resources').'#regulations'],
                ['Frequently asked questions', route('resources').'#faq'],
            ],
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
                        <span class="brand-name block font-serif text-lg font-bold text-brand-800">CSO Desk Office</span>
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
                    <div class="flex items-center gap-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="header-login">My dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="header-login">Log in</a>
                            <a href="{{ route('register') }}" class="header-apply">Apply now</a>
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
                        @php $children = $menus[$route] ?? []; @endphp
                        <li @if ($children) class="relative" x-data="navMenu" @nav-menu-open.window="closeIfOther($event)" @mouseenter="show()" @mouseleave="hide()"
                            @focusin="show()" @focusout="leave($event)" @keydown.escape="open = false" @click.outside="open = false" @endif>
                            <a href="{{ route($route) }}"
                               @if (request()->routeIs($route, "{$route}.*")) aria-current="page" @endif
                               @if ($children) aria-haspopup="true" :aria-expanded="open.toString()" @click="tap($event)" @endif
                               @class([
                                   'nav-link',
                                   'text-accent-400' => request()->routeIs($route, "{$route}.*"),
                                   'text-brand-100 hover:text-paper' => ! request()->routeIs($route, "{$route}.*"),
                               ])>{{ $label }}@if ($children)<x-phosphor-caret-down class="ml-1 inline h-3.5 w-3.5 align-[-0.125em] transition-transform duration-fast ease-out-strong" ::class="open && 'rotate-180'" aria-hidden="true" />@endif</a>
                            @if ($children)
                                <div x-show="open" x-cloak
                                     x-transition:enter="transition duration-fast ease-out-strong" x-transition:enter-start="-translate-y-1 opacity-0"
                                     class="absolute left-2 top-full z-dropdown w-max min-w-48">
                                    <ul class="overflow-hidden rounded-b-md border-t-2 border-accent bg-brand-900 py-1 shadow-overlay">
                                        @foreach ($children as [$childLabel, $href])
                                            <li><a href="{{ $href }}" class="nav-sub-link"><span>{{ $childLabel }}</span></a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div id="mobile-menu" x-show="open" x-cloak class="border-t border-brand-700 lg:hidden">
                <ul class="px-4 py-2">
                    @foreach ($links as $route => $label)
                        @php $children = $menus[$route] ?? []; @endphp
                        <li @if ($children) x-data="{ sub: false }" @endif>
                            <div class="flex items-center">
                                <a href="{{ route($route) }}"
                                   @if (request()->routeIs($route, "{$route}.*")) aria-current="page" @endif
                                   @class([
                                       'flex min-h-11 flex-1 items-center text-sm font-semibold uppercase tracking-[0.06em]',
                                       'text-accent-400' => request()->routeIs($route, "{$route}.*"),
                                       'text-brand-100' => ! request()->routeIs($route, "{$route}.*"),
                                   ])>{{ $label }}</a>
                                @if ($children)
                                    <button type="button" @click="sub = ! sub" :aria-expanded="sub.toString()" aria-controls="mobile-sub-{{ $route }}"
                                            class="inline-flex h-11 w-11 items-center justify-center rounded-md text-brand-100 transition-colors duration-fast hover:bg-brand-700 hover:text-accent-400">
                                        <x-phosphor-caret-down class="h-4 w-4 transition-transform duration-fast ease-out-strong" ::class="sub && 'rotate-180'" aria-hidden="true" />
                                        <span class="sr-only">{{ $label }} sections</span>
                                    </button>
                                @endif
                            </div>
                            @if ($children)
                                <ul id="mobile-sub-{{ $route }}" x-show="sub" x-cloak
                                    x-transition:enter="transition duration-fast ease-out-strong" x-transition:enter-start="-translate-y-1 opacity-0"
                                    class="mb-2 overflow-hidden rounded-md bg-brand-900 py-1">
                                    @foreach ($children as [$childLabel, $href])
                                        <li>
                                            <a href="{{ $href }}" class="nav-sub-link min-h-11"><span>{{ $childLabel }}</span></a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="flex gap-3 border-t border-brand-700 px-4 py-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-accent flex-1">My dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn flex-1 border border-brand-500 text-paper hover:bg-brand-700">Log in</a>
                        <a href="{{ route('register') }}" class="header-apply flex-1">Apply now</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    {{--
        One compact block (public refinement, Nov 2026; it was a full screen tall): who runs the site,
        where to find the office, the pages, then one line for the copyright, legal basis and privacy.
    --}}
    <footer class="bg-brand-900 text-brand-200">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)_auto] lg:gap-12">
                <div class="flex items-start gap-3">
                    <x-civic-mark class="h-12 w-12 shrink-0" />
                    <div>
                        <p class="font-serif text-lg font-bold text-paper">Lingayen CivicLink</p>
                        <p class="mt-1 max-w-[46ch] text-sm leading-relaxed">
                            The CSO accreditation and activity record of the Municipality of Lingayen, kept by the
                            {{ config('office.office.name') }} of the Public Employment Service Office.
                        </p>
                    </div>
                </div>

                <address class="text-sm not-italic leading-relaxed">
                    <span class="font-semibold text-paper">{{ config('office.office.name') }}</span><br>
                    {{ config('office.office.address') }}<br>
                    {{ config('office.office.hours') }}<br>
                    <a href="tel:{{ config('office.office.phone_link') }}" class="tabular-nums underline-offset-4 hover:text-paper hover:underline">{{ config('office.office.phone') }}</a>
                    <span aria-hidden="true">·</span>
                    <a href="mailto:{{ config('office.office.email') }}" class="break-all underline-offset-4 hover:text-paper hover:underline">{{ config('office.office.email') }}</a>
                </address>

                <nav aria-label="Footer" class="md:col-span-2 lg:col-span-1">
                    <ul class="grid grid-cols-2 gap-x-8 text-sm sm:grid-cols-3 lg:grid-cols-2">
                        @foreach ($links as $route => $label)
                            <li><a href="{{ route($route) }}" class="inline-flex min-h-8 items-center underline-offset-4 hover:text-paper hover:underline">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            </div>

            <p class="mt-6 flex flex-col gap-1 border-t border-brand-700 pt-4 text-xs text-brand-300 sm:flex-row sm:flex-wrap sm:gap-x-2">
                <span>&copy; {{ now()->year }} Municipality of Lingayen, Pangasinan · Civil Society Desk Office</span>
                <span class="hidden sm:inline" aria-hidden="true">·</span>
                <span>Accreditation under RA 7160 and DILG Memorandum Circular No. 2025-060</span>
                <span class="hidden sm:inline" aria-hidden="true">·</span>
                <a href="{{ route('privacy') }}" class="w-fit underline-offset-4 hover:text-paper hover:underline">Privacy notice</a>
            </p>
        </div>
    </footer>
</body>
</html>
