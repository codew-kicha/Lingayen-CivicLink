<x-layouts.public>
    <section class="bg-navy-900 text-paper">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <h1 class="text-3xl font-semibold tracking-tight sm:text-[clamp(1.95rem,5vw,3.05rem)]">
                    Find an accredited CSO in Lingayen, or get your own accredited.
                </h1>
                <p class="mt-4 max-w-[60ch] text-lg text-navy-200">
                    The official register of civil society organizations partnering with the
                    Municipality of Lingayen, with the work they do recorded as it happens.
                </p>

                <form action="{{ route('directory') }}" method="GET" class="mt-8 flex flex-wrap gap-2">
                    <label for="hero-search" class="sr-only">Search accredited organizations</label>
                    <input type="search" id="hero-search" name="q"
                           placeholder="Search by name, sector, or barangay"
                           class="w-full min-w-0 flex-1 rounded-md border-0 bg-paper px-4 py-3 text-base
                                  text-ink placeholder:text-muted focus-visible:outline-amber-400 sm:w-auto">
                    <button type="submit"
                            class="rounded-md bg-amber-500 px-5 py-3 text-sm font-semibold text-navy-900
                                   transition-colors duration-fast ease-out-strong hover:bg-amber-400
                                   focus-visible:outline-amber-400">
                        Search directory
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="border-b border-line bg-surface" aria-label="Transparency statistics">
        <div class="mx-auto grid max-w-7xl grid-cols-2 divide-line px-4 sm:px-6 md:grid-cols-4 md:divide-x lg:px-8">
            @foreach ($stats as $stat)
                <div class="px-2 py-6 md:px-6">
                    <p class="font-mono text-2xl tabular-nums text-navy-800">{{ $stat['value'] }}</p>
                    <p class="mt-1 text-sm text-muted">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="grid gap-12 md:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
            <div>
                <h2 class="text-xl font-semibold text-ink">What counts as a CSO</h2>
                <p class="mt-4 max-w-[68ch] text-muted">
                    A civil society organization is a non-government, non-profit group organized around
                    a shared community interest: people's organizations, cooperatives, neighborhood
                    associations, and non-government organizations among them. Under the Local
                    Government Code, these groups are the LGU's partners in local governance, and
                    accredited CSOs may sit on Local Special Bodies such as the Local Development
                    Council and the Local School Board.
                </p>
                <a href="{{ route('about') }}"
                   class="mt-5 inline-block text-sm font-semibold text-navy-700 hover:text-navy-900 hover:underline">
                    Read the legal basis
                </a>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-ink">Where CSOs are working</h2>
                <p class="mt-4 text-muted">Accredited organizations by advocacy area.</p>
                <ul class="mt-5 divide-y divide-line border-y border-line">
                    @foreach ($sectors as $sector)
                        <li class="flex items-center gap-4 py-3">
                            <span class="text-sm text-ink">{{ $sector['name'] }}</span>
                            <span class="ml-auto font-mono text-sm tabular-nums text-muted">{{ $sector['count'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="border-t border-line bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-end gap-4">
                <h2 class="text-xl font-semibold text-ink">Recently accredited</h2>
                <a href="{{ route('directory') }}"
                   class="ml-auto text-sm font-semibold text-navy-700 hover:text-navy-900 hover:underline">
                    View the full directory
                </a>
            </div>

            <div class="mt-6 grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                @foreach ($featured as $org)
                    <a href="{{ route('directory') }}"
                       class="panel block p-5 transition-colors duration-fast ease-out-strong hover:border-navy-300">
                        <h3 class="font-semibold text-ink">{{ $org['name'] }}</h3>
                        <p class="mt-1 text-sm text-muted">{{ $org['sector'] }} &middot; Brgy. {{ $org['barangay'] }}</p>
                        <p class="mt-3 line-clamp-2 text-sm text-muted">{{ $org['advocacy'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end gap-4">
            <h2 class="text-xl font-semibold text-ink">Latest updates</h2>
            <a href="{{ route('resources') }}"
               class="ml-auto text-sm font-semibold text-navy-700 hover:text-navy-900 hover:underline">
                All news and reports
            </a>
        </div>

        <ul class="mt-6 divide-y divide-line border-y border-line">
            @foreach ($news as $post)
                <li class="py-5">
                    <a href="{{ route('resources') }}" class="group block">
                        <p class="font-mono text-xs uppercase tracking-wide text-muted">{{ $post['date'] }}</p>
                        <h3 class="mt-1 font-semibold text-ink group-hover:underline">{{ $post['title'] }}</h3>
                        <p class="mt-1 max-w-[68ch] text-sm text-muted">{{ $post['excerpt'] }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="border-t border-line bg-navy-900 text-paper">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-6 px-4 py-14 sm:px-6 lg:px-8">
            <div>
                <h2 class="text-xl font-semibold">Ready to apply for accreditation?</h2>
                <p class="mt-2 max-w-[60ch] text-navy-200">
                    Register your organization, upload your requirements, and track the review through
                    every Sangguniang Bayan reading without a trip to the municipal hall.
                </p>
            </div>
            <div class="flex flex-wrap gap-3 md:ml-auto">
                <a href="{{ route('accreditation') }}"
                   class="rounded-md border border-navy-400 px-5 py-3 text-sm font-semibold text-paper
                          transition-colors duration-fast ease-out-strong hover:bg-navy-800
                          focus-visible:outline-amber-400">
                    Read the guidelines
                </a>
                <a href="{{ route('register') }}"
                   class="rounded-md bg-amber-500 px-5 py-3 text-sm font-semibold text-navy-900
                          transition-colors duration-fast ease-out-strong hover:bg-amber-400
                          focus-visible:outline-amber-400">
                    Apply Now
                </a>
            </div>
        </div>
    </section>
</x-layouts.public>
