<x-layouts.public :title="$organization->name">
    <div class="border-b border-line bg-surface">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <a href="{{ route('directory') }}" class="text-sm font-semibold text-navy-700 hover:underline">
                Back to the directory
            </a>
            <div class="mt-4 flex flex-wrap items-start gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-ink">{{ $organization->name }}</h1>
                    <p class="mt-1.5 text-muted">
                        {{ $organization->sector }} &middot; Brgy. {{ $organization->barangay }}, Lingayen
                    </p>
                </div>
                @if ($accreditation)
                    <div class="ml-auto text-right">
                        <x-status-badge :status="$accreditation->status" />
                        <p class="mt-2 text-sm text-muted">
                            Accredited until
                            <span class="font-mono tabular-nums">{{ $accreditation->expires_at->format('d M Y') }}</span>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        @if ($organization->advocacy)
            <section>
                <h2 class="text-xl font-semibold text-ink">Advocacy</h2>
                <p class="mt-3 max-w-[68ch] text-muted">{{ $organization->advocacy }}</p>
            </section>
        @endif

        <section class="mt-12">
            <h2 class="text-xl font-semibold text-ink">Verified activities</h2>
            <p class="mt-2 text-sm text-muted">
                Community work logged by this organization and verified by PESO.
            </p>

            @if ($activities->isEmpty())
                <div class="panel mt-5 p-8 text-center">
                    <p class="font-medium text-ink">No verified activities yet.</p>
                    <p class="mt-1 text-sm text-muted">Activities appear here once PESO has verified them.</p>
                </div>
            @else
                <ul class="mt-5 divide-y divide-line border-y border-line">
                    @foreach ($activities as $activity)
                        <li class="py-4">
                            <p class="font-mono text-xs tabular-nums text-muted">
                                {{ $activity->activity_date->format('d M Y') }}
                            </p>
                            <h3 class="mt-1 font-semibold text-ink">{{ $activity->title }}</h3>
                            <p class="mt-1 max-w-[68ch] text-sm text-muted">{{ $activity->description }}</p>
                            @if ($activity->participants_estimate)
                                <p class="mt-1.5 text-sm text-muted">
                                    Reached about
                                    <span class="font-mono tabular-nums">{{ number_format($activity->participants_estimate) }}</span>
                                    residents
                                </p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.public>
