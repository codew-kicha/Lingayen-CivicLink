<x-layouts.app header="Dashboard" subheader="Everything waiting on the PESO office today." :woven="true">
    {{-- Actionable counts are links; the three queues get a warning treatment while they have items. --}}
    @php
        $tiles = [
            ['Applications to review', $counts['awaiting_review'], route('admin.applications.index'), 'clipboard-text', true],
            ['Activities to verify', $counts['pending_activities'], route('admin.activities.index'), 'check-square', true],
            ['Expiring within 90 days', $counts['expiring_soon'], route('admin.organizations.index', ['status' => 'expiring']), 'calendar-x', true],
            ['Inactive CSOs', $counts['inactive'], route('admin.organizations.index', ['status' => 'inactive']), 'warning-circle', false],
            ['Active accreditations', $counts['active_accreditations'], null, 'seal-check', false],
        ];
    @endphp
    {{-- Two per row on phones (the last spans both), so the counts take half the screen they used to. --}}
    <section aria-label="Queue summary" class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-5">
        @foreach ($tiles as [$label, $value, $url, $icon, $urgent])
            @php $alert = $urgent && $value > 0; @endphp
            <{{ $url ? 'a' : 'div' }} @if ($url) href="{{ $url }}" @endif
                @class([
                    'card flex items-start gap-3 p-3 sm:gap-4 sm:p-4',
                    'col-span-2 sm:col-span-1' => $loop->last,
                    'border-warning/40 bg-warning-subtle' => $alert,
                    'transition-colors duration-fast ease-out-strong hover:border-primary/40' => $url && ! $alert,
                    'transition-colors duration-fast ease-out-strong hover:border-warning' => $alert,
                ])>
                <x-dynamic-component :component="'phosphor-'.$icon" @class(['h-5 w-5 shrink-0 sm:h-6 sm:w-6', 'text-warning' => $alert, 'text-muted' => ! $alert]) aria-hidden="true" />
                <div class="min-w-0">
                    <p @class(['text-page-title font-semibold tabular-nums', 'text-warning' => $alert, 'text-ink' => ! $alert])>{{ $value }}</p>
                    <p class="text-sm text-muted">{{ $label }}</p>
                    @if ($url)
                        <p @class(['mt-1 text-xs font-semibold', 'text-warning' => $alert, 'text-primary' => ! $alert])>
                            {{ $alert ? 'Review now' : ($urgent ? 'All clear' : 'View') }}
                        </p>
                    @endif
                </div>
            </{{ $url ? 'a' : 'div' }}>
        @endforeach
    </section>

    {{-- Latest score of each accredited organization, highest first. Two columns of five on wide screens. --}}
    <section class="card mt-6" aria-labelledby="top-title">
        <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 border-b border-line px-4 py-3">
            <h2 id="top-title" class="card-title">Top 10 performing CSOs</h2>
            <p class="text-xs text-muted">By performance score · informational only</p>
            <a href="{{ route('admin.scorecards.index') }}" class="ml-auto whitespace-nowrap text-sm font-semibold text-primary hover:underline">All scorecards</a>
        </div>

        @if ($topPerformers->isEmpty())
            <x-empty-state title="No scores yet." icon="chart-line-up">
                Scores appear once the office verifies activities, or after Recalculate on the Scorecards page.
            </x-empty-state>
        @else
            <ol class="grid gap-x-8 px-4 py-2 xl:grid-flow-col xl:grid-cols-2 xl:grid-rows-5">
                @foreach ($topPerformers as $score)
                    @php $percent = round($score->total_score * 100); @endphp
                    <li class="flex items-center gap-3 border-b border-line py-3 last:border-b-0 xl:[&:nth-child(5)]:border-b-0">
                        <span @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold tabular-nums',
                            'bg-accent text-accent-fg' => $loop->iteration <= 3,
                            'bg-canvas text-muted' => $loop->iteration > 3,
                        ])>{{ $loop->iteration }}<span class="sr-only">.</span></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline gap-3">
                                <a href="{{ route('admin.organizations.edit', $score->organization) }}" class="entity-link truncate">{{ $score->organization->name }}</a>
                                <span class="ml-auto text-sm font-semibold tabular-nums text-ink">{{ $percent }}%</span>
                            </div>
                            <p class="truncate text-xs text-muted">{{ $score->organization->sector }} · Brgy. {{ $score->organization->barangay }}</p>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-canvas" aria-hidden="true">
                                <div class="h-full rounded-full bg-primary" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card" aria-labelledby="apps-title">
            <div class="flex items-center gap-3 border-b border-line px-4 py-3">
                <h2 id="apps-title" class="card-title">Applications awaiting review</h2>
                <a href="{{ route('admin.applications.index') }}" class="ml-auto whitespace-nowrap text-sm font-semibold text-primary hover:underline">View all {{ $counts['awaiting_review'] }}</a>
            </div>

            @if ($recentApplications->isEmpty())
                <x-empty-state title="No applications are waiting for review." icon="clipboard-text" />
            @else
                <div class="table-wrap">
                    <table class="table-stack w-full">
                        <caption class="sr-only">Applications awaiting PESO review</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="table-head">Organization</th>
                                <th scope="col" class="table-head">Status</th>
                                <th scope="col" class="table-head w-32">Filed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentApplications as $application)
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <a href="{{ route('admin.applications.show', $application) }}" class="entity-link">{{ $application->organization->name }}</a>
                                        <x-application-type :type="$application->type" />
                                    </td>
                                    <td class="table-cell"><x-status-badge :status="$application->status" /></td>
                                    <td class="table-date" data-label="Filed">{{ $application->submitted_at?->format('d M Y') ?? 'Not filed' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card" aria-labelledby="acts-title">
            <div class="flex items-center gap-3 border-b border-line px-4 py-3">
                <h2 id="acts-title" class="card-title">Activities awaiting verification</h2>
                <a href="{{ route('admin.activities.index') }}" class="ml-auto whitespace-nowrap text-sm font-semibold text-primary hover:underline">View all {{ $counts['pending_activities'] }}</a>
            </div>

            @if ($recentActivities->isEmpty())
                <x-empty-state title="No activities are waiting for verification." icon="check-square" />
            @else
                <div class="table-wrap">
                    <table class="table-stack w-full">
                        <caption class="sr-only">Activities awaiting verification</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="table-head">Activity</th>
                                <th scope="col" class="table-head">Organization</th>
                                <th scope="col" class="table-head w-32">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentActivities as $activity)
                                <tr class="table-row">
                                    <td class="table-cell">
                                        <a href="{{ route('admin.activities.index') }}" class="entity-link">{{ $activity->title }}</a>
                                    </td>
                                    <td class="table-cell text-muted">{{ $activity->organization->name }}</td>
                                    <td class="table-date" data-label="Held">{{ $activity->activity_date->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
