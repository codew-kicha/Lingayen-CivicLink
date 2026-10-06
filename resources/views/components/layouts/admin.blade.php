<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $header ?? 'PESO Admin' }} | Lingayen CivicLink</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-sticky
              focus:rounded-md focus:bg-brand-900 focus:px-4 focus:py-2 focus:text-sm
              focus:font-semibold focus:text-paper">
        Skip to main content
    </a>

    <div class="flex min-h-screen">
        <aside class="hidden w-60 shrink-0 flex-col bg-brand-900 text-brand-200 lg:flex">
            <div class="flex items-center gap-2.5 px-4 py-4">
                <x-civic-mark class="h-10 w-10" />
                <div>
                    <p class="text-sm font-semibold text-paper">CivicLink</p>
                    <p class="text-xs text-brand-300">PESO Admin</p>
                </div>
            </div>

            <nav aria-label="Admin" class="flex-1 space-y-0.5 px-2 py-2">
                @php
                    $nav = [
                        ['admin.dashboard', 'Dashboard', null],
                        ['admin.applications.index', 'Applications', $pendingApplications],
                        ['admin.activities.index', 'Activity verification', $pendingActivities],
                        ['admin.organizations.index', 'Organizations', null],
                        ['admin.scorecards.index', 'Scorecards', null],
                        ['admin.analytics', 'Analytics & Reports', null],
                        ['admin.news.index', 'News posts', null],
                        ['admin.annual-reports.index', 'Annual reports', null],
                        ['admin.accounts.index', 'Accounts', null],
                        ['admin.audit.index', 'Audit log', null],
                    ];
                @endphp
                @foreach ($nav as [$route, $label, $count])
                    <a href="{{ Route::has($route) ? route($route) : '#' }}"
                       @if (request()->routeIs($route)) aria-current="page" @endif
                       class="flex items-center gap-2 rounded-sm px-3 py-2 text-sm font-medium
                              transition-colors duration-fast ease-out-strong
                              hover:bg-brand-800 hover:text-paper focus-visible:outline-accent-400
                              {{ request()->routeIs($route) ? 'bg-brand-800 text-paper' : '' }}">
                        <span>{{ $label }}</span>
                        @if ($count)
                            <span class="ml-auto rounded-sm bg-accent-500 px-1.5 py-0.5 text-xs font-semibold text-brand-900">
                                {{ $count }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-brand-800 px-2 py-2">
                <a href="{{ route('profile.edit') }}"
                   class="block rounded-sm px-3 py-2 text-sm hover:bg-brand-800 hover:text-paper focus-visible:outline-accent-400">
                    {{ auth()->user()->name }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-sm px-3 py-2 text-left text-sm hover:bg-brand-800 hover:text-paper focus-visible:outline-accent-400">
                        Log out
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
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
