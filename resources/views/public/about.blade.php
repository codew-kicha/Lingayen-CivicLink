{{--
    About: what CSOs are, their role in local governance, how that role came to be, and the office
    that runs this site. Legal text is quoted from the 1987 Constitution, RA 7160 and DILG MC No.
    2025-060 (June 23, 2025). Confirm the circular's wording against the copy from DILG Lingayen.
--}}
@php
    $history = [
        ['1986', 'People Power', 'Civic, church and community groups join the EDSA People Power Revolution, a turning point for organized citizen participation.'],
        ['1987', 'The Constitution', 'The State commits to encourage non-governmental, community-based and sectoral organizations, and protects people\'s organizations\' right to take part in decision-making.'],
        ['1991', 'The Local Government Code', 'RA 7160 makes people\'s and non-governmental organizations partners of local governments, gives them seats on Local Special Bodies, and has the Sanggunian accredit them.'],
        ['2016 to 2022', 'DILG accreditation guidelines', 'A series of DILG circulars (MC 2016-97, MC 2019-72, MC 2022-083) sets how LGUs accredit CSOs and select their representatives.'],
        ['2021', 'CSO Desks and People\'s Councils', 'DILG MC 2021-012 asks LGUs to set up a CSO Desk and a People\'s Council to work with civil society.'],
        ['2025', 'The current guidelines', 'DILG MC No. 2025-060 (June 23, 2025) replaces the earlier circulars. The process on this site follows it.'],
    ];
    $bodies = [
        ['Local Development Council', 'At least one fourth of its members come from CSOs. It drafts the municipality\'s development plans and investment programs.'],
        ['Local Health Board', 'Includes a representative from a private or non-government organization involved in health services.'],
        ['Local School Board', 'Brings community representatives, such as the federation of parents-teachers associations, into decisions on local schools.'],
        ['Local Peace and Order Council', 'Includes three CSOs, selected from accredited organizations.'],
    ];
@endphp

<x-layouts.public title="About Us">
    <x-page-banner title="About Lingayen CivicLink" photo="events/cso3.jpg"
                   alt="CSO representatives facing the Civil Society Desk Office panel at an assembly in Lingayen"
                   lede="Run by the Civil Society Desk Office of the Public Employment Service Office, Lingayen, Pangasinan, to move CSO accreditation off paper and to keep a continuing public record of what accredited organizations do between accreditation cycles." />

    <div class="mx-auto grid max-w-7xl gap-12 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] lg:px-8 lg:py-section">
        <div class="space-y-section">
            <section id="why" class="scroll-mt-28" aria-labelledby="why-title">
                <h2 id="why-title" class="section-title">Why this exists</h2>
                <div class="mt-4 max-w-[58ch] space-y-4 leading-relaxed text-muted">
                    <p>
                        Accreditation in Lingayen has run on physical visits, printed forms, a logbook at the Sangguniang
                        Bayan Secretariat, and phone calls to ask where an application stands. Records go missing, applicants
                        wait without news, and the office has no structured way to report on what the sector contributes.
                    </p>
                    <p>
                        CivicLink digitizes that process end to end, then keeps going: accredited organizations log their
                        activities, the office verifies them, and the result is a living public record instead of a list
                        refreshed once every election cycle.
                    </p>
                </div>
                <div class="mt-8 grid grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] gap-3">
                    <figure class="reveal-photo overflow-hidden rounded-lg">
                        <img src="{{ asset('images/events/cso4.jpg') }}" alt="Organization representatives filling in registration forms at a CSO assembly"
                             loading="lazy" width="1200" height="800" class="aspect-[4/3] h-full w-full object-cover">
                    </figure>
                    <figure class="reveal-photo self-end overflow-hidden rounded-lg">
                        <img src="{{ asset('images/events/cso6.jpg') }}" alt="Members of a senior citizens association listening at the assembly"
                             loading="lazy" width="1200" height="800" class="aspect-[4/5] h-full w-full object-cover">
                    </figure>
                </div>
            </section>

            <section id="what" class="scroll-mt-28" aria-labelledby="what-title">
                <h2 id="what-title" class="section-title">What civil society organizations are</h2>
                <p class="mt-4 max-w-[58ch] leading-relaxed text-muted">
                    The DILG guidelines define a civil society organization as "a non-state and non-profit association that
                    works to improve society and the human condition." In Lingayen that includes:
                </p>
                <ul class="mt-6 grid max-w-3xl gap-x-8 gap-y-3 text-sm sm:grid-cols-2">
                    @foreach ([
                        ['People\'s organizations', 'Associations of citizens with an identifiable leadership, membership and structure, such as farmers\', fisherfolk\'s, women\'s and senior citizens\' groups.'],
                        ['Non-government organizations', 'Non-profit, non-stock groups working for the development of communities and sectors.'],
                        ['Cooperatives', 'Member-owned groups registered with the Cooperative Development Authority.'],
                        ['Civic, professional and sectoral groups', 'Including transport groups such as TODAs, and organizations of OFW families and persons with disabilities.'],
                    ] as $index => [$kind, $detail])
                        <li class="reveal flex gap-3" style="transition-delay: {{ $index * 60 }}ms">
                            <x-phosphor-check-circle class="mt-hair h-5 w-5 shrink-0 text-accent-600" aria-hidden="true" />
                            <span><span class="font-semibold text-ink">{{ $kind }}.</span> <span class="text-muted">{{ $detail }}</span></span>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section id="purpose" class="scroll-mt-28" aria-labelledby="purpose-title">
                <h2 id="purpose-title" class="section-title">Their purpose in local governance</h2>
                <p class="mt-4 max-w-[58ch] leading-relaxed text-muted">
                    CSOs bring residents' needs into government decisions and carry programs into the barangays. The law
                    gives them that role in so many words:
                </p>
                <div class="mt-6 grid gap-6 md:grid-cols-2">
                    <figure class="reveal border-t-2 border-accent-500 pt-4">
                        <blockquote class="font-serif text-lg leading-relaxed text-brand-900">
                            "The right of the people and their organizations to effective and reasonable participation at all levels
                            of social, political, and economic decision-making shall not be abridged."
                        </blockquote>
                        <figcaption class="mt-2 text-sm font-semibold text-muted">1987 Constitution, Article XIII, Section 16</figcaption>
                    </figure>
                    <figure class="reveal border-t-2 border-accent-500 pt-4" style="transition-delay: 80ms">
                        <blockquote class="font-serif text-lg leading-relaxed text-brand-900">
                            "Local government units shall promote the establishment and operation of people's and non-governmental
                            organizations to become active partners in the pursuit of local autonomy."
                        </blockquote>
                        <figcaption class="mt-2 text-sm font-semibold text-muted">Local Government Code of 1991, Section 34</figcaption>
                    </figure>
                </div>
                <p class="mt-8 max-w-[58ch] leading-relaxed text-muted">
                    Under the same Code, the municipality may work with CSOs through joint ventures and cooperative
                    arrangements (Section 35) and may assist their economic, social, environmental and cultural projects, with
                    the Sanggunian's concurrence (Section 36). Accredited CSOs sit on the municipality's Local Special Bodies:
                </p>
                <dl class="mt-6 divide-y divide-line border-y border-line">
                    @foreach ($bodies as $index => [$body, $role])
                        <div class="reveal grid gap-1 py-4 sm:grid-cols-[14rem_minmax(0,1fr)] sm:gap-6" style="transition-delay: {{ $index * 60 }}ms">
                            <dt class="font-semibold text-ink">{{ $body }}</dt>
                            <dd class="text-sm leading-relaxed text-muted">{{ $role }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section id="history" class="scroll-mt-28" aria-labelledby="history-title">
                <h2 id="history-title" class="section-title reveal">How this came to be</h2>
                <ol class="relative mt-8">
                    <span class="reveal-line reveal-line-y absolute bottom-6 left-[4.5rem] top-2 hidden w-px bg-line-strong sm:block" aria-hidden="true"></span>
                    @foreach ($history as [$year, $title, $detail])
                        <li class="reveal relative grid gap-1 pb-8 last:pb-0 sm:grid-cols-[4.5rem_minmax(0,1fr)] sm:gap-8">
                            <p class="text-sm font-semibold tabular-nums text-brand-700 sm:pt-hair">{{ $year }}</p>
                            <div class="relative sm:pl-2">
                                <span class="absolute -left-[2.35rem] top-2 hidden h-3 w-3 rounded-full border-2 border-paper bg-accent-500 sm:block" aria-hidden="true"></span>
                                <h3 class="font-semibold text-ink">{{ $title }}</h3>
                                <p class="mt-1 max-w-[62ch] text-sm leading-relaxed text-muted">{{ $detail }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section id="office" class="scroll-mt-28" aria-labelledby="office-title">
                <h2 id="office-title" class="section-title">Lingayen and the Civil Society Desk Office</h2>
                <div class="mt-4 max-w-[58ch] space-y-4 leading-relaxed text-muted">
                    <p>
                        Lingayen is the capital of Pangasinan, with 32 barangays. Its Civil Society Desk Office works within the
                        Public Employment Service Office, which every municipality maintains under the PESO Act (RA 8759, as
                        amended by RA 10691).
                    </p>
                    <p>
                        The desk receives accreditation applications, tracks them through the Sangguniang Bayan, keeps the
                        directory of accredited organizations, and verifies the community work they report.
                    </p>
                </div>
            </section>

            <section id="officials" class="scroll-mt-28" aria-labelledby="officials-title">
                <h2 id="officials-title" class="section-title">Office officials</h2>
                <ul class="mt-6 divide-y divide-line border-y border-line">
                    @foreach ($officials as $official)
                        <li class="flex flex-wrap items-baseline gap-x-4 gap-y-1 py-3">
                            <span class="font-medium text-ink">{{ $official['name'] }}</span>
                            <span class="text-sm text-muted">{{ $official['position'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{--
            The rail follows the reader: where they are on this long page (the current section lights up as
            it scrolls into view), then how to reach the office.
        --}}
        <aside class="h-fit space-y-6 lg:sticky lg:top-24" x-data="scrollSpy(['why', 'what', 'purpose', 'history', 'office', 'officials'])">
            <nav aria-label="On this page" class="hidden lg:block">
                <p class="text-sm font-semibold text-ink">On this page</p>
                <ul class="mt-3 border-l border-line text-sm">
                    @foreach ([
                        'why' => 'Why this exists',
                        'what' => 'What civil society organizations are',
                        'purpose' => 'Their purpose in local governance',
                        'history' => 'How this came to be',
                        'office' => 'Lingayen and the Desk Office',
                        'officials' => 'Office officials',
                    ] as $id => $label)
                        <li>
                            <a href="#{{ $id }}" :aria-current="current === '{{ $id }}' ? 'true' : null"
                               class="ml-2 flex min-h-9 items-center rounded-sm px-3 text-muted transition-colors duration-fast hover:bg-brand-50 hover:text-brand-800
                                      aria-[current=true]:bg-brand-50 aria-[current=true]:font-semibold aria-[current=true]:text-brand-900">{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <section class="rounded-lg bg-brand-900 p-6 text-brand-100" aria-labelledby="office-details-title">
                <h2 id="office-details-title" class="font-serif text-xl font-bold text-paper">{{ config('office.office.name') }}</h2>
                <p class="mt-1 text-sm">{{ config('office.office.parent') }}</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li class="flex gap-3"><x-phosphor-map-pin class="mt-hair h-5 w-5 shrink-0 text-accent-400" aria-hidden="true" /><span>{{ config('office.office.address') }}</span></li>
                    <li class="flex gap-3"><x-phosphor-clock class="mt-hair h-5 w-5 shrink-0 text-accent-400" aria-hidden="true" /><span>{{ config('office.office.hours') }}</span></li>
                    <li class="flex gap-3"><x-phosphor-phone class="mt-hair h-5 w-5 shrink-0 text-accent-400" aria-hidden="true" />
                        <a href="tel:{{ config('office.office.phone_link') }}" class="tabular-nums text-paper underline-offset-4 hover:underline">{{ config('office.office.phone') }}</a></li>
                    <li class="flex gap-3"><x-phosphor-envelope-simple class="mt-hair h-5 w-5 shrink-0 text-accent-400" aria-hidden="true" />
                        <a href="mailto:{{ config('office.office.email') }}" class="break-all text-paper underline-offset-4 hover:underline">{{ config('office.office.email') }}</a></li>
                    <li class="flex gap-3"><x-phosphor-facebook-logo class="mt-hair h-5 w-5 shrink-0 text-accent-400" aria-hidden="true" />
                        <a href="{{ config('office.office.facebook') }}" rel="noopener" target="_blank" class="text-paper underline-offset-4 hover:underline">facebook.com/pesolingayen</a></li>
                </ul>
                <a href="{{ route('contact') }}" class="btn-accent mt-6 w-full">Contact the office</a>
            </section>
        </aside>
    </div>
</x-layouts.public>
