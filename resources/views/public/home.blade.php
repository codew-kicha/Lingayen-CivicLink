@php
    $stepIcons = ['phosphor-file-text', 'phosphor-magnifying-glass', 'phosphor-clipboard-text', 'phosphor-buildings', 'phosphor-seal-check'];
    $statIcons = ['phosphor-seal-check', 'phosphor-handshake', 'phosphor-users-three', 'phosphor-map-pin'];
    $process = config('office.process');
    $featured = collect($featured);
    $news = collect($news);
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
        <div class="mx-auto grid max-w-7xl items-center gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)] lg:gap-14 lg:px-8 lg:py-14">
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
                           class="min-w-0 basis-full rounded-md border-0 bg-paper px-4 py-3 text-base text-ink placeholder:text-muted sm:basis-0 sm:flex-1
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
                <span class="hidden shrink-0 items-center gap-1.5 rounded-sm bg-accent-100 px-2.5 py-1 text-xs font-semibold text-brand-800 sm:inline-flex">
                    <x-phosphor-seal-check class="h-4 w-4" aria-hidden="true" />
                    Just verified
                </span>
                <div class="grid min-w-0 flex-1" aria-live="off" :aria-live="playing ? 'off' : 'polite'">
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

    {{-- The real process, in order: numbers carry meaning here. --}}
    <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="section-title">How accreditation works</h2>
            <p class="mt-5 text-muted">From filing to a seat on Lingayen's Local Special Bodies, every step is tracked here.</p>
        </div>

        <ol class="relative mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-5 lg:gap-6">
            <span class="absolute left-[10%] right-[10%] top-8 hidden h-px bg-line-strong lg:block" aria-hidden="true"></span>
            @foreach ($process as $index => $step)
                <li class="reveal relative text-center">
                    <span class="relative mx-auto flex h-16 w-16 items-center justify-center rounded-lg bg-brand-50 text-brand-700 ring-8 ring-paper">
                        <x-dynamic-component :component="$stepIcons[$index] ?? 'phosphor-circle'" class="h-8 w-8" aria-hidden="true" />
                        <span class="absolute -right-2 -top-2 flex h-6 w-6 items-center justify-center rounded-full bg-accent-500 font-mono text-xs font-semibold text-brand-900">{{ $index + 1 }}</span>
                    </span>
                    <h3 class="mt-5 font-semibold text-brand-900">{{ $step['title'] }}</h3>
                    <p class="mx-auto mt-2 max-w-[28ch] text-sm leading-relaxed text-muted">{{ $step['detail'] }}</p>
                </li>
            @endforeach
        </ol>

        <p class="mt-12 text-center">
            <a href="{{ route('accreditation') }}" class="btn-primary px-6">See the requirements</a>
        </p>
    </section>

    {{-- What is a CSO: text beside a photo. --}}
    <section class="bg-surface">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div>
                <h2 class="section-title text-left">What counts as a CSO</h2>
                <p class="mt-6 max-w-[62ch] leading-relaxed text-muted">
                    A civil society organization is a non-government, non-profit group organized around a shared community
                    interest: people's organizations, cooperatives, neighborhood associations, and non-government
                    organizations among them. Under the Local Government Code, these groups are the LGU's partners in local
                    governance, and accredited CSOs may sit on Local Special Bodies such as the Local Development Council and
                    the Local School Board.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('about') }}" class="btn-primary px-6">About the program</a>
                    <a href="{{ route('accreditation.form') }}" class="btn-secondary px-6">Download the paper form</a>
                </div>
            </div>
            <figure class="relative aspect-[4/3] overflow-hidden rounded-lg">
                <img src="{{ asset('images/stock/reading-session.jpg') }}" alt="" loading="lazy" width="1600" height="1000"
                     class="h-full w-full object-cover">
            </figure>
        </div>
    </section>

    {{-- Two bordered panels, after pcnc's benefits section. --}}
    <section class="mx-auto grid max-w-7xl gap-6 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div class="rounded-lg border border-line bg-paper p-8">
            <h2 class="font-serif text-xl font-bold text-brand-900">What accreditation opens up</h2>
            <ul class="mt-6 space-y-5">
                @foreach ([
                    ['Representation', 'Eligibility for seats on Local Special Bodies, including the Local Development Council and Local School Board.'],
                    ['Recognition', 'A listing in this public directory, with your verified community work beside your name.'],
                    ['A track record', 'Every verified activity is added to a public record of your organization\'s work in the community.'],
                    ['Partnership', 'A recognized channel for working with the municipal government on programs in your barangay.'],
                ] as [$title, $detail])
                    <li class="flex gap-4">
                        <x-phosphor-check-circle class="mt-0.5 h-6 w-6 shrink-0 text-accent-600" aria-hidden="true" />
                        <span>
                            <span class="block font-semibold text-ink">{{ $title }}</span>
                            <span class="mt-1 block text-sm leading-relaxed text-muted">{{ $detail }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="rounded-lg border border-line bg-paper p-8">
            <h2 class="font-serif text-xl font-bold text-brand-900">Where CSOs are working</h2>
            <p class="mt-2 text-sm text-muted">Accredited organizations by sector, as the Civil Society Desk Office classifies them.</p>
            <ul class="mt-4 grid sm:grid-cols-2 sm:gap-x-8">
                @foreach ($sectors as $sector)
                    <li class="border-b border-line">
                        <a href="{{ route('directory', ['sector' => $sector['name']]) }}"
                           class="group flex min-h-11 items-center gap-3 py-2.5 hover:text-brand-700">
                            <x-sector-icon :sector="$sector['name']" class="h-6 w-6 shrink-0 text-brand-600" />
                            <span class="text-sm text-ink group-hover:underline">{{ $sector['name'] }}</span>
                            <span class="ml-auto font-mono text-sm tabular-nums text-muted">{{ $sector['count'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Live figures over a photo (pcnc's Accomplishments band). Real counts, nothing rounded up. --}}
    <section aria-label="Transparency statistics" class="photo-wash photo-wash-even text-paper">
        <img src="{{ asset('images/stock/canoe-fishers.jpg') }}" alt="" loading="lazy" width="1600" height="1000">
        <div class="relative z-10 mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="section-title text-paper">What the record shows</h2>
                <p class="mt-5 text-brand-100">Counted live from organizations' activity logs, after the Civil Society Desk Office verifies each one.</p>
            </div>
            <dl class="mt-14 grid grid-cols-2 gap-y-12 lg:grid-cols-4">
                @foreach ($stats as $index => $stat)
                    <div class="flex flex-col items-center text-center">
                        <dt class="order-2 mt-3 text-sm text-brand-100">{{ $stat['label'] }}</dt>
                        <dd class="order-1">
                            <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-accent-500 text-brand-900">
                                <x-dynamic-component :component="$statIcons[$index] ?? 'phosphor-circle'" class="h-7 w-7" aria-hidden="true" />
                            </span>
                            <span class="font-serif text-4xl font-bold tabular-nums sm:text-5xl">
                                <span aria-hidden="true" data-count-to="{{ $stat['value'] }}">{{ number_format($stat['value']) }}</span>
                                <span class="sr-only">{{ number_format($stat['value']) }}</span>
                            </span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- The legal mandate, quoted from the Local Government Code. --}}
    <section class="bg-surface">
        <div class="mx-auto max-w-4xl px-4 py-20 sm:px-6 lg:px-8">
        <figure class="rounded-lg bg-paper px-6 py-12 text-center shadow-panel sm:px-14">
            <x-phosphor-quotes class="mx-auto h-9 w-9 text-accent-500" aria-hidden="true" />
            <blockquote class="mt-6 font-serif text-xl leading-relaxed text-brand-900 sm:text-2xl">
                Local government units shall promote the establishment and operation of people's and non-governmental
                organizations to become active partners in the pursuit of local autonomy.
            </blockquote>
            <figcaption class="mt-6 text-sm font-semibold text-muted">
                Local Government Code of 1991 (RA 7160), Section 34
            </figcaption>
        </figure>
        </div>
    </section>

    {{-- Call to action band with the newest accredited organizations. --}}
    <section class="bg-brand-700 text-paper">
        <div class="mx-auto max-w-7xl px-4 py-16 text-center sm:px-6 lg:px-8">
            <h2 class="font-serif text-[clamp(1.6rem,2.6vw,2.125rem)] font-bold">Join Lingayen's accredited CSO community</h2>
            <p class="mx-auto mt-3 max-w-[56ch] text-brand-100">Recently accredited by the Civil Society Desk Office:</p>

            @if ($featured->isNotEmpty())
                <ul class="mt-8 flex flex-wrap justify-center gap-3">
                    @foreach ($featured as $org)
                        <li>
                            <a href="{{ $org['url'] }}"
                               class="inline-flex items-center gap-2.5 rounded-md bg-brand-800 px-4 py-3 text-sm font-semibold
                                      transition-colors duration-fast ease-out-strong hover:bg-brand-900">
                                <x-sector-icon :sector="$org['sector']" class="h-5 w-5 text-accent-400" />
                                {{ $org['name'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-10 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="btn-accent px-6 py-3">Apply for accreditation</a>
                <a href="{{ route('directory') }}" class="btn-on-dark py-3">View the full directory</a>
            </div>
        </div>
    </section>

    {{-- Latest updates: the newest post leads, the rest follow as a list. --}}
    @if ($news->isNotEmpty())
        @php $lead = $news->first(); @endphp
        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 class="section-title">Latest updates</h2>
            </div>
            <div class="mt-12 grid gap-10 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                <a href="{{ route('resources') }}" class="group block rounded-lg border border-line bg-paper p-8 hover:border-brand-300">
                    <p class="font-mono text-xs uppercase tracking-wide text-muted">{{ $lead['date'] }}</p>
                    <h3 class="mt-3 font-serif text-2xl font-bold leading-snug text-brand-900 group-hover:underline">{{ $lead['title'] }}</h3>
                    <p class="mt-4 leading-relaxed text-muted">{{ $lead['excerpt'] }}</p>
                    <span class="mt-6 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700">
                        Read on the Resources page <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                    </span>
                </a>
                <ul class="divide-y divide-line">
                    @foreach ($news->skip(1) as $post)
                        <li class="py-5 first:pt-0">
                            <a href="{{ route('resources') }}" class="group block">
                                <p class="font-mono text-xs uppercase tracking-wide text-muted">{{ $post['date'] }}</p>
                                <h3 class="mt-1 font-semibold text-brand-900 group-hover:underline">{{ $post['title'] }}</h3>
                                <p class="mt-1 text-sm text-muted line-clamp-2">{{ $post['excerpt'] }}</p>
                            </a>
                        </li>
                    @endforeach
                    <li class="pt-5">
                        <a href="{{ route('resources') }}" class="text-sm font-semibold text-brand-700 hover:underline">All news and reports</a>
                    </li>
                </ul>
            </div>
        </section>
    @endif
</x-layouts.public>
