<x-layouts.public title="About Us">
    <div class="border-b border-line bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-semibold text-ink">About Lingayen CivicLink</h1>
            <p class="mt-3 max-w-[68ch] text-muted">
                Run by the Civil Society Desk Office of the Public Employment Service Office, Lingayen,
                Pangasinan, to move CSO accreditation off paper and to keep a continuing public record
                of what accredited organizations actually do between accreditation cycles.
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

                {{-- Decorative placeholder photos; swap for real CSO event photos later (§8). --}}
                <div class="mt-8 grid grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] gap-3">
                    <figure class="photo-duotone aspect-[4/3] rounded-lg">
                        <img src="{{ asset('images/stock/floating-market.jpg') }}" alt="" loading="lazy"
                             width="1600" height="1000">
                    </figure>
                    <figure class="photo-duotone aspect-[3/4] self-end rounded-lg sm:aspect-[4/5]">
                        <img src="{{ asset('images/stock/reading-session.jpg') }}" alt="" loading="lazy"
                             width="1600" height="1000">
                    </figure>
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
                    <dd class="mt-0.5 text-ink">{{ config('office.office.name') }}</dd>
                    <dd class="text-muted">{{ config('office.office.parent') }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Address</dt>
                    <dd class="mt-0.5 text-ink">{{ config('office.office.address') }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Office hours</dt>
                    <dd class="mt-0.5 text-ink">{{ config('office.office.hours') }}</dd>
                </div>
                <div>
                    <dt class="text-muted">Facebook</dt>
                    <dd class="mt-0.5">
                        <a href="{{ config('office.office.facebook') }}" rel="noopener" target="_blank"
                           class="text-navy-700 hover:underline">facebook.com/pesolingayen</a>
                    </dd>
                </div>
            </dl>
            <a href="{{ route('contact') }}" class="btn-secondary mt-6 w-full">Contact the office</a>
        </aside>
    </div>
</x-layouts.public>
