<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' | Lingayen CivicLink' : 'Lingayen CivicLink' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans text-ink antialiased">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-sticky focus:rounded-md
              focus:bg-navy-900 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-paper">
        Skip to the form
    </a>

    <div class="grid min-h-screen lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]">
        <aside class="flex flex-col bg-navy-900 px-6 py-5 text-paper sm:px-10 lg:py-10">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 self-start rounded-sm focus-visible:outline-rose-400">
                <x-civic-mark class="h-9 w-9 text-sm" />
                <span class="font-semibold tracking-tight">Lingayen CivicLink</span>
            </a>

            <div class="hidden flex-1 flex-col justify-center py-12 lg:flex">
                @isset($aside)
                    {{ $aside }}
                @else
                    <p class="max-w-[34ch] text-xl font-semibold leading-snug [text-wrap:balance]">
                        The public record of Lingayen's accredited civil society organizations.
                    </p>
                @endisset
            </div>

            <p class="hidden text-sm text-navy-300 lg:block">
                {{ config('office.office.name') }}<br>
                {{ config('office.office.address') }}<br>
                {{ config('office.office.hours') }}
            </p>
        </aside>

        <main id="main" class="flex items-start justify-center px-6 py-10 sm:px-10 lg:items-center lg:py-16">
            <div class="w-full max-w-md">
                @isset($heading)
                    <h1 class="text-2xl font-semibold text-ink [text-wrap:balance]">{{ $heading }}</h1>
                @endisset
                @isset($intro)
                    <p class="mt-2 text-muted">{{ $intro }}</p>
                @endisset

                @if (session('status') && session('status') !== 'verification-link-sent')
                    <p role="status" class="mt-6 rounded-sm border border-success-600 bg-success-100 px-4 py-3 text-sm font-medium text-success-600">
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
