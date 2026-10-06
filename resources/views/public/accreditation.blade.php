<x-layouts.public title="Accreditation">
    <x-page-banner title="Accreditation" photo="crop-rows.jpg"
                   lede="Who can apply, what you need to submit, and how the review runs from filing to Sangguniang Bayan endorsement." />

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <section>
            <h2 class="font-serif text-xl font-bold text-brand-900">Who can apply</h2>
            <p class="mt-4 max-w-[68ch] text-muted">
                Any non-government, non-profit organization operating in Lingayen with a verifiable
                membership base and at least one year of community work: people's organizations,
                cooperatives, neighborhood associations, civic clubs, and non-government
                organizations.
            </p>
        </section>

        <section class="mt-12">
            <h2 class="font-serif text-xl font-bold text-brand-900">How the review works</h2>
            <ol class="mt-6 grid gap-px overflow-hidden rounded-lg border border-line bg-line md:grid-cols-5">
                @foreach ($process as $step)
                    <li class="bg-paper p-6">
                        <p class="font-mono text-xs tabular-nums text-brand-600">Step {{ $loop->iteration }}</p>
                        <h3 class="mt-2 font-semibold text-ink">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm text-muted">{{ $step['detail'] }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="mt-12 grid gap-12 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <div>
                <h2 class="font-serif text-xl font-bold text-brand-900">What you need to submit</h2>
                <p class="mt-2 text-sm text-muted">
                    Scans or clear photographs are accepted. PDF, JPG, or PNG, up to 5 MB per file.
                </p>
                <ul class="mt-6 divide-y divide-line border-y border-line">
                    @foreach ($requirements as $requirement)
                        <li class="py-4">
                            <p class="font-medium text-ink">
                                {{ $requirement['label'] }}
                                @if ($requirement['optional'])
                                    <span class="ml-1 text-sm font-normal text-muted">Optional</span>
                                @endif
                            </p>
                            @if ($requirement['note'])
                                <p class="mt-1 text-sm text-muted">{{ $requirement['note'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <aside class="panel h-fit p-6">
                <h2 class="font-serif text-lg font-bold text-brand-900">Prefer to file on paper?</h2>
                <p class="mt-2 text-sm text-muted">
                    Download the application form, fill it in by hand, and bring it with two copies
                    of each requirement to the Civil Society Desk Office. Staff will encode it into
                    the same system, and you will be able to track it online afterwards.
                </p>
                <a href="{{ route('accreditation.form') }}" class="btn-secondary mt-4 w-full">Download the paper form (PDF)</a>
                <a href="{{ route('register') }}" class="btn-primary mt-2 w-full">Start an online application</a>
                <a href="{{ route('contact') }}" class="btn-ghost mt-2 w-full">Ask the CSO Desk Office</a>
            </aside>
        </section>
    </div>
</x-layouts.public>
