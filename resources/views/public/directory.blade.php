<x-layouts.public title="Accredited CSOs">
    <div class="border-b border-line bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-semibold text-ink">Accredited CSOs</h1>
            <p class="mt-3 max-w-[68ch] text-muted">
                Organizations currently accredited by the Municipality of Lingayen. Member names and
                contact details are held by PESO and are not shown publicly.
            </p>

            <form method="GET" action="{{ route('directory') }}" class="mt-8 grid gap-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto]">
                <div>
                    <label for="q" class="field-label">Search</label>
                    <input type="search" id="q" name="q" value="{{ request('q') }}"
                           placeholder="Organization name" class="field-input">
                </div>
                <div>
                    <label for="sector" class="field-label">Sector</label>
                    <select id="sector" name="sector" class="field-input">
                        <option value="">All sectors</option>
                        @foreach ($sectors as $sector)
                            <option value="{{ $sector }}" @selected(request('sector') === $sector)>{{ $sector }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="barangay" class="field-label">Barangay</label>
                    <select id="barangay" name="barangay" class="field-input">
                        <option value="">All barangays</option>
                        @foreach ($barangays as $barangay)
                            <option value="{{ $barangay }}" @selected(request('barangay') === $barangay)>{{ $barangay }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary">Filter</button>
                    @if (request()->hasAny(['q', 'sector', 'barangay']))
                        <a href="{{ route('directory') }}" class="btn-ghost">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <p class="text-sm text-muted">
            {{ $organizations->total() }} {{ Str::plural('organization', $organizations->total()) }} listed
        </p>

        @if ($organizations->isEmpty())
            <div class="panel mt-6 p-10 text-center">
                <p class="font-medium text-ink">No organizations match those filters.</p>
                <p class="mt-1 text-sm text-muted">Try a broader search, or clear the filters to see every accredited CSO.</p>
            </div>
        @else
            <div class="mt-6 grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
                @foreach ($organizations as $organization)
                    <a href="{{ route('directory.show', $organization) }}"
                       class="panel block p-5 transition-colors duration-fast ease-out-strong hover:border-navy-300">
                        <div class="flex items-start gap-3">
                            <h2 class="font-semibold text-ink">{{ $organization->name }}</h2>
                            <span class="ml-auto shrink-0"><x-status-badge status="active" /></span>
                        </div>
                        <p class="mt-1.5 text-sm text-muted">
                            {{ $organization->sector }} &middot; Brgy. {{ $organization->barangay }}
                        </p>
                        @if ($organization->advocacy)
                            <p class="mt-3 line-clamp-2 text-sm text-muted">{{ $organization->advocacy }}</p>
                        @endif
                    </a>
                @endforeach
            </div>

            <div class="mt-8">{{ $organizations->withQueryString()->links() }}</div>
        @endif
    </div>
</x-layouts.public>
