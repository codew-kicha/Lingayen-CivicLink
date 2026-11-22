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
                   lede="Why it matters, who can apply, what to submit, and how it runs: application, validation, committee hearing, and approval." />

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-section">
        {{-- Why: five benefits and the honest caveat, closing the grid as its sixth tile. --}}
        <section id="why" class="scroll-mt-28" aria-labelledby="why-title">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)] lg:items-end lg:gap-12">
                <h2 id="why-title" class="section-title">Why get accredited</h2>
                <p class="max-w-[58ch] leading-relaxed text-muted">
                    Accreditation by the Sangguniang Bayan is how a CSO earns a formal place in Lingayen's local governance.
                </p>
            </div>
            <ul class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($benefits as [$icon, $title, $detail])
                    <li class="group flex gap-4 rounded-lg border border-line bg-paper p-6 transition duration-base ease-out-strong hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-panel">
                        <x-dynamic-component :component="$icon" class="mt-hair h-6 w-6 shrink-0 text-brand-600 transition-colors duration-base group-hover:text-accent-600" aria-hidden="true" />
                        <div>
                            <h3 class="font-semibold text-ink">{{ $title }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-muted">{{ $detail }}</p>
                        </div>
                    </li>
                @endforeach
                <li class="flex gap-4 rounded-lg bg-brand-900 p-6 text-brand-100">
                    <x-phosphor-info class="mt-hair h-6 w-6 shrink-0 text-accent-400" aria-hidden="true" />
                    <div>
                        <h3 class="font-semibold text-paper">Good to know</h3>
                        <p class="mt-1 text-sm leading-relaxed">
                            CSOs can join the Local Development Council's committees even without accreditation. Help for projects is
                            decided case by case by the Mayor with the Sangguniang Bayan. Accreditation is not a promise of funding.
                        </p>
                    </div>
                </li>
            </ul>
        </section>

        {{-- Who can apply, as a check the visitor ticks: the answer says which application fits. --}}
        <section id="who" class="mt-section grid scroll-mt-28 gap-8 lg:grid-cols-2 lg:gap-12" aria-labelledby="who-title">
            <div x-data="eligibilityCheck">
                <h2 id="who-title" class="section-title">Who can apply</h2>
                <p class="mt-4 max-w-[58ch] leading-relaxed text-muted">
                    Any non-government, non-profit organization working in Lingayen: people's organizations, cooperatives,
                    neighborhood associations, civic clubs, and NGOs. Tick what is true of your organization.
                </p>
                <fieldset class="mt-6">
                    <legend class="sr-only">What the Sangguniang Bayan looks for</legend>
                    <div class="divide-y divide-line rounded-lg border border-line bg-paper">
                        @foreach ([
                            'Registered with the SEC, CDA, DOLE or DHSUD, or accredited by a national government agency',
                            'Its purposes are aimed at community development',
                            'At least one year of projects or activities in Lingayen',
                            'Holds annual meetings and keeps annual reports',
                        ] as $index => $criterion)
                            <label class="flex min-h-11 cursor-pointer items-start gap-3 px-4 py-3 transition-colors duration-fast hover:bg-brand-50">
                                <input type="checkbox" x-model="met[{{ $index }}]" class="mt-hair h-5 w-5 shrink-0 rounded-sm border-line-strong text-primary">
                                <span class="text-ink">{{ $criterion }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="mt-4 min-h-20 rounded-lg px-4 py-3 text-sm transition-colors duration-base" aria-live="polite"
                     :class="{
                        'bg-canvas text-muted': verdict === 'none',
                        'bg-success-subtle text-success': verdict === 'accreditation',
                        'bg-accent-100 text-brand-900': verdict === 'recognition' || verdict === 'not-yet',
                     }">
                    <p x-show="verdict === 'none'">
                        The Sangguniang Bayan looks for all four. Not registered yet? An organization that meets the other three
                        can receive a Certificate of Recognition: it can help fill seats on a Local Special Body but cannot vote.
                    </p>
                    <div x-show="verdict === 'accreditation'" x-cloak class="flex flex-wrap items-center gap-3">
                        <p class="flex-1 font-semibold"><x-phosphor-seal-check class="mr-1 inline h-5 w-5 align-text-bottom" aria-hidden="true" />Your organization can apply for accreditation.</p>
                        <a href="{{ route('register') }}" class="btn-primary">Start an application</a>
                    </div>
                    <p x-show="verdict === 'recognition'" x-cloak>
                        <span class="font-semibold">Apply for a Certificate of Recognition.</span> It is for organizations that meet
                        everything except registration; it can help fill seats on a Local Special Body but cannot vote.
                    </p>
                    <p x-show="verdict === 'not-yet'" x-cloak>
                        <span class="font-semibold">Not yet: <span x-text="count"></span> of 4.</span>
                        The Sangguniang Bayan looks for all four.
                        <a href="{{ route('contact') }}" class="font-semibold underline underline-offset-4">Ask the Desk Office</a> what your organization still needs.
                    </p>
                </div>
            </div>
            <figure class="relative min-h-80 overflow-hidden rounded-lg">
                <img src="{{ asset('images/events/cso1.jpg') }}" alt="CSO members seated at an assembly in the Lingayen municipal covered court"
                     loading="lazy" width="1200" height="800" class="absolute inset-0 h-full w-full object-cover">
            </figure>
        </section>

        {{-- How the review works: pick a step and read it in full. --}}
        <section id="how" class="mt-section scroll-mt-28" aria-labelledby="how-title">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)] lg:items-end lg:gap-12">
                <h2 id="how-title" class="section-title">How the review works</h2>
                <p class="max-w-[58ch] leading-relaxed text-muted">
                    Choose a phase to see what happens, who acts, and what you receive. You follow every phase from your CivicLink
                    account, and the office's remarks after the committee hearing reach you by email.
                </p>
            </div>
            <x-fee-period-note class="mt-6" />
            <x-process-explorer :steps="$process" class="mt-8" />
            <ul class="mt-12 grid gap-4 border-y border-line py-6 text-sm text-muted sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['phosphor-calendar-check', 'Applications are accepted at any time of year.'],
                    ['phosphor-users-three', 'An organization sits on one Local Special Body only.'],
                    ['phosphor-hourglass', 'Filed within a free filing period: free, and valid for three years. Outside it: the fee applies, and it is renewed yearly.'],
                    ['phosphor-prohibit', 'Partisan political activity is a ground for losing accreditation.'],
                ] as [$icon, $rule])
                    <li class="flex gap-3">
                        <x-dynamic-component :component="$icon" class="h-5 w-5 shrink-0 text-brand-600" aria-hidden="true" />{{ $rule }}
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- What to submit: a checklist per application type, remembered on this device. --}}
        @php
            $formKeys = array_keys($paperForms);
            $counts = collect($paperForms)->map(fn ($form) => count($form['items']))->all();
            $madeOnline = fn (string $item) => str_contains($item, 'Accreditation Form') || str_contains($item, 'list of officers');
        @endphp
        <section id="submit" class="mt-section grid scroll-mt-28 gap-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:gap-12" aria-labelledby="submit-title"
                 x-data="requirementsChecklist(@js($counts))">
            <div>
                <h2 id="submit-title" class="section-title">What you need to submit</h2>
                <p class="mt-4 max-w-[58ch] leading-relaxed text-muted">
                    As listed on the Sangguniang Bayan's forms. The form and member list are filled in on the website; the rest are
                    uploaded after the Desk Office approves your form. Tick each one as you gather it.
                </p>
                <div class="mt-6 inline-flex rounded-md border border-line bg-canvas p-1" role="tablist" aria-label="Application type">
                    @foreach ($paperForms as $key => $form)
                        <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                                :aria-selected="(tab === '{{ $key }}').toString()" @click="tab = '{{ $key }}'"
                                class="inline-flex min-h-11 items-center gap-2 rounded-sm px-4 text-sm font-semibold transition-colors duration-fast"
                                :class="tab === '{{ $key }}' ? 'bg-paper text-brand-900 shadow-panel' : 'text-muted hover:text-ink'">
                            {{ $form['title'] }}
                            <span class="tabular-nums text-xs text-muted"><span x-text="ready('{{ $key }}')">0</span>/{{ count($form['items']) }}</span>
                        </button>
                    @endforeach
                </div>
                @foreach ($paperForms as $key => $form)
                    <div id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" x-show="tab === '{{ $key }}'" @if (! $loop->first) x-cloak @endif class="mt-4">
                        <div class="h-1 overflow-hidden rounded-full bg-brand-50" aria-hidden="true">
                            <div class="h-1 rounded-full bg-brand-600 transition-[width] duration-slow ease-out-strong"
                                 :style="`width: ${ready('{{ $key }}') / total('{{ $key }}') * 100}%`"></div>
                        </div>
                        <ul class="mt-3 divide-y divide-line rounded-lg border border-line bg-paper">
                            @foreach ($form['items'] as $item)
                                @php $id = $key.'.'.$loop->index; @endphp
                                <li>
                                    <label class="flex min-h-11 cursor-pointer items-start gap-3 px-4 py-3 transition-colors duration-fast hover:bg-brand-50">
                                        <input type="checkbox" :checked="ticked['{{ $id }}']" @change="toggle('{{ $id }}')"
                                               class="mt-hair h-5 w-5 shrink-0 rounded-sm border-line-strong text-primary">
                                        <span class="min-w-0 flex-1 text-ink" :class="ticked['{{ $id }}'] && 'text-muted line-through decoration-brand-300'">{{ $item }}</span>
                                        <span @class(['shrink-0 rounded-sm px-2 py-hair text-xs font-semibold',
                                                     'bg-success-subtle text-success' => $madeOnline($item),
                                                     'bg-canvas text-muted' => ! $madeOnline($item)])>{{ $madeOnline($item) ? 'Filled in online' : 'Upload' }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                        @if ($form['note'])
                            <p class="mt-3 text-sm text-muted">Filing on paper at the office? {{ $form['note'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <aside id="paper-forms" class="h-fit scroll-mt-24 rounded-lg bg-brand-900 p-6 text-brand-100 lg:sticky lg:top-24">
                <h2 class="font-serif text-2xl font-bold text-paper">Ready to apply?</h2>
                <p class="mt-3 text-sm leading-relaxed">
                    Register your organization, list your members, and fill in the accreditation form on the website. Your progress
                    is saved, so you can finish later. Without internet access? Bring the requirements to the Civil Society Desk
                    Office and staff will file them for you.
                </p>
                <a href="{{ route('register') }}" class="btn-accent mt-6 w-full">Register and apply online</a>
                <a href="{{ route('contact') }}" class="btn mt-2 w-full border border-brand-500 text-paper hover:bg-brand-800">Ask the CSO Desk Office</a>
            </aside>
        </section>
    </div>
</x-layouts.public>
