<x-layouts.public title="Accredited CSOs">
    <x-page-banner title="Accredited CSOs" photo="events/cso5.jpg" alt="Members of accredited organizations at a CSO assembly in Lingayen"
                   lede="Organizations currently accredited by the Municipality of Lingayen. Member names and contact details are held by PESO and are not shown publicly." />

    {{-- Filters sit in a raised panel that overlaps the banner edge. On phones only the search shows;
         Barangay and Sector open with the Filters button (already open when one is in use). --}}
    <div class="relative z-10 mx-auto -mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-lg border border-line bg-paper p-4 shadow-panel sm:p-6" x-data="{ more: @js(request()->hasAny(['barangay', 'sector'])) }">
            <form method="GET" action="{{ route('directory') }}" class="grid gap-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto]">
                <div>
                    <label for="q" class="field-label">Search</label>
                    <input type="search" id="q" name="q" value="{{ request('q') }}"
                           placeholder="Organization name" class="field-input">
                </div>
                <div class="hidden sm:block" :class="{ '!block': more }">
                    <label for="barangay" class="field-label">Barangay</label>
                    <select id="barangay" name="barangay" class="field-input">
                        <option value="">All barangays</option>
                        @foreach ($barangays as $barangay)
                            <option value="{{ $barangay }}" @selected(request('barangay') === $barangay)>{{ $barangay }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hidden sm:block" :class="{ '!block': more }">
                    <label for="sector" class="field-label">Sector</label>
                    <select id="sector" name="sector" class="field-input">
                        <option value="">All sectors</option>
                        @foreach ($sectors as $sector)
                            <option value="{{ $sector }}" @selected(request('sector') === $sector)>{{ $sector }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="button" class="btn-secondary sm:hidden" @click="more = ! more" :aria-expanded="more.toString()">
                        <x-phosphor-funnel class="h-4 w-4" aria-hidden="true" />Filters
                    </button>
                    <button type="submit" class="btn-primary flex-1 sm:flex-none">Search</button>
                    @if (request()->hasAny(['q', 'sector', 'barangay']))
                        <a href="{{ route('directory') }}" class="btn-ghost">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 pb-12 pt-12 sm:px-6 lg:px-8">
        <p class="text-sm text-muted">
            <span class="font-semibold tabular-nums text-ink">{{ $organizations->total() }}</span>
            {{ Str::plural('organization', $organizations->total()) }} with an active accreditation{{ request()->hasAny(['q', 'sector', 'barangay']) ? ' match your search' : '' }}.
            Activity counts include work done as a partner of another organization.
        </p>

        @if ($organizations->isEmpty())
            <div class="panel mt-6 p-12 text-center">
                <p class="font-medium text-ink">No organizations match those filters.</p>
                <p class="mt-1 text-sm text-muted">Try a broader search, or clear the filters to see every accredited CSO.</p>
            </div>
        @else
            {{--
                The register (public refinement, Nov 2026): one row per organization with its record, so
                a resident confirms an accreditation and an official compares activity at a glance,
                instead of equal cards holding an icon and a blurb.
            --}}
            <div class="mt-6 border-t-2 border-brand-800">
                <div class="hidden grid-cols-[minmax(0,2.4fr)_minmax(0,1fr)_7rem_8rem_8rem] gap-6 border-b border-line px-2 py-3 text-xs font-semibold uppercase tracking-[0.08em] text-muted lg:grid" aria-hidden="true">
                    <span>Organization</span>
                    <span>Barangay</span>
                    <span class="text-right">Verified activities</span>
                    <span>Last activity</span>
                    <span>Accredited until</span>
                </div>
                <ul class="divide-y divide-line border-b border-line">
                    @foreach ($organizations as $organization)
                        @php $until = $organization->accreditations->first()?->expires_at; @endphp
                        <li>
                            <a href="{{ route('directory.show', $organization) }}"
                               class="group grid grid-cols-[3rem_minmax(0,1fr)] items-center gap-x-4 gap-y-2 py-4 transition-colors duration-fast ease-out-strong hover:bg-brand-50 lg:grid-cols-[minmax(0,2.4fr)_minmax(0,1fr)_7rem_8rem_8rem] lg:gap-6 lg:px-2">
                                <span class="flex min-w-0 items-center gap-4 lg:col-auto">
                                    @if ($organization->logo_path)
                                        <img src="{{ asset('storage/'.$organization->logo_path) }}" alt="" loading="lazy"
                                             class="h-12 w-12 shrink-0 rounded-md border border-line bg-paper object-contain p-1">
                                    @else
                                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-700 transition-colors duration-fast group-hover:bg-paper">
                                            <x-sector-icon :sector="$organization->sector" class="h-6 w-6" />
                                        </span>
                                    @endif
                                    <span class="hidden min-w-0 lg:block">
                                        <span class="block font-semibold text-ink underline-offset-4 group-hover:text-brand-800 group-hover:underline">{{ $organization->name }}</span>
                                        <span class="block text-sm text-muted">{{ $organization->sector }}</span>
                                    </span>
                                </span>
                                {{-- Phones: everything in one block beside the mark. --}}
                                <span class="min-w-0 lg:hidden">
                                    <span class="block font-semibold text-ink group-hover:text-brand-800">{{ $organization->name }}</span>
                                    <span class="block text-sm text-muted">{{ $organization->sector }} · Brgy. {{ $organization->barangay }}</span>
                                    <span class="mt-1 block text-sm tabular-nums text-ink">
                                        {{ $organization->verified_count }} verified {{ Str::plural('activity', $organization->verified_count) }}@if ($until) · until {{ $until->format('M Y') }}@endif
                                    </span>
                                </span>
                                <span class="hidden text-sm text-ink lg:block">Brgy. {{ $organization->barangay }}</span>
                                <span class="hidden text-right font-serif text-xl font-bold tabular-nums text-brand-800 lg:block">
                                    {{ $organization->verified_count }}<span class="sr-only"> verified activities</span>
                                </span>
                                <span class="hidden text-sm tabular-nums text-muted lg:block">
                                    <span class="sr-only">Last activity </span>{{ $organization->last_activity ? \Illuminate\Support\Carbon::parse($organization->last_activity)->format('d M Y') : 'None yet' }}
                                </span>
                                <span class="hidden text-sm tabular-nums text-ink lg:block">
                                    <span class="sr-only">Accredited until </span>{{ $until?->format('d M Y') }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-8">{{ $organizations->withQueryString()->links('pagination.public') }}</div>
        @endif
    </div>
</x-layouts.public>
