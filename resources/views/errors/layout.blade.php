<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Lingayen CivicLink</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col bg-paper">
    <header class="bg-navy-900 text-paper">
        <div class="mx-auto flex max-w-7xl items-center gap-2.5 px-4 py-3 sm:px-6 lg:px-8">
            <x-civic-mark />
            <span class="text-base font-semibold tracking-tight">Lingayen CivicLink</span>
        </div>
    </header>

    <main class="mx-auto flex w-full max-w-2xl flex-1 flex-col justify-center px-4 py-20 sm:px-6">
        <p class="font-mono text-sm tabular-nums text-navy-600">@yield('code')</p>
        <h1 class="mt-2 text-2xl font-semibold text-ink">@yield('title')</h1>
        <p class="mt-3 max-w-[60ch] text-muted">@yield('message')</p>

        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ url('/') }}" class="btn-primary">Go to the home page</a>
            <a href="{{ route('contact') }}" class="btn-secondary">Contact the PESO office</a>
        </div>
    </main>
</body>
</html>
