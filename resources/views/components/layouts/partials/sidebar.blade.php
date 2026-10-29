{{--
    Sidebar contents for the shared app layout, rendered in the desktop sidebar and in the mobile
    drawer. Items come from config('navigation')[role]; counts from the AppServiceProvider composer.
--}}
@php
    $user = auth()->user();
    $groups = config('navigation.'.($user->isAdmin() ? 'admin' : 'cso_rep'), []);
    $role = $user->isAdmin() ? 'PESO Admin' : 'CSO Representative';
    $affiliation = collect([$user->position, $user->isAdmin() ? 'Civil Society Desk Office' : $user->organization?->name])
        ->filter()->join(', ');
@endphp

<div class="flex shrink-0 items-center gap-3 px-4 py-3">
    <x-civic-mark class="h-9 w-9" />
    <div class="min-w-0">
        <p class="text-sm font-semibold text-on-primary">CSO Desk Office</p>
        <p class="text-xs text-on-dark-muted">{{ $role }}</p>
    </div>
    @if ($collapsible ?? false)
        {{-- Desktop only: hides the sidebar so the page takes the full width. --}}
        <button type="button" @click="toggleSidebar()" aria-controls="app-sidebar" :aria-expanded="(! collapsed).toString()" title="Hide menu"
                class="-mr-2 ml-auto inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-sm text-on-dark
                       transition-colors duration-fast ease-out-strong hover:bg-primary-dark-hover hover:text-on-primary focus-visible:outline-focus-on-dark">
            <x-phosphor-list class="h-5 w-5" aria-hidden="true" />
            <span class="sr-only">Hide menu</span>
        </button>
    @endif
</div>

{{-- Only this part scrolls, and only when the window is too short to fit it (thin maroon scrollbar). --}}
<nav aria-label="Main" class="sidebar-scroll min-h-0 flex-1 overflow-y-auto px-2 pb-2">
    @foreach ($groups as $group => $items)
        <p @class(['px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-on-dark-muted', $loop->first ? 'pt-2' : 'pt-4'])>{{ $group }}</p>
        <ul>
            @foreach ($items as $item)
                @php
                    $active = request()->routeIs(...($item['active'] ?? [$item['route']]));
                    $count = isset($item['count']) ? ($navCounts[$item['count']] ?? 0) : 0;
                @endphp
                <li>
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                       @class([
                           'group flex min-h-9 items-center gap-3 rounded-sm px-3 text-sm max-lg:min-h-11 touch:min-h-11',
                           'transition-colors duration-fast ease-out-strong focus-visible:outline-focus-on-dark',
                           'bg-primary-dark-hover font-semibold text-on-primary shadow-[inset_3px_0_0_rgb(var(--color-accent))]' => $active,
                           'text-on-dark hover:bg-primary-dark-hover hover:text-on-primary' => ! $active,
                       ])>
                        <x-dynamic-component :component="'phosphor-'.$item['icon']" class="h-5 w-5 shrink-0" aria-hidden="true" />
                        <span class="truncate">{{ $item['label'] }}</span>
                        @if ($count)
                            <span class="count-badge ml-auto" data-nav-count="{{ $item['count'] }}" aria-label="{{ $count }} waiting">{{ $count }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    @endforeach
</nav>

{{-- User block: long names truncate on one line; the full text is in the tooltip and for screen readers. --}}
<div class="shrink-0 border-t border-primary-dark-hover px-2 py-2">
    <div class="flex min-w-0 items-center gap-3 px-3 py-1" title="{{ $user->name }}{{ $affiliation ? ' · '.$affiliation : '' }}">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-dark-hover text-xs font-semibold text-on-primary" aria-hidden="true">
            {{ Str::of($user->name)->explode(' ')->filter()->take(2)->map(fn ($part) => Str::upper(Str::substr($part, 0, 1)))->join('') }}
        </span>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-on-primary">{{ $user->name }}</p>
            @if ($affiliation)
                <p class="truncate text-xs text-on-dark-muted">{{ $affiliation }}</p>
            @endif
        </div>
    </div>
    <a href="{{ route('profile.edit') }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif
       @class([
           'flex min-h-9 items-center gap-3 rounded-sm px-3 text-sm max-lg:min-h-11 touch:min-h-11 focus-visible:outline-focus-on-dark',
           'bg-primary-dark-hover font-semibold text-on-primary shadow-[inset_3px_0_0_rgb(var(--color-accent))]' => request()->routeIs('profile.*'),
           'text-on-dark hover:bg-primary-dark-hover hover:text-on-primary' => ! request()->routeIs('profile.*'),
       ])>
        <x-phosphor-gear class="h-5 w-5 shrink-0" aria-hidden="true" />
        Account settings
    </a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit"
                class="flex min-h-9 w-full items-center gap-3 rounded-sm px-3 text-left text-sm text-on-dark max-lg:min-h-11 touch:min-h-11
                       hover:bg-primary-dark-hover hover:text-on-primary focus-visible:outline-focus-on-dark">
            <x-phosphor-sign-out class="h-5 w-5 shrink-0" aria-hidden="true" />
            Log out
        </button>
    </form>
</div>
