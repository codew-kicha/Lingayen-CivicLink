<x-layouts.public>
    <section class="bg-navy-900 text-paper">
        <div class="mx-auto max-w-7xl px-4 pb-16 pt-14 sm:px-6 lg:px-8 lg:pb-20 lg:pt-16">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.9fr)_minmax(0,1fr)]">
                <div class="max-w-4xl">
                    <h1 class="flex items-center gap-3 text-sm font-semibold text-navy-200">
                        <span class="h-px w-8 bg-rose-500" aria-hidden="true"></span>
                        The public record of Lingayen's accredited civil society organizations
                    </h1>

                    @if ($ticker)
                        <div x-data="activityTicker({{ count($ticker) }})"
                             @mouseenter="hovered = true" @mouseleave="hovered = false"
                             @focusin="focused = true" @focusout="focused = false"
                             role="region" aria-label="Recently verified activities" class="mt-8">
                            <div class="grid" aria-live="off" :aria-live="playing ? 'off' : 'polite'">
                                @foreach ($ticker as $index => $item)
                                    <article @class([
                                                '[grid-area:1/1] self-end transition duration-slow ease-out-strong',
                                                'invisible translate-y-1.5 opacity-0' => $index > 0,
                                             ])
                                             :class="{ 'invisible translate-y-1.5 opacity-0': current !== {{ $index }} }">
                                        <p class="text-xl font-semibold leading-snug tracking-tight [text-wrap:balance]
                                                  sm:text-[clamp(1.5rem,2.6vw,2rem)] sm:leading-tight">
                                            {{ $item['title'] }} in Barangay {{ $item['barangay'] }}, by
                                            <a href="{{ $item['url'] }}"
                                               class="underline decoration-rose-500 decoration-2 underline-offset-[6px]
                                                      transition-colors duration-fast ease-out-strong hover:decoration-rose-400
                                                      focus-visible:outline-rose-400">{{ $item['organization'] }}</a>.
                                        </p>
                                        <p class="mt-3 flex items-center gap-2 text-sm text-navy-200">
                                            <x-phosphor-seal-check class="h-4 w-4 shrink-0 text-rose-400" aria-hidden="true" />
                                            Held {{ $item['held'] }}, verified by the Civil Society Desk Office
                                        </p>
                                    </article>
                                @endforeach
                            </div>

                            @if (count($ticker) > 1)
                                <div x-cloak class="mt-5 flex items-center gap-3 text-sm text-navy-200">
                                    <button type="button" @click="userPaused = ! userPaused"
                                            :aria-label="userPaused ? 'Resume rotating activities' : 'Pause rotating activities'"
                                            class="inline-flex h-11 w-11 items-center justify-center rounded-md border border-navy-600
                                                   transition-colors duration-fast ease-out-strong hover:bg-navy-800
                                                   active:scale-[0.97] focus-visible:outline-rose-400">
                                        <x-phosphor-pause x-show="! userPaused" class="h-5 w-5" aria-hidden="true" />
                                        <x-phosphor-play x-show="userPaused" class="h-5 w-5" aria-hidden="true" />
                                    </button>
                                    <span class="font-mono tabular-nums">
                                        <span x-text="current + 1">1</span> of {{ count($ticker) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    @else
                        <p class="mt-8 max-w-[40ch] text-2xl font-semibold leading-tight [text-wrap:balance]">
                            Verified activities appear here as the Civil Society Desk Office confirms them.
                        </p>
                    @endif

                    <p class="mt-10 max-w-[64ch] text-navy-200">
                        Each entry is logged by an accredited organization and checked by the Civil Society
                        Desk Office before it appears here.
                    </p>
                </div>

                {{-- Decorative placeholder photo; swap the path for a real CSO event photo later (§8). --}}
                <figure class="photo-duotone hidden min-h-[22rem] rounded-lg lg:block">
                    <img src="{{ asset('images/stock/shoreline-dusk.jpg') }}" alt=""
                         class="absolute inset-0" width="1600" height="1000" fetchpriority="high">
                </figure>
            </div>

            <div class="mt-10 grid gap-8 border-t border-navy-700 pt-8 md:grid-cols-2 md:gap-12">
                <div>
                    <h2 class="font-semibold">Checking on an organization?</h2>
                    <form action="{{ route('directory') }}" method="GET" class="mt-3 flex flex-wrap gap-2">
                        <label for="hero-search" class="sr-only">Organization name</label>
                        <input type="search" id="hero-search" name="q"
                               placeholder="Organization name"
                               class="w-full min-w-0 flex-1 rounded-md border-0 bg-paper px-4 py-3 text-base
                                      text-ink placeholder:text-muted focus-visible:outline-rose-400 sm:w-auto">
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-md bg-rose-500 px-5 py-3 text-sm font-semibold
                                       text-navy-900 transition-colors duration-fast ease-out-strong hover:bg-rose-400
                                       active:scale-[0.97] focus-visible:outline-rose-400">
                            <x-phosphor-magnifying-glass class="h-4 w-4" aria-hidden="true" />
                            Check accreditation
                        </button>
                    </form>
                </div>

                <div>
                    <h2 class="font-semibold">Representing a CSO?</h2>
                    <p class="mt-1.5 max-w-[48ch] text-sm text-navy-200">
                        File a new or renewal application online and follow it through each Sangguniang
                        Bayan reading.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center gap-2 rounded-md border border-navy-400 px-5 py-3 text-sm
                                  font-semibold transition-colors duration-fast ease-out-strong hover:bg-navy-800
                                  active:scale-[0.97] focus-visible:outline-rose-400">
                            Apply for accreditation
                            <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                        </a>
                        <a href="{{ route('accreditation') }}"
                           class="inline-flex items-center rounded-md px-3 py-3 text-sm font-semibold text-navy-200
                                  underline-offset-4 hover:text-paper hover:underline focus-visible:outline-rose-400">
                            See the requirements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-b border-line bg-surface" aria-label="Transparency statistics">
        <div class="mx-auto grid max-w-7xl grid-cols-2 divide-line px-4 sm:px-6 md:grid-cols-4 md:divide-x lg:px-8">
            @foreach ($stats as $stat)
                <div class="px-2 py-6 md:px-6">
                    <p class="font-mono text-2xl tabular-nums text-navy-800">
                        <span aria-hidden="true" data-count-to="{{ $stat['value'] }}">{{ number_format($stat['value']) }}</span>
                        <span class="sr-only">{{ number_format($stat['value']) }}</span>
                    </p>
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
                <p class="mt-4 text-muted">Accredited organizations by sector, as the Civil Society Desk Office classifies them.</p>
                <ul class="mt-5 grid sm:grid-cols-2 sm:gap-x-8">
                    @foreach ($sectors as $sector)
                        <li class="border-b border-line">
                            <a href="{{ route('directory', ['sector' => $sector['name']]) }}"
                               class="group flex min-h-11 items-center gap-3 py-3 transition-colors duration-fast ease-out-strong
                                      hover:text-navy-700 focus-visible:outline-navy-600">
                                <x-sector-icon :sector="$sector['name']" class="h-6 w-6 shrink-0 text-navy-600" />
                                <span class="text-sm text-ink group-hover:underline">{{ $sector['name'] }}</span>
                                <span class="ml-auto font-mono text-sm tabular-nums text-muted">{{ $sector['count'] }}</span>
                            </a>
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

            <ol class="mt-6 grid md:grid-cols-2 md:gap-x-10">
                @foreach ($featured as $org)
                    <li class="border-b border-line">
                        <a href="{{ $org['url'] }}"
                           class="group grid grid-cols-[auto_minmax(0,1fr)_auto] items-start gap-x-4 py-5
                                  focus-visible:outline-navy-600">
                            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-navy-900 text-rose-400">
                                <x-sector-icon :sector="$org['sector']" class="h-6 w-6" />
                            </span>
                            <span>
                                <span class="block font-semibold text-ink group-hover:underline">{{ $org['name'] }}</span>
                                <span class="mt-0.5 block text-sm text-muted">Brgy. {{ $org['barangay'] }} &middot; {{ $org['sector'] }}</span>
                                <span class="mt-2 block text-sm text-muted line-clamp-2">{{ $org['advocacy'] }}</span>
                            </span>
                            <span class="font-mono text-sm tabular-nums text-muted">
                                <time datetime="{{ $org['accredited_on']->toDateString() }}">{{ $org['accredited_on']->format('M Y') }}</time>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
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
</x-layouts.public>
