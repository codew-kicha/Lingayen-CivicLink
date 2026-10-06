@props(['header' => 'Dashboard', 'subheader' => null])

{{--
    The one signed-in layout for both roles (admin and CSO representative). Only the navigation
    differs, from config('navigation'). Page frame: max-w-7xl, padding 16/24/32 by breakpoint,
    title and subtitle always in the same place. See docs/DESIGN_SYSTEM.md §3.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $header }} | Lingayen CivicLink</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas text-ink" x-data="{ drawer: false }" @keydown.escape.window="drawer = false">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-toast focus:rounded-md
              focus:bg-primary-dark focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-on-primary">
        Skip to main content
    </a>

    {{-- Thin progress bar while the next page loads (pagination, filters, navigation). --}}
    <div x-data="pageProgress" x-show="loading" x-cloak class="fixed inset-x-0 top-0 z-toast h-1 overflow-hidden" role="progressbar" aria-label="Loading">
        <div class="h-full w-1/3 animate-[progress_1.1s_ease-in-out_infinite] bg-accent"></div>
    </div>

    <div class="flex min-h-screen">
        {{-- Locked to the viewport: never scrolls with the page. Only the nav inside may scroll, as a fallback. --}}
        <aside class="sticky top-0 hidden h-dvh w-64 shrink-0 flex-col self-start bg-primary-dark lg:flex">
            @include('components.layouts.partials.sidebar')
        </aside>

        {{-- Below lg: off-canvas drawer. Focus is trapped while open; Esc and the overlay close it. --}}
        <div x-show="drawer" x-cloak class="fixed inset-0 z-dialog lg:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
            <div x-show="drawer" x-transition.opacity class="absolute inset-0 bg-ink/50" @click="drawer = false" aria-hidden="true"></div>
            <aside x-show="drawer" x-trap.noscroll.inert="drawer"
                   x-transition:enter="transition duration-base ease-out-strong" x-transition:enter-start="-translate-x-full"
                   x-transition:leave="transition duration-fast ease-out-strong" x-transition:leave-end="-translate-x-full"
                   class="relative flex h-dvh w-72 max-w-[85vw] flex-col bg-primary-dark">
                <button type="button" @click="drawer = false"
                        class="absolute right-2 top-3 inline-flex h-11 w-11 items-center justify-center rounded-sm text-on-primary
                               hover:bg-primary-dark-hover focus-visible:outline-focus-on-dark">
                    <x-phosphor-x class="h-5 w-5" aria-hidden="true" />
                    <span class="sr-only">Close navigation</span>
                </button>
                @include('components.layouts.partials.sidebar')
            </aside>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <div class="sticky top-0 z-sticky flex items-center gap-3 bg-primary-dark px-2 py-2 text-on-primary lg:hidden">
                <button type="button" @click="drawer = true" :aria-expanded="drawer.toString()"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-sm hover:bg-primary-dark-hover focus-visible:outline-focus-on-dark">
                    <x-phosphor-list class="h-6 w-6" aria-hidden="true" />
                    <span class="sr-only">Open navigation</span>
                </button>
                <x-civic-mark class="h-8 w-8" />
                <span class="truncate text-sm font-semibold">{{ $header }}</span>
            </div>

            <main id="main" class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mb-6 flex flex-wrap items-start gap-x-6 gap-y-4">
                    <div class="min-w-0 flex-1 basis-80">
                        {{-- Pagination links point here (#page-title) so focus lands on the results after a page change. --}}
                        <h1 id="page-title" tabindex="-1" class="text-page-title font-semibold text-ink">{{ $header }}</h1>
                        @if ($subheader)
                            <p class="mt-1 max-w-[72ch] text-sm text-muted">{{ $subheader }}</p>
                        @endif
                    </div>
                    @isset($actions)
                        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
                    @endisset
                </div>

                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
