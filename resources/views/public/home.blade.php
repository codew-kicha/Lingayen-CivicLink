@php
    $process = config('office.process');
    $featured = collect($featured);
    $news = collect($news);
    [$accredited, $verified, $reached, $barangays] = array_column($stats, 'value');
@endphp

<x-layouts.public>
    {{-- Hero: the municipality's own loop (public/videos/lingayen_hero.mp4), shown whole like pcnc.com.ph's
         hero. It loops continuously with no controls (client's choice). Visitors whose device asks
         for reduced motion or data saving get the poster frame and the video never downloads. --}}
    <section x-data="heroVideo" aria-label="Lingayen's civil society organizations" class="relative bg-paper">
        <video x-ref="video" muted loop playsinline preload="none" aria-hidden="true"
               poster="{{ asset('videos/lingayen_hero-poster.jpg') }}"
               class="block aspect-video max-h-[78vh] w-full object-cover">
            <source src="{{ asset('videos/lingayen_hero.mp4') }}" type="video/mp4">
        </video>
    </section>

    {{-- Headline and search, in a maroon band directly under the video. --}}
    <section class="bg-brand-800 text-paper">
        <div class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)] lg:gap-12 lg:px-8 lg:py-12">
            <div>
                <h1 class="font-serif text-[clamp(1.9rem,3.6vw,2.9rem)] font-bold leading-[1.15]">
                    The public record of Lingayen's civil society organizations
                </h1>
                <p class="mt-4 max-w-[52ch] text-lg leading-relaxed text-brand-100">
                    Check which organizations are accredited, see their verified community work, and apply for accreditation.
                </p>
            </div>
            <div>
                <form action="{{ route('directory') }}" method="GET" class="flex flex-wrap gap-2">
                    <label for="hero-search" class="sr-only">Organization name</label>
                    <input type="search" id="hero-search" name="q" placeholder="Search an organization by name"
                           class="min-w-0 basis-full rounded-md border-0 bg-paper px-4 py-3 text-base text-ink placeholder:text-placeholder sm:basis-0 sm:flex-1
                                  focus-visible:outline-accent-400">
                    <button type="submit" class="btn-accent w-full py-3 sm:w-auto">
                        <x-phosphor-magnifying-glass class="h-4 w-4" aria-hidden="true" />
                        Check accreditation
                    </button>
                </form>
                <p class="mt-4 text-sm text-brand-100">
                    Representing a CSO?
                    <a href="{{ route('accreditation') }}" class="font-semibold text-paper underline underline-offset-4 hover:text-accent-200">See how to apply</a>
                </p>
            </div>
        </div>
    </section>

    {{-- Live strip: the latest activity PESO verified, rotating. --}}
    @if ($ticker)
        <section x-data="activityTicker({{ count($ticker) }})"
                 @mouseenter="hovered = true" @mouseleave="hovered = false"
                 @focusin="focused = true" @focusout="focused = false"
                 aria-label="Recently verified activities" class="border-b border-line bg-paper">
            <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <span class="hidden shrink-0 items-center gap-tight rounded-sm bg-accent-100 px-3 py-1 text-xs font-semibold text-brand-800 sm:inline-flex">
                    <x-phosphor-seal-check class="h-4 w-4" aria-hidden="true" />
                    Just verified
                </span>
                <div class="grid min-w-0 flex-1 overflow-hidden" aria-live="off" :aria-live="playing ? 'off' : 'polite'">
                    @foreach ($ticker as $index => $item)
                        <p @class(['[grid-area:1/1] truncate text-sm text-ink transition duration-slow ease-out-strong', 'invisible translate-y-1 opacity-0' => $index > 0])
                           :class="{ 'invisible translate-y-1 opacity-0': current !== {{ $index }} }">
                            <span class="font-semibold">{{ $item['title'] }}</span> in Brgy. {{ $item['barangay'] }}, by
                            <a href="{{ $item['url'] }}" class="font-semibold text-brand-700 underline decoration-accent-400 underline-offset-4 hover:text-brand-900">{{ $item['organization'] }}</a>
                            <span class="text-muted">({{ $item['held'] }})</span>
                        </p>
                    @endforeach
                </div>
                @if (count($ticker) > 1)
                    <button type="button" x-cloak @click="userPaused = ! userPaused"
                            :aria-label="userPaused ? 'Resume rotating activities' : 'Pause rotating activities'"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-brand-700 hover:bg-brand-50">
                        <x-phosphor-pause x-show="! userPaused" class="h-5 w-5" aria-hidden="true" />
                        <x-phosphor-play x-show="userPaused" class="h-5 w-5" aria-hidden="true" />
                    </button>
                @endif
            </div>
        </section>
    @endif

    {{-- The real process, in order: the numbers carry meaning. A row on desktop, a timeline on phones. --}}
    <section class="mx-auto max-w-7xl px-4 py-section sm:px-6 lg:px-8">
        <h2 class="section-title">How accreditation works</h2>
        <p class="mt-3 max-w-[60ch] text-muted">From filing to a seat on Lingayen's Local Special Bodies, every step is tracked here.</p>

        <ol class="relative mt-12 grid gap-8 lg:grid-cols-5 lg:gap-6">
            @foreach ($process as $index => $step)
                <li class="reveal relative grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-4 lg:block">
                    @unless ($loop->last)
                        <span class="reveal-line reveal-line-y absolute bottom-[-2rem] left-5 top-10 w-px bg-line-strong lg:hidden" aria-hidden="true"></span>
                            <span class="reveal-line reveal-line-x absolute left-10 top-5 hidden h-px w-[calc(100%-1rem)] bg-line-strong lg:block" aria-hidden="true"></span>
                    @endunless
                    <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-brand-800 text-sm font-semibold tabular-nums text-paper ring-4 ring-paper">{{ $index + 1 }}</span>
                    <div class="lg:mt-4">
                        <h3 class="font-semibold text-brand-900">{{ $step['title'] }}</h3>
                        <p class="mt-1 max-w-[34ch] text-sm leading-relaxed text-muted">{{ $step['detail'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <p class="mt-12">
            <a href="{{ route('accreditation') }}" class="btn-primary px-6">Requirements and why to get accredited</a>
        </p>
    </section>

    {{-- What is a CSO: text beside a photo from one of the office's CSO assemblies. --}}
    <section class="bg-band">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-section sm:px-6 lg:grid-cols-2 lg:px-8">
            <div>
                <h2 class="section-title">What counts as a CSO</h2>
                <p class="mt-4 max-w-[62ch] leading-relaxed text-muted">
                    A civil society organization is a non-government, non-profit group organized around a shared community
                    interest: people's organizations, cooperatives, neighborhood associations, and non-government
                    organizations among them. Under the Local Government Code, these groups are the LGU's partners in local
                    governance, and accredited CSOs may sit on Local Special Bodies such as the Local Development Council and
                    the Local School Board.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('about') }}" class="btn-primary px-6">About the program</a>
                    <a href="{{ route('accreditation') }}#paper-forms" class="btn-secondary px-6">Get the paper forms</a>
                </div>
            </div>
            <figure class="reveal-photo overflow-hidden rounded-lg">
                <img src="{{ asset('images/events/cso2.jpg') }}" loading="lazy" width="1200" height="800"
                     alt="The Civil Society Desk Office addressing a CSO assembly at the Lingayen municipal covered court"
                     class="aspect-[3/2] h-full w-full object-cover">
            </figure>
        </div>
    </section>

    {{-- The record, told as a sentence with real counts, then where the work happens. --}}
    <section aria-labelledby="record-title" class="mx-auto max-w-7xl px-4 py-section sm:px-6 lg:px-8">
        <h2 id="record-title" class="section-title">What the record shows</h2>
        <p class="mt-6 max-w-[40ch] font-serif text-[clamp(1.35rem,2.4vw,1.85rem)] leading-snug text-ink">
            <span class="font-bold tabular-nums text-brand-800"><span aria-hidden="true" data-count-to="{{ $accredited }}">{{ number_format($accredited) }}</span><span class="sr-only">{{ number_format($accredited) }}</span></span>
            accredited {{ Str::plural('organization', $accredited) }} {{ $accredited === 1 ? 'has' : 'have' }} logged
            <span class="font-bold tabular-nums text-brand-800"><span aria-hidden="true" data-count-to="{{ $verified }}">{{ number_format($verified) }}</span><span class="sr-only">{{ number_format($verified) }}</span></span>
            verified {{ Str::plural('activity', $verified) }}, reaching about
            <span class="font-bold tabular-nums text-brand-800"><span aria-hidden="true" data-count-to="{{ $reached }}">{{ number_format($reached) }}</span><span class="sr-only">{{ number_format($reached) }}</span></span>
            residents in
            <span class="font-bold tabular-nums text-brand-800">{{ number_format($barangays) }}</span>
            {{ Str::plural('barangay', $barangays) }}.
        </p>
        <p class="mt-3 text-sm text-muted">Counted live from organizations' activity logs, after the Civil Society Desk Office verifies each one.</p>

        <div class="mt-12 grid gap-12 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <div>
                <h3 class="font-semibold text-ink">Where CSOs are working</h3>
                <ul class="mt-3 grid sm:grid-cols-2 sm:gap-x-8">
                    @foreach ($sectors as $sector)
                        <li class="border-b border-line">
                            <a href="{{ route('directory', ['sector' => $sector['name']]) }}"
                               class="group flex min-h-11 items-center gap-3 py-2 hover:text-brand-700">
                                <x-sector-icon :sector="$sector['name']" class="h-5 w-5 shrink-0 text-brand-600" />
                                <span class="text-sm text-ink group-hover:underline">{{ $sector['name'] }}</span>
                                <span class="ml-auto text-sm tabular-nums text-muted">{{ $sector['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @if ($featured->isNotEmpty())
                <div>
                    <h3 class="font-semibold text-ink">Recently accredited</h3>
                    <ul class="mt-3">
                        @foreach ($featured as $org)
                            <li class="border-b border-line">
                                <a href="{{ $org['url'] }}" class="group flex min-h-11 items-center gap-3 py-2">
                                    <x-sector-icon :sector="$org['sector']" class="h-5 w-5 shrink-0 text-accent-600" />
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-ink group-hover:text-brand-700 group-hover:underline">{{ $org['name'] }}</span>
                                        <span class="block text-xs text-muted">Brgy. {{ $org['barangay'] }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('directory') }}" class="mt-4 inline-flex items-center gap-tight text-sm font-semibold text-brand-700 hover:underline">
                        View the full directory <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- The legal mandate, quoted from the Local Government Code. --}}
    <section class="bg-band">
        <figure class="mx-auto max-w-3xl px-4 py-12 sm:px-6 sm:text-center lg:px-8 lg:py-section">
            <blockquote class="font-serif text-lg leading-relaxed text-brand-900 sm:text-xl lg:text-2xl">
                “Local government units shall promote the establishment and operation of people's and non-governmental
                organizations to become active partners in the pursuit of local autonomy.”
            </blockquote>
            <figcaption class="mt-4 text-sm font-semibold text-muted">
                Local Government Code of 1991 (RA 7160), Section 34
            </figcaption>
        </figure>
    </section>

    {{-- Latest updates: the newest post leads, the rest follow as a list. --}}
    @if ($news->isNotEmpty())
        @php $lead = $news->first(); @endphp
        <section class="mx-auto max-w-7xl px-4 py-section sm:px-6 lg:px-8">
            <h2 class="section-title">Latest updates</h2>
            <div class="mt-8 grid gap-12 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                <a href="{{ route('resources') }}" class="group block rounded-lg border border-line bg-paper p-6 hover:border-brand-300 sm:p-8">
                    <p class="text-sm tabular-nums text-muted">{{ $lead['date'] }}</p>
                    <h3 class="mt-2 font-serif text-xl font-bold leading-snug text-brand-900 group-hover:underline sm:text-2xl">{{ $lead['title'] }}</h3>
                    <p class="mt-4 leading-relaxed text-muted">{{ $lead['excerpt'] }}</p>
                    <span class="mt-6 inline-flex items-center gap-tight text-sm font-semibold text-brand-700">
                        Read on the Resources page <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                    </span>
                </a>
                <ul class="divide-y divide-line">
                    @foreach ($news->skip(1) as $post)
                        <li class="py-6 first:pt-0">
                            <a href="{{ route('resources') }}" class="group block">
                                <p class="text-sm tabular-nums text-muted">{{ $post['date'] }}</p>
                                <h3 class="mt-1 font-semibold text-brand-900 group-hover:underline">{{ $post['title'] }}</h3>
                                <p class="mt-1 text-sm text-muted line-clamp-2">{{ $post['excerpt'] }}</p>
                            </a>
                        </li>
                    @endforeach
                    <li class="pt-6">
                        <a href="{{ route('resources') }}" class="text-sm font-semibold text-brand-700 hover:underline">All news and reports</a>
                    </li>
                </ul>
            </div>
        </section>
    @endif
</x-layouts.public>
