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
            {{ $organizations->total() }} {{ Str::plural('organization', $organizations->total()) }} listed
        </p>

        @if ($organizations->isEmpty())
            <div class="panel mt-6 p-12 text-center">
                <p class="font-medium text-ink">No organizations match those filters.</p>
                <p class="mt-1 text-sm text-muted">Try a broader search, or clear the filters to see every accredited CSO.</p>
            </div>
        @else
            <div class="mt-6 grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                @foreach ($organizations as $organization)
                    <div class="reveal">
                        <a href="{{ route('directory.show', $organization) }}"
                           class="panel block h-full p-6 transition-colors duration-fast ease-out-strong hover:border-brand-300">
                            {{-- Every listed organization is accredited, so the card shows who they are, not a status. --}}
                            <div class="flex items-start gap-4">
                                @if ($organization->logo_path)
                                    <img src="{{ asset('storage/'.$organization->logo_path) }}" alt="" loading="lazy"
                                         class="h-12 w-12 shrink-0 rounded-md border border-line bg-paper object-contain p-1">
                                @else
                                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-700">
                                        <x-sector-icon :sector="$organization->sector" class="h-6 w-6" />
                                    </span>
                                @endif
                                <div class="min-w-0">
                                    <h2 class="font-semibold text-ink">{{ $organization->name }}</h2>
                                    <p class="mt-1 text-sm text-muted">Brgy. {{ $organization->barangay }}, {{ $organization->sector }}</p>
                                </div>
                            </div>
                            @if ($organization->advocacy)
                                <p class="mt-3 line-clamp-2 text-sm text-muted">{{ $organization->advocacy }}</p>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">{{ $organizations->withQueryString()->links('pagination.public') }}</div>
        @endif
    </div>
</x-layouts.public>
