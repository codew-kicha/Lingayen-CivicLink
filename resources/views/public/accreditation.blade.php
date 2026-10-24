{{--
    Accreditation: why, who, how, and what to bring. Benefits and rules follow DILG MC No. 2025-060
    (June 23, 2025) and RA 7160; confirm wording against the copy from DILG Lingayen. The document
    list is the office's own (config/document_types.php).
--}}
@php
    $benefits = [
        ['phosphor-users-three', 'A vote on Local Special Bodies', 'Only accredited CSOs can be selected as voting representatives on the Local Development Council, Local Health Board, Local School Board and Local Peace and Order Council.'],
        ['phosphor-map-trifold', 'A say in the municipality\'s plans', 'The Local Development Council drafts Lingayen\'s development plans and investment programs, and at least one fourth of its members come from CSOs.'],
        ['phosphor-chalkboard-teacher', 'Training for your officers', 'Organizations selected for Local Special Bodies go through the CSO Capacity Development Program, funded by the LGU.'],
        ['phosphor-handshake', 'A recognized partner of the LGU', 'Accreditation also counts for taking part in local government programs, and makes your organization a known partner for joint projects in your barangay.'],
        ['phosphor-seal-check', 'A public record of your work', 'You get a listing in the public directory, with each activity the office verifies added to your organization\'s record.'],
    ];
@endphp

<x-layouts.public title="Accreditation">
    <x-page-banner title="Accreditation" photo="events/cso4.jpg"
                   alt="Organization representatives signing registration forms at a CSO assembly in Lingayen"
                   lede="Why it matters, who can apply, what to submit, and how the review runs from filing to Sangguniang Bayan endorsement." />

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-section">
        <section aria-labelledby="why-title">
            <h2 id="why-title" class="section-title reveal">Why get accredited</h2>
            <p class="reveal mt-4 max-w-[68ch] leading-relaxed text-muted">
                Accreditation by the Sangguniang Bayan is how a CSO earns a formal place in Lingayen's local governance.
            </p>
            <ul class="mt-8 grid gap-x-12 gap-y-8 md:grid-cols-2">
                @foreach ($benefits as $index => [$icon, $title, $detail])
                    <li class="reveal flex gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand-700">
                            <x-dynamic-component :component="$icon" class="h-6 w-6" aria-hidden="true" />
                        </span>
                        <span>
                            <span class="block font-semibold text-ink">{{ $title }}</span>
                            <span class="mt-1 block text-sm leading-relaxed text-muted">{{ $detail }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
            <p class="reveal mt-8 max-w-[72ch] rounded-md bg-band px-4 py-3 text-sm leading-relaxed text-muted">
                Good to know: CSOs can join the Local Development Council's sectoral and functional committees even without
                accreditation, and help from the municipality for projects is decided case by case by the Mayor with the
                Sangguniang Bayan's concurrence. Accreditation is not a promise of funding.
            </p>
        </section>

        <section class="mt-section grid gap-12 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]" aria-labelledby="who-title">
            <div class="reveal">
                <h2 id="who-title" class="section-title">Who can apply</h2>
                <p class="mt-4 max-w-[68ch] leading-relaxed text-muted">
                    Any non-government, non-profit organization working in Lingayen: people's organizations, cooperatives,
                    neighborhood associations, civic clubs, and non-government organizations. The Sangguniang Bayan looks for:
                </p>
                <ul class="mt-4 space-y-2 text-sm text-ink">
                    @foreach ([
                        'Registration with the SEC, CDA, DOLE or DHSUD, or accreditation by a national government agency',
                        'Purposes aimed at community development',
                        'At least one year of projects or activities in Lingayen',
                        'Annual meetings held and annual reports kept',
                    ] as $criterion)
                        <li class="flex gap-3">
                            <x-phosphor-check class="mt-1 h-4 w-4 shrink-0 text-accent-600" aria-hidden="true" />
                            {{ $criterion }}
                        </li>
                    @endforeach
                </ul>
                <p class="mt-4 max-w-[68ch] text-sm leading-relaxed text-muted">
                    Not registered yet? An organization that meets everything except registration can receive a Certificate
                    of Recognition. It can help fill seats on a Local Special Body but cannot vote.
                </p>
            </div>
            <figure class="reveal-photo overflow-hidden rounded-lg self-start">
                <img src="{{ asset('images/events/cso1.jpg') }}" alt="CSO members seated at an assembly in the Lingayen municipal covered court"
                     loading="lazy" width="500" height="333" class="aspect-[3/2] h-full w-full object-cover">
            </figure>
        </section>

        <section class="mt-section" aria-labelledby="how-title">
            <h2 id="how-title" class="section-title reveal">How the review works</h2>
            <ol class="relative mt-8 grid gap-8 lg:grid-cols-5 lg:gap-6">
                    @foreach ($process as $step)
                    <li class="reveal relative grid grid-cols-[2.5rem_minmax(0,1fr)] gap-x-4 lg:block">
                        @unless ($loop->last)
                            <span class="reveal-line reveal-line-y absolute bottom-[-2rem] left-5 top-10 w-px bg-line-strong lg:hidden" aria-hidden="true"></span>
                            <span class="reveal-line reveal-line-x absolute left-10 top-5 hidden h-px w-[calc(100%-1rem)] bg-line-strong lg:block" aria-hidden="true"></span>
                        @endunless
                        <span class="relative z-10 flex h-10 w-10 items-center justify-center rounded-full bg-brand-800 text-sm font-semibold tabular-nums text-paper ring-4 ring-paper">{{ $loop->iteration }}</span>
                        <div class="lg:mt-4">
                            <h3 class="font-semibold text-ink">{{ $step['title'] }}</h3>
                            <p class="mt-1 max-w-[34ch] text-sm leading-relaxed text-muted">{{ $step['detail'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
            <ul class="reveal mt-12 grid max-w-4xl gap-x-8 gap-y-2 text-sm text-muted sm:grid-cols-2">
                <li>Applications are accepted at any time of year.</li>
                <li>An organization sits on one Local Special Body only.</li>
                <li>How long accreditation lasts is set by the Sangguniang Bayan. The office currently issues it for three years.</li>
                <li>Partisan political activity is a ground for losing accreditation.</li>
            </ul>
        </section>

        <section class="mt-section grid gap-12 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]" aria-labelledby="submit-title">
            <div class="reveal">
                <h2 id="submit-title" class="section-title">What you need to submit</h2>
                <p class="mt-2 max-w-[62ch] text-sm text-muted">
                    As listed on the Sangguniang Bayan's accreditation forms. Filing online? Upload scans or clear photographs
                    instead: PDF, JPG, or PNG, up to 5 MB per file.
                </p>
                <div class="mt-6 grid gap-8 sm:grid-cols-2">
                    @foreach ($paperForms as $form)
                        <div>
                            <h3 class="font-semibold text-ink">{{ $form['title'] }}</h3>
                            @if ($form['note'])
                                <p class="mt-1 text-sm text-muted">{{ $form['note'] }}</p>
                            @endif
                            <ol class="mt-3 divide-y divide-line border-y border-line text-sm">
                                @foreach ($form['items'] as $item)
                                    <li class="flex gap-3 py-3">
                                        <span class="w-5 shrink-0 font-semibold tabular-nums text-brand-700">{{ chr(96 + $loop->iteration) }})</span>
                                        <span class="text-ink">{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>
            </div>

            <aside id="paper-forms" class="reveal panel h-fit scroll-mt-24 p-6 lg:sticky lg:top-24">
                <h2 class="card-title">Ready to apply?</h2>
                <p class="mt-2 text-sm text-muted">
                    Register your organization and file online, or download the form for a new application or a renewal, fill it
                    in by hand, and bring it with the requirements to the Civil Society Desk Office. Staff will encode it into the same system, and
                    you can track it online afterwards.
                </p>
                <a href="{{ route('register') }}" class="btn-primary mt-4 w-full">Start an online application</a>
                <a href="{{ route('accreditation.form', 'new') }}" class="btn-secondary mt-2 w-full">
                    <x-phosphor-file-pdf class="h-4 w-4" aria-hidden="true" />Form for new applications (PDF)
                </a>
                <a href="{{ route('accreditation.form', 'renewal') }}" class="btn-secondary mt-2 w-full">
                    <x-phosphor-file-pdf class="h-4 w-4" aria-hidden="true" />Form for renewals (PDF)
                </a>
                <a href="{{ route('contact') }}" class="btn-ghost mt-2 w-full">Ask the CSO Desk Office</a>
            </aside>
        </section>
    </div>
</x-layouts.public>
