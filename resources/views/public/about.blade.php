<x-layouts.public title="About Us">
    <div class="border-b border-line bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-semibold text-ink">About Lingayen CivicLink</h1>
            <p class="mt-3 max-w-[68ch] text-muted">
                A service of the Public Employment Service Office of Lingayen, Pangasinan, built to
                move CSO accreditation off paper and to keep a continuing public record of what
                accredited organizations actually do between accreditation cycles.
            </p>
        </div>
    </div>

    <div class="mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] lg:px-8">
        <div class="space-y-12">
            <section>
                <h2 class="text-xl font-semibold text-ink">Why this exists</h2>
                <div class="mt-4 max-w-[68ch] space-y-4 text-muted">
                    <p>
                        Accreditation in Lingayen has run on physical visits, printed forms, a logbook
                        at the Sangguniang Bayan Secretariat, and phone calls to ask where an
                        application stands. Records go missing, applicants wait without news, and PESO
                        has no structured way to report on what the sector contributes.
                    </p>
                    <p>
                        CivicLink digitizes that process end to end, then keeps going: accredited
                        organizations log their activities, PESO verifies them, and the result is a
                        living public record instead of a list refreshed once every election cycle.
                    </p>
                </div>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-ink">Legal basis</h2>
                <div class="mt-4 max-w-[68ch] space-y-4 text-muted">
                    <p>
                        The Local Government Code of 1991 (RA 7160) directs local government units to
                        help establish and support people's organizations and non-government
                        organizations as partners in local governance. Local Special Bodies, including
                        the Local Development Council, Local Health Board, Local School Board, and
                        Local Peace and Order Council, are required by law to include CSO
                        representation.
                    </p>
                    <p>
                        DILG Memorandum Circular 2022-083 sets the accreditation and selection
                        guidelines LGUs follow when bringing CSOs into those bodies. The process in
                        this system follows that circular.
                    </p>
                </div>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-ink">Office officials</h2>
                <p class="mt-2 text-sm text-muted">Placeholder entries pending confirmation from the PESO office.</p>
                <ul class="mt-5 divide-y divide-line border-y border-line">
                    @foreach ($officials as $official)
                        <li class="flex flex-wrap items-baseline gap-x-4 gap-y-1 py-3">
                            <span class="font-medium text-ink">{{ $official['name'] }}</span>
                            <span class="text-sm text-muted">{{ $official['position'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <aside class="panel h-fit p-6">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted">Office details</h2>
            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="text-muted">Office</dt>
                    <dd class="mt-0.5 text-ink">Public Employment Service Office</dd>
                </div>
                <div>
                    <dt class="text-muted">Address</dt>
                    <dd class="mt-0.5 text-ink">Municipal Hall, Poblacion, Lingayen, Pangasinan</dd>
                </div>
                <div>
                    <dt class="text-muted">Office hours</dt>
                    <dd class="mt-0.5 text-ink">Monday to Friday, 8:00 AM to 5:00 PM</dd>
                </div>
            </dl>
            <a href="{{ route('contact') }}" class="btn-secondary mt-6 w-full">Contact the office</a>
        </aside>
    </div>
</x-layouts.public>
