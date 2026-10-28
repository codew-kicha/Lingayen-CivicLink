{{--
    The representative's home. Same building blocks as the admin dashboard: a row of summary tiles,
    then cards with a header row (title, optional link). The title bar and its actions stay pinned
    on desktop (layout `sticky`).
--}}
<x-layouts.app header="Dashboard" :subheader="$organization?->name" :sticky="(bool) $organization">
    @if (! $organization)
        <section class="card">
            <x-empty-state title="Set up your organization first" icon="user-circle">
                Before you can apply for accreditation, tell us about your organization: its name,
                sector, barangay, advocacy, and officers.
                <x-slot:action>
                    <a href="{{ route('cso.profile.edit') }}" class="btn-primary">Set up organization profile</a>
                </x-slot:action>
            </x-empty-state>
        </section>
    @else
        @php
            $daysLeft = $accreditation ? (int) now()->diffInDays($accreditation->expires_at, false) : null;
            $waiting = collect();
            if ($pendingActivities) {
                $waiting->push(['hourglass', $pendingActivities.' '.Str::plural('activity', $pendingActivities).' awaiting verification', false]);
            }
            foreach ($expiringDocuments as $document) {
                $waiting->push(['calendar-x', \App\Models\Document::label($document->document_type).' expires '.$document->expires_at->format('d M Y'), true]);
            }
            $tiles = [
                $accreditation
                    ? ['Accreditation', 'Active', "Until {$accreditation->expires_at->format('d M Y')} · {$daysLeft} days left", 'seal-check', null, $daysLeft <= 60]
                    : ($latestApplication
                        ? ['Accreditation', ucfirst(str_replace('_', ' ', $latestApplication->status)), 'Latest application', 'clipboard-text', route('cso.applications.show', $latestApplication), false]
                        : ['Accreditation', 'Not accredited', 'Apply when you are ready', 'seal-check', route('cso.applications.index', ['apply' => 1]), false]),
                ['Performance score', $score ? number_format($score->total_score * 100, 0).'%' : 'Not yet', 'Informational only', 'chart-line-up', null, false],
                ['Verified activities', $verifiedActivities, 'All time', 'check-circle', route('cso.activities.index'), false],
                ['Awaiting verification', $pendingActivities, 'Checked by the office', 'hourglass', route('cso.activities.index'), false],
            ];
        @endphp

        <x-slot:actions>
            @if ($latestApplication && ! $accreditation)
                <a href="{{ route('cso.applications.show', $latestApplication) }}" class="btn-secondary">View application</a>
            @elseif (! $accreditation)
                <a href="{{ route('cso.applications.index', ['apply' => 1]) }}" class="btn-secondary">Apply for accreditation</a>
            @endif
            @if ($accreditation)
                <a href="{{ route('cso.activities.index', ['log' => 1]) }}" class="btn-primary">
                    <x-phosphor-plus class="h-4 w-4" aria-hidden="true" />Log an activity
                </a>
            @endif
        </x-slot:actions>

        <section aria-label="Summary" class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            @foreach ($tiles as [$label, $value, $note, $icon, $url, $alert])
                <{{ $url ? 'a' : 'div' }} @if ($url) href="{{ $url }}" @endif
                    @class([
                        'card flex items-start gap-3 p-3 sm:gap-4 sm:p-4',
                        'border-warning/40 bg-warning-subtle' => $alert,
                        'transition-colors duration-fast ease-out-strong hover:border-primary/40' => $url,
                    ])>
                    <x-dynamic-component :component="'phosphor-'.$icon" @class(['h-5 w-5 shrink-0 sm:h-6 sm:w-6', 'text-warning' => $alert, 'text-muted' => ! $alert]) aria-hidden="true" />
                    <div class="min-w-0">
                        <p class="text-sm text-muted">{{ $label }}</p>
                        <p @class(['text-card-title font-semibold tabular-nums', 'text-warning' => $alert, 'text-ink' => ! $alert])>{{ $value }}</p>
                        <p class="mt-1 text-xs text-muted">{{ $note }}</p>
                    </div>
                </{{ $url ? 'a' : 'div' }}>
            @endforeach
        </section>

        <div class="mt-6 grid items-start gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <section class="card" aria-labelledby="status-title">
                    <div class="border-b border-line px-6 py-4">
                        <h2 id="status-title" class="card-title">Accreditation status</h2>
                    </div>
                    <div class="p-6">
                        @if ($accreditation)
                            <div class="flex flex-wrap items-center gap-3">
                                <x-status-badge status="active" />
                                <p class="text-sm text-muted">
                                    Valid until
                                    <span class="tabular-nums text-ink">{{ $accreditation->expires_at->format('d F Y') }}</span>
                                    ({{ $daysLeft }} days remaining)
                                </p>
                            </div>
                            <p class="mt-3 text-sm text-muted">
                                Reference code
                                <span class="font-mono text-ink">{{ $accreditation->verification_code }}</span>
                            </p>
                        @elseif ($latestApplication)
                            <div class="flex flex-wrap items-center gap-3">
                                <x-status-badge :status="$latestApplication->status" />
                                <a href="{{ route('cso.applications.show', $latestApplication) }}"
                                   class="text-sm font-semibold text-primary hover:underline">View application</a>
                            </div>
                            <p class="mt-6 text-sm font-semibold text-ink">Sangguniang Bayan reading stage</p>
                            <div class="mt-2"><x-sb-stepper :stage="$latestApplication->sb_stage" /></div>
                        @else
                            <p class="text-sm text-muted">Your organization is not yet accredited and has no application on file.</p>
                            <a href="{{ route('cso.applications.index', ['apply' => 1]) }}" class="btn-primary mt-4">Apply for accreditation</a>
                        @endif
                    </div>
                </section>

                <section class="card" aria-labelledby="recent-title">
                    <div class="flex items-center gap-3 border-b border-line px-6 py-4">
                        <h2 id="recent-title" class="card-title">Recent activities</h2>
                        <a href="{{ route('cso.activities.index') }}" class="ml-auto whitespace-nowrap text-sm font-semibold text-primary hover:underline">View all</a>
                    </div>
                    @if ($recentActivities->isEmpty())
                        <x-empty-state title="No activities logged yet." icon="check-square">
                            Log your projects and events so the office can verify them.
                        </x-empty-state>
                    @else
                        <div class="table-wrap">
                            <table class="table-stack w-full">
                                <caption class="sr-only">Your most recent activities</caption>
                                <thead>
                                    <tr>
                                        <th scope="col" class="table-head pl-6">Activity</th>
                                        <th scope="col" class="table-head">Status</th>
                                        <th scope="col" class="table-head w-32 pr-6">Held</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentActivities as $activity)
                                        <tr>
                                            <td class="table-cell pl-6 font-semibold text-ink">{{ $activity->title }}</td>
                                            <td class="table-cell"><x-status-badge :status="$activity->status" /></td>
                                            <td class="table-date pr-6" data-label="Held">{{ $activity->activity_date->format('d M Y') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>

            <div class="space-y-6">
                <section class="card" aria-labelledby="waiting-title">
                    <div class="border-b border-line px-6 py-4">
                        <h2 id="waiting-title" class="card-title">What the office is waiting on</h2>
                    </div>
                    @if ($waiting->isEmpty())
                        <p class="flex items-center gap-2 p-6 text-sm text-muted">
                            <x-phosphor-check-circle class="h-5 w-5 shrink-0 text-success" aria-hidden="true" />
                            Nothing outstanding. Your records are up to date.
                        </p>
                    @else
                        <ul class="divide-y divide-line px-6">
                            @foreach ($waiting as [$icon, $text, $warn])
                                <li class="flex items-start gap-3 py-3 text-sm text-ink">
                                    <x-dynamic-component :component="'phosphor-'.$icon" @class(['mt-px h-5 w-5 shrink-0', 'text-warning' => $warn, 'text-muted' => ! $warn]) aria-hidden="true" />
                                    {{ $text }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="card" aria-labelledby="score-title">
                    <div class="flex items-baseline gap-3 border-b border-line px-6 py-4">
                        <h2 id="score-title" class="card-title">Performance score</h2>
                        @if ($score)
                            <span class="ml-auto text-card-title font-semibold tabular-nums text-ink">{{ number_format($score->total_score * 100, 0) }}%</span>
                        @endif
                    </div>
                    <div class="p-6">
                        @if ($score)
                            <dl class="space-y-4 text-sm">
                                @foreach ([
                                    'Activity frequency' => $score->activity_frequency,
                                    'Community reach' => $score->community_reach,
                                    'Compliance timeliness' => $score->compliance_timeliness,
                                    'Document currency' => $score->document_currency,
                                ] as $label => $value)
                                    <div>
                                        <div class="flex gap-3">
                                            <dt class="text-muted">{{ $label }}</dt>
                                            <dd class="ml-auto tabular-nums text-ink">{{ number_format($value * 100, 0) }}%</dd>
                                        </div>
                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-canvas" aria-hidden="true">
                                            <div class="h-full rounded-full bg-primary" style="width: {{ round($value * 100) }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="text-sm text-muted">
                                No score computed yet. Your score appears once the office verifies your first logged activity.
                            </p>
                        @endif
                        <p class="mt-6 border-t border-line pt-3 text-xs text-muted">
                            This score is informational only. It does not affect accreditation or renewal decisions.
                        </p>
                    </div>
                </section>

                @if ($notifications->isNotEmpty())
                    <section class="card" aria-labelledby="updates-title">
                        <div class="border-b border-line px-6 py-4">
                            <h2 id="updates-title" class="card-title">Recent updates</h2>
                        </div>
                        <ul class="divide-y divide-line px-6">
                            @foreach ($notifications as $notification)
                                <li class="py-3">
                                    <p class="flex items-center gap-2 text-sm font-semibold text-ink">
                                        {{ $notification->data['headline'] ?? 'Update' }}
                                        @unless ($notification->read_at)<span class="badge-info">New</span>@endunless
                                        <span class="ml-auto text-xs font-normal text-muted">{{ $notification->created_at->diffForHumans() }}</span>
                                    </p>
                                    <p class="mt-1 text-sm text-muted">{{ $notification->data['body'] ?? '' }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </div>
    @endif
</x-layouts.app>
