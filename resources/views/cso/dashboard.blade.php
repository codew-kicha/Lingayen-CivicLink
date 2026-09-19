<x-layouts.cso header="Dashboard" :subheader="$organization?->name">
    @if (! $organization)
        <section class="panel p-8 text-center">
            <h2 class="text-lg font-semibold text-ink">Set up your organization first</h2>
            <p class="mx-auto mt-2 max-w-[60ch] text-muted">
                Before you can apply for accreditation, tell us about your organization: its name,
                sector, barangay, advocacy, and officers.
            </p>
            <a href="{{ route('cso.profile.edit') }}" class="btn-primary mt-6">Set up organization profile</a>
        </section>
    @else
        <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <section class="panel p-5">
                    <h2 class="font-semibold text-ink">Accreditation status</h2>

                    @if ($accreditation)
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <x-status-badge status="active" />
                            <p class="text-sm text-muted">
                                Valid until
                                <span class="font-mono tabular-nums text-ink">{{ $accreditation->expires_at->format('d F Y') }}</span>
                                ({{ (int) now()->diffInDays($accreditation->expires_at, false) }} days remaining)
                            </p>
                        </div>
                        <p class="mt-3 text-sm text-muted">
                            Reference code
                            <span class="font-mono text-ink">{{ $accreditation->verification_code }}</span>
                        </p>
                    @elseif ($latestApplication)
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <x-status-badge :status="$latestApplication->status" />
                            <a href="{{ route('cso.applications.show', $latestApplication) }}"
                               class="text-sm font-semibold text-navy-700 hover:underline">View application</a>
                        </div>
                        <div class="mt-4">
                            <p class="text-sm font-medium text-muted">Sangguniang Bayan reading stage</p>
                            <div class="mt-2"><x-sb-stepper :stage="$latestApplication->sb_stage" /></div>
                        </div>
                    @else
                        <p class="mt-3 text-muted">
                            Your organization is not yet accredited and has no application on file.
                        </p>
                        <a href="{{ route('cso.applications.create') }}" class="btn-primary mt-4">
                            Apply for accreditation
                        </a>
                    @endif
                </section>

                <section class="panel p-5">
                    <h2 class="font-semibold text-ink">What PESO is waiting on</h2>

                    @php
                        $waiting = collect();
                        if ($pendingActivities) {
                            $waiting->push($pendingActivities.' '.Str::plural('activity', $pendingActivities).' awaiting verification');
                        }
                        foreach ($expiringDocuments as $document) {
                            $waiting->push(
                                config("document_types.{$document->document_type}", Str::headline($document->document_type))
                                .' expires '.$document->expires_at->format('d M Y')
                            );
                        }
                    @endphp

                    @if ($waiting->isEmpty())
                        <p class="mt-3 text-muted">Nothing outstanding. Your records are up to date.</p>
                    @else
                        <ul class="mt-3 divide-y divide-line border-y border-line">
                            @foreach ($waiting as $item)
                                <li class="py-2.5 text-sm text-ink">{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            <aside class="space-y-6">
                <section class="panel p-5">
                    <h2 class="font-semibold text-ink">Performance score</h2>

                    @if ($score)
                        <p class="mt-3 font-mono text-3xl tabular-nums text-navy-800">
                            {{ number_format($score->total_score * 100, 0) }}%
                        </p>
                        <dl class="mt-4 space-y-2 text-sm">
                            @foreach ([
                                'Activity frequency' => $score->activity_frequency,
                                'Community reach' => $score->community_reach,
                                'Compliance timeliness' => $score->compliance_timeliness,
                                'Document currency' => $score->document_currency,
                            ] as $label => $value)
                                <div class="flex gap-3">
                                    <dt class="text-muted">{{ $label }}</dt>
                                    <dd class="ml-auto font-mono tabular-nums text-ink">
                                        {{ number_format($value * 100, 0) }}%
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <p class="mt-3 text-sm text-muted">
                            No score computed yet. Your score appears once PESO verifies your first
                            logged activity.
                        </p>
                    @endif

                    <p class="mt-4 border-t border-line pt-3 text-xs text-muted">
                        This score is informational only. It does not affect accreditation or renewal
                        decisions.
                    </p>
                </section>

                <section class="panel p-5">
                    <h2 class="font-semibold text-ink">Activity log</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex gap-3">
                            <dt class="text-muted">Verified</dt>
                            <dd class="ml-auto font-mono tabular-nums text-ink">{{ $verifiedActivities }}</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="text-muted">Awaiting verification</dt>
                            <dd class="ml-auto font-mono tabular-nums text-ink">{{ $pendingActivities }}</dd>
                        </div>
                    </dl>
                    <a href="{{ route('cso.activities.index') }}" class="btn-secondary mt-4 w-full">Log an activity</a>
                </section>

                @if ($notifications->isNotEmpty())
                    <section class="panel p-5">
                        <h2 class="font-semibold text-ink">Recent updates</h2>
                        <ul class="mt-3 divide-y divide-line border-y border-line">
                            @foreach ($notifications as $notification)
                                <li class="py-2.5">
                                    <p class="text-sm font-medium text-ink">{{ $notification->data['headline'] ?? 'Update' }}</p>
                                    <p class="mt-0.5 text-sm text-muted">{{ $notification->data['body'] ?? '' }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </aside>
        </div>
    @endif
</x-layouts.cso>
