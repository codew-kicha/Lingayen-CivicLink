<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' | Lingayen CivicLink' : 'Lingayen CivicLink' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-sticky focus:rounded-md
              focus:bg-brand-900 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-paper">
        Skip to the form
    </a>

    <div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        <aside class="photo-wash photo-wash-even flex flex-col px-6 py-5 text-paper sm:px-10 lg:py-10">
            {{-- Placeholder stock photo (images/stock/SOURCES.txt). --}}
            <img src="{{ asset('images/stock/floating-market.jpg') }}" alt="" width="1600" height="1000" class="hidden lg:block">
            <a href="{{ route('home') }}" class="relative z-10 flex items-center gap-2.5 self-start rounded-sm focus-visible:outline-accent-400">
                <x-civic-mark class="h-11 w-11" />
                <span class="font-serif text-lg font-bold">Lingayen CivicLink</span>
            </a>

            <div class="relative z-10 hidden flex-1 flex-col justify-center py-12 lg:flex">
                @isset($aside)
                    {{ $aside }}
                @else
                    <p class="max-w-[24ch] font-serif text-3xl font-bold leading-snug">
                        The public record of Lingayen's accredited civil society organizations.
                    </p>
                @endisset
            </div>

            <p class="relative z-10 hidden text-sm text-brand-100 lg:block">
                {{ config('office.office.name') }}<br>
                {{ config('office.office.address') }}<br>
                {{ config('office.office.hours') }}
            </p>
        </aside>

        <main id="main" class="flex items-start justify-center px-6 py-10 sm:px-10 lg:items-center lg:py-16">
            <div class="w-full max-w-md">
                @isset($heading)
                    <h1 class="font-serif text-[1.75rem] font-bold leading-tight text-brand-900">{{ $heading }}</h1>
                @endisset
                @isset($intro)
                    <p class="mt-2 text-muted">{{ $intro }}</p>
                @endisset

                @if (session('status') && session('status') !== 'verification-link-sent')
                    <p role="status" class="mt-6 rounded-sm border border-success bg-success-subtle px-4 py-3 text-sm font-medium text-success">
                        {{ session('status') }}
                    </p>
                @endif

                <div class="mt-8">
                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>
</body>
</html>
