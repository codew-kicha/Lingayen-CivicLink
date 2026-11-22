@php
    $process = config('office.process');
    $featured = collect($featured);
    $news = collect($news);
    [$accredited, $verified, $reached, $barangays] = array_column($stats, 'value');
@endphp

<x-layouts.public>
    {{-- Hero: the municipality's own loop (public/videos/lingayen_hero.mp4), shown whole like pcnc.com.ph's
         hero. The smaller Lingayen seal and its flip are part of the video itself (pre-final defense
         panel), so nothing is laid over it. It plays on its own, muted and inline, looping with no
         controls (client's choice); heroVideo starts it so that visitors whose device asks for reduced
         motion or data saving get the poster (the video's first frame) and the video never downloads.
         ?v= is the file's date, so a replaced video is never served from an old cache. --}}
    @php $heroVersion = fn (string $file) => asset("videos/$file").'?v='.@filemtime(public_path("videos/$file")); @endphp
    <section x-data="heroVideo" aria-label="Lingayen's civil society organizations" class="relative bg-paper">
        <video x-ref="video" muted loop playsinline preload="none" aria-hidden="true" disablepictureinpicture
               poster="{{ $heroVersion('lingayen_hero-poster.jpg') }}"
               class="block aspect-video max-h-[78vh] w-full object-cover">
            <source src="{{ $heroVersion('lingayen_hero.mp4') }}" type="video/mp4">
            <img src="{{ $heroVersion('lingayen_hero-poster.jpg') }}" alt="" class="block aspect-video w-full object-cover">
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

    {{--
        The path to accreditation (public refinement, Nov 2026): the steps as a timeline beside what a
        CSO needs to know before starting (the fee comes after the form is approved), instead of five
        equal circles in a row.
    --}}
    <section aria-labelledby="process-title" class="mx-auto max-w-7xl px-4 py-section sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-12">
            <div class="lg:sticky lg:top-24 lg:self-start">
                <h2 id="process-title" class="section-title">How accreditation works</h2>
                <p class="mt-4 max-w-[48ch] text-lg leading-relaxed text-muted">
                    Four phases, from the online form to the committee hearing and approval. Each one is tracked on this
                    site, and the organization is notified as it moves.
                </p>
                <dl class="mt-8 grid grid-cols-2 border-y border-line">
                    <div class="py-4 pr-4">
                        <dt class="text-sm text-muted">New application fee</dt>
                        <dd class="mt-1 font-serif text-2xl font-bold tabular-nums text-brand-800">PHP 1,000</dd>
                    </div>
                    <div class="border-l border-line py-4 pl-4">
                        <dt class="text-sm text-muted">Renewal fee</dt>
                        <dd class="mt-1 font-serif text-2xl font-bold tabular-nums text-brand-800">PHP 500</dd>
                    </div>
                </dl>
                <p class="mt-3 text-sm text-muted">
                    Free within a free filing period, which a DILG memorandum opens after each new administration; accreditation then lasts
                    three years. Otherwise paid at the Municipal Treasury after the Desk Office approves your form, and renewed yearly.
                </p>
                <x-fee-period-note class="mt-3" />
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-primary px-6">Start an application</a>
                    <a href="{{ route('accreditation') }}" class="btn-secondary px-6">Requirements in full</a>
                </div>
            </div>
            <x-process-timeline :steps="$process" />
        </div>
    </section>

    {{-- What a CSO is, beside a photo from one of the office's CSO assemblies, and where accredited CSOs take part. --}}
    <section aria-labelledby="cso-title" class="bg-band">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 py-section sm:px-6 lg:grid-cols-2 lg:gap-12 lg:px-8">
            <figure class="overflow-hidden rounded-lg lg:order-first">
                <img src="{{ asset('images/events/cso2.jpg') }}" loading="lazy" width="1200" height="800"
                     alt="The Civil Society Desk Office addressing a CSO assembly at the Lingayen municipal covered court"
                     class="aspect-[4/3] h-full w-full object-cover">
            </figure>
            <div>
                <h2 id="cso-title" class="section-title">What counts as a CSO</h2>
                <p class="mt-4 max-w-[58ch] leading-relaxed text-muted">
                    A non-government, non-profit group organized around a shared community interest: people's organizations,
                    cooperatives, neighborhood and sectoral associations, and NGOs. Under the Local Government Code they are the
                    municipality's partners in local governance.
                </p>
                <h3 class="mt-8 font-semibold text-ink">Where accredited CSOs take part</h3>
                <ul class="mt-3 divide-y divide-line border-y border-line">
                    @foreach ([
                        ['Local Development Council', 'At least one fourth of its members'],
                        ['Local Health Board', 'One seat for a health organization'],
                        ['Local School Board', 'Community and parents-teachers representatives'],
                        ['Local Peace and Order Council', 'Three accredited CSOs'],
                    ] as [$body, $seat])
                        <li class="grid gap-1 py-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] sm:gap-6">
                            <span class="font-semibold text-brand-900">{{ $body }}</span>
                            <span class="text-sm text-muted sm:pt-hair">{{ $seat }}</span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('about') }}" class="btn-primary px-6">About the program</a>
                    <a href="{{ route('accreditation') }}#paper-forms" class="btn-secondary px-6">See the requirements</a>
                </div>
            </div>
        </div>
    </section>

    {{-- The record, told as a sentence with real counts, then where the work happens and who joined last. --}}
    @php $sectorMax = max(collect($sectors)->max('count') ?: 0, 1); @endphp
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
            of Lingayen's 32 {{ Str::plural('barangay', 32) }}.
        </p>
        <p class="mt-3 text-sm text-muted">Counted live from organizations' activity logs, after the Civil Society Desk Office verifies each one against its sign-in sheet.</p>

        <div class="mt-12 grid gap-12 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] lg:gap-12">
            <div>
                <h3 class="font-semibold text-ink">Accredited organizations by sector</h3>
                <ul class="mt-3 grid sm:grid-cols-2 sm:gap-x-8">
                    @foreach ($sectors as $sector)
                        <li class="border-b border-line">
                            <a href="{{ route('directory', ['sector' => $sector['name']]) }}"
                               class="group grid min-h-11 grid-cols-[1.25rem_minmax(0,1fr)_4rem_1.5rem] items-center gap-x-3 py-2">
                                <x-sector-icon :sector="$sector['name']" class="h-5 w-5 text-brand-600" />
                                <span class="truncate text-sm text-ink underline-offset-4 group-hover:text-brand-700 group-hover:underline">{{ $sector['name'] }}</span>
                                <span class="h-2 rounded-sm bg-brand-50" aria-hidden="true">
                                    <span class="block h-2 rounded-sm bg-brand-600 transition-colors duration-fast group-hover:bg-accent-500"
                                          style="width: {{ round($sector['count'] / $sectorMax * 100) }}%"></span>
                                </span>
                                <span class="text-right text-sm font-semibold tabular-nums text-brand-800">{{ $sector['count'] }}</span>
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
                                <a href="{{ $org['url'] }}" class="group grid min-h-11 grid-cols-[1.25rem_minmax(0,1fr)_auto] items-center gap-x-3 py-3">
                                    <x-sector-icon :sector="$org['sector']" class="h-5 w-5 text-accent-600" />
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-ink underline-offset-4 group-hover:text-brand-700 group-hover:underline">{{ $org['name'] }}</span>
                                        <span class="block text-xs text-muted">Brgy. {{ $org['barangay'] }}</span>
                                    </span>
                                    @if ($org['accredited_on'])
                                        <span class="text-xs tabular-nums text-muted">{{ \Illuminate\Support\Carbon::parse($org['accredited_on'])->format('M Y') }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('directory') }}" class="mt-4 inline-flex min-h-11 items-center gap-tight text-sm font-semibold text-brand-700 underline-offset-4 hover:underline">
                        View all {{ number_format($accredited) }} accredited organizations <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                    </a>
                </div>
            @endif
        </div>
    </section>

    {{-- Latest updates: the newest post leads, the rest follow as a list. --}}
    @if ($news->isNotEmpty())
        @php $lead = $news->first(); @endphp
        <section class="bg-band">
        <div class="mx-auto max-w-7xl px-4 py-section sm:px-6 lg:px-8">
            <h2 class="section-title">Latest updates</h2>
            <div class="mt-8 grid gap-12 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                <a href="{{ route('resources') }}" class="group block border-t-4 border-brand-800 pt-6">
                    <p class="text-sm tabular-nums text-muted">{{ $lead['date'] }}</p>
                    <h3 class="mt-2 font-serif text-2xl font-bold leading-snug text-brand-900 underline-offset-4 group-hover:underline sm:text-3xl">{{ $lead['title'] }}</h3>
                    <p class="mt-4 leading-relaxed text-muted">{{ $lead['excerpt'] }}</p>
                    <span class="mt-6 inline-flex items-center gap-tight text-sm font-semibold text-brand-700">
                        Read on the Resources page <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                    </span>
                </a>
                <ul class="divide-y divide-line border-t border-line">
                    @foreach ($news->skip(1) as $post)
                        <li class="py-6">
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
        </div>
        </section>
    @endif
</x-layouts.public>
