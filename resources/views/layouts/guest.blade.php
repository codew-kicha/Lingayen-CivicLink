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
{{--
    The one layout for every sign-in page (sign in, register, password reset, invitation, email
    check): a maroon band at the top (the site's own colour) carrying the seal and name, a single
    card overlapping the band's edge, and the office's details below. An optional $footer sits
    outside the card (the switch between Sign in and Register).
--}}
<body class="min-h-dvh bg-band font-sans text-ink antialiased">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-sticky focus:rounded-md
              focus:bg-brand-900 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-paper">
        Skip to the form
    </a>

    <div class="absolute inset-x-0 top-0 h-44 bg-brand-800 sm:h-48" aria-hidden="true"></div>

    <div class="relative mx-auto flex min-h-dvh w-full max-w-md flex-col px-4 pb-8 pt-8 sm:pt-12">
        <a href="{{ route('home') }}" class="mx-auto flex items-center gap-3 rounded-sm focus-visible:outline-focus-on-dark">
            <x-civic-mark class="h-12 w-12" />
            <span class="leading-tight">
                <span class="block font-serif text-lg font-bold text-paper">CSO Desk Office</span>
                <span class="block text-xs text-brand-100">Municipality of Lingayen, Pangasinan</span>
            </span>
        </a>

        <main id="main" class="card mt-8 p-6 shadow-overlay sm:mt-12 sm:p-8">
            @isset($heading)
                <h1 class="font-serif text-[1.5rem] font-bold leading-tight text-brand-900">{{ $heading }}</h1>
            @endisset
            @isset($intro)
                <p class="mt-2 text-sm leading-relaxed text-muted">{{ $intro }}</p>
            @endisset

            @if (session('status') && session('status') !== 'verification-link-sent')
                <p role="status" class="mt-6 rounded-sm border border-success bg-success-subtle px-4 py-3 text-sm font-medium text-success">
                    {{ session('status') }}
                </p>
            @endif

            <div class="mt-6">
                {{ $slot }}
            </div>
        </main>

        @isset($footer)
            <div class="mt-4 text-center text-sm text-muted">{{ $footer }}</div>
        @endisset

        <p class="mt-8 text-center text-xs leading-relaxed text-muted">
            {{ config('office.office.name') }}, {{ config('office.office.address') }}<br>
            {{ config('office.office.hours') }}
        </p>
    </div>
</body>
</html>
