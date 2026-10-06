<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $header ?? 'PESO Admin' }} | Lingayen CivicLink</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas" x-data="{ drawer: false }" @keydown.escape.window="drawer = false">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-sticky
              focus:rounded-md focus:bg-brand-900 focus:px-4 focus:py-2 focus:text-sm
              focus:font-semibold focus:text-paper">
        Skip to main content
    </a>

    <div class="flex min-h-screen">
        <aside class="sticky top-0 hidden h-screen w-60 shrink-0 flex-col self-start overflow-y-auto bg-brand-900 text-brand-200 lg:flex">
            @include('components.layouts.partials.admin-sidebar')
        </aside>

        {{-- Below lg the sidebar becomes an off-canvas drawer: focus trapped, Esc and the overlay close it. --}}
        <div x-show="drawer" x-cloak class="fixed inset-0 z-dialog lg:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
            <div x-show="drawer" x-transition.opacity class="absolute inset-0 bg-[oklch(0.27_0.01_50/0.5)]" @click="drawer = false" aria-hidden="true"></div>
            <aside x-show="drawer" x-trap.noscroll.inert="drawer"
                   x-transition:enter="transition duration-base ease-out-strong" x-transition:enter-start="-translate-x-full"
                   x-transition:leave="transition duration-fast ease-out-strong" x-transition:leave-end="-translate-x-full"
                   class="relative flex h-full w-72 max-w-[85vw] flex-col overflow-y-auto bg-brand-900 text-brand-200">
                <button type="button" @click="drawer = false"
                        class="absolute right-2 top-3 inline-flex h-11 w-11 items-center justify-center rounded-sm text-paper hover:bg-brand-800 focus-visible:outline-accent-400">
                    <x-phosphor-x class="h-5 w-5" aria-hidden="true" />
                    <span class="sr-only">Close navigation</span>
                </button>
                @include('components.layouts.partials.admin-sidebar')
            </aside>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <div class="sticky top-0 z-sticky flex items-center gap-3 bg-brand-900 px-2 py-2 text-paper lg:hidden">
                <button type="button" @click="drawer = true" :aria-expanded="drawer.toString()"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-sm hover:bg-brand-800 focus-visible:outline-accent-400">
                    <x-phosphor-list class="h-6 w-6" aria-hidden="true" />
                    <span class="sr-only">Open navigation</span>
                </button>
                <x-civic-mark class="h-8 w-8" />
                <span class="text-sm font-semibold">CivicLink</span>
            </div>
            <header class="border-b border-line bg-paper">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-3 px-4 py-3 sm:px-6">
                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-semibold text-ink">{{ $header ?? 'Dashboard' }}</h1>
                        @isset($subheader)
                            <p class="mt-0.5 text-sm text-muted">{{ $subheader }}</p>
                        @endisset
                    </div>
                    @isset($actions)
                        <div class="flex flex-wrap items-center gap-2 sm:ml-auto">{{ $actions }}</div>
                    @endisset
                </div>
            </header>

            <main id="main" class="flex-1 px-4 py-6 sm:px-6">
                <x-flash />
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
