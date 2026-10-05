<x-layouts.public :title="$organization->name">
    <div class="border-b border-line bg-surface">
        <div class="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
            <a href="{{ route('directory') }}" class="text-sm font-semibold text-navy-700 hover:underline">
                Back to the directory
            </a>
            <div class="mt-5 flex flex-wrap items-start gap-5">
                @if ($organization->logo_path)
                    <img src="{{ asset('storage/'.$organization->logo_path) }}" alt="{{ $organization->name }} logo"
                         class="h-16 w-16 shrink-0 rounded-lg border border-line bg-paper object-contain p-1">
                @else
                    <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-navy-900 text-rose-400">
                        <x-sector-icon :sector="$organization->sector" class="h-8 w-8" />
                    </span>
                @endif
                <div class="min-w-0 flex-1">
                    <h1 class="text-2xl font-semibold text-ink [text-wrap:balance]">{{ $organization->name }}</h1>
                    <p class="mt-1.5 text-muted">
                        Brgy. {{ $organization->barangay }}, Lingayen &middot; {{ $organization->sector }}
                    </p>
                </div>
                @if ($accreditation)
                    <div class="sm:text-right">
                        <x-status-badge :status="$accreditation->status" />
                        <p class="mt-2 text-sm text-muted">
                            Accredited until
                            <span class="font-mono tabular-nums">{{ $accreditation->expires_at->format('d M Y') }}</span>
                        </p>
                    </div>
                @endif
            </div>

            <dl class="mt-8 grid grid-cols-3 divide-x divide-line border-y border-line">
                <div class="py-4 pr-4">
                    <dt class="text-sm text-muted">Verified activities</dt>
                    <dd class="mt-1 font-mono text-xl tabular-nums text-navy-800">
                        <span aria-hidden="true" data-count-to="{{ $ledger['count'] }}">{{ number_format($ledger['count']) }}</span>
                        <span class="sr-only">{{ number_format($ledger['count']) }}</span>
                    </dd>
                </div>
                <div class="px-4 py-4">
                    <dt class="text-sm text-muted">Residents reached</dt>
                    <dd class="mt-1 font-mono text-xl tabular-nums text-navy-800">
                        <span aria-hidden="true" data-count-to="{{ $ledger['reached'] }}">{{ number_format($ledger['reached']) }}</span>
                        <span class="sr-only">{{ number_format($ledger['reached']) }}</span>
                    </dd>
                </div>
                <div class="py-4 pl-4">
                    <dt class="text-sm text-muted">Logging since</dt>
                    <dd class="mt-1 font-mono text-xl tabular-nums text-navy-800">
                        {{ $ledger['since'] ? \Illuminate\Support\Carbon::parse($ledger['since'])->format('M Y') : 'Not yet' }}
                    </dd>
                </div>
            </dl>
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
            <h2 class="text-xl font-semibold text-ink">Activity record</h2>
            <p class="mt-2 text-sm text-muted">
                Community work logged by this organization and verified by the Civil Society Desk Office,
                newest first.
            </p>

            @if ($activities->isEmpty())
                <div class="panel mt-5 p-8 text-center">
                    <p class="font-medium text-ink">No verified activities yet.</p>
                    <p class="mt-1 text-sm text-muted">
                        Activities appear here once the Civil Society Desk Office has verified them.
                    </p>
                </div>
            @else
                <ol class="mt-5">
                    @foreach ($activities as $activity)
                        <li class="reveal grid gap-x-6 gap-y-1 border-b border-line py-4 sm:grid-cols-[7rem_minmax(0,1fr)]">
                            <p class="font-mono text-sm tabular-nums text-muted">
                                <time datetime="{{ $activity->activity_date->toDateString() }}">
                                    {{ $activity->activity_date->format('d M Y') }}
                                </time>
                            </p>
                            <div>
                                <h3 class="font-semibold text-ink">{{ $activity->title }}</h3>
                                <p class="mt-1 max-w-[68ch] text-sm text-muted">{{ $activity->description }}</p>
                                @php
                                    $others = $activity->partnerOrganizations->prepend($activity->organization)
                                        ->reject(fn ($org) => $org->id === $organization->id);
                                @endphp
                                @if ($others->isNotEmpty())
                                    <p class="mt-1 text-sm text-muted">
                                        With {{ $others->pluck('name')->join(', ', ' and ') }}
                                    </p>
                                @endif
                                <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted">
                                    <span class="inline-flex items-center gap-1.5 text-success-600">
                                        <x-phosphor-seal-check class="h-4 w-4" aria-hidden="true" />
                                        Verified {{ $activity->verified_at?->format('d M Y') }}
                                    </span>
                                    @if ($activity->participants_estimate)
                                        <span>
                                            About
                                            <span class="font-mono tabular-nums">{{ number_format($activity->participants_estimate) }}</span>
                                            residents
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>
    </div>
</x-layouts.public>
