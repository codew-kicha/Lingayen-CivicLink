<x-layouts.public title="Accreditation">
    <div class="border-b border-line bg-surface">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-semibold text-ink">Accreditation</h1>
            <p class="mt-3 max-w-[68ch] text-muted">
                Who can apply, what you need to submit, and how the review runs from filing to
                Sangguniang Bayan endorsement.
            </p>
        </div>
    </div>

    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <section>
            <h2 class="text-xl font-semibold text-ink">Who can apply</h2>
            <p class="mt-4 max-w-[68ch] text-muted">
                Any non-government, non-profit organization operating in Lingayen with a verifiable
                membership base and at least one year of community work: people's organizations,
                cooperatives, neighborhood associations, civic clubs, and non-government
                organizations.
            </p>
        </section>

        <section class="mt-14">
            <h2 class="text-xl font-semibold text-ink">How the review works</h2>
            <ol class="mt-6 grid gap-px overflow-hidden rounded-lg border border-line bg-line md:grid-cols-5">
                @foreach ($process as $step)
                    <li class="bg-paper p-5">
                        <p class="font-mono text-xs tabular-nums text-amber-700">Step {{ $loop->iteration }}</p>
                        <h3 class="mt-2 font-semibold text-ink">{{ $step['title'] }}</h3>
                        <p class="mt-1.5 text-sm text-muted">{{ $step['detail'] }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        <section class="mt-14 grid gap-12 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <div>
                <h2 class="text-xl font-semibold text-ink">What you need to submit</h2>
                <p class="mt-2 text-sm text-muted">
                    Scans or clear photographs are accepted. PDF, JPG, or PNG, up to 5 MB per file.
                </p>
                <ul class="mt-5 divide-y divide-line border-y border-line">
                    @foreach ($requirements as $requirement)
                        <li class="py-3.5">
                            <p class="font-medium text-ink">{{ $requirement['label'] }}</p>
                            <p class="mt-0.5 text-sm text-muted">{{ $requirement['note'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>

            <aside class="panel h-fit p-6">
                <h2 class="font-semibold text-ink">Prefer to file on paper?</h2>
                <p class="mt-2 text-sm text-muted">
                    Download the application form, fill it in by hand, and bring it to the PESO
                    office. Staff will encode it into the same system, and you will be able to track
                    it online afterwards.
                </p>
                <p class="mt-4 text-sm text-muted">
                    The downloadable form is being prepared and will be posted here.
                </p>
                <a href="{{ route('register') }}" class="btn-primary mt-6 w-full">Start an online application</a>
                <a href="{{ route('contact') }}" class="btn-ghost mt-2 w-full">Ask PESO a question</a>
            </aside>
        </section>
    </div>
</x-layouts.public>
