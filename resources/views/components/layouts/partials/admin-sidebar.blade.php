{{-- Admin sidebar contents, rendered in the desktop sidebar and in the mobile drawer. --}}
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
