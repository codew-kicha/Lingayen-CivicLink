<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $header ?? 'PESO Admin' }} | Lingayen CivicLink</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface">
    <a href="#main"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-sticky
              focus:rounded-md focus:bg-navy-900 focus:px-4 focus:py-2 focus:text-sm
              focus:font-semibold focus:text-paper">
        Skip to main content
    </a>

    <div class="flex min-h-screen">
        <aside class="hidden w-60 shrink-0 flex-col bg-navy-900 text-navy-200 lg:flex">
            <div class="flex items-center gap-2.5 px-4 py-4">
                <x-civic-mark class="h-8 w-8 text-sm" />
                <div>
                    <p class="text-sm font-semibold text-paper">CivicLink</p>
                    <p class="text-xs text-navy-300">PESO Admin</p>
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
                              hover:bg-navy-800 hover:text-paper focus-visible:outline-rose-400
                              {{ request()->routeIs($route) ? 'bg-navy-800 text-paper' : '' }}">
                        <span>{{ $label }}</span>
                        @if ($count)
                            <span class="ml-auto rounded-sm bg-rose-500 px-1.5 py-0.5 text-xs font-semibold text-navy-900">
                                {{ $count }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <div class="border-t border-navy-800 px-2 py-2">
                <a href="{{ route('profile.edit') }}"
                   class="block rounded-sm px-3 py-2 text-sm hover:bg-navy-800 hover:text-paper focus-visible:outline-rose-400">
                    {{ auth()->user()->name }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-sm px-3 py-2 text-left text-sm hover:bg-navy-800 hover:text-paper focus-visible:outline-rose-400">
                        Log out
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-line bg-paper">
                <div class="flex items-center gap-4 px-4 py-3 sm:px-6">
                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-semibold text-ink">{{ $header ?? 'Dashboard' }}</h1>
                        @isset($subheader)
                            <p class="mt-0.5 text-sm text-muted">{{ $subheader }}</p>
                        @endisset
                    </div>
                    @isset($actions)
                        <div class="ml-auto flex items-center gap-2">{{ $actions }}</div>
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
