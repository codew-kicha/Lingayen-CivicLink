<x-layouts.app header="Dashboard" subheader="Everything waiting on the PESO office today.">
    {{-- Actionable counts are links; the three queues get a warning treatment while they have items. --}}
    @php
        $tiles = [
            ['Applications to review', $counts['awaiting_review'], route('admin.applications.index'), 'clipboard-text', true],
            ['Activities to verify', $counts['pending_activities'], route('admin.activities.index'), 'check-square', true],
            ['Expiring within 90 days', $counts['expiring_soon'], route('admin.organizations.index'), 'calendar-x', true],
            ['Inactive CSOs', $counts['inactive'], route('admin.organizations.index', ['status' => 'inactive']), 'warning-circle', false],
            ['Active accreditations', $counts['active_accreditations'], null, 'seal-check', false],
        ];
    @endphp
    <section aria-label="Queue summary" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($tiles as [$label, $value, $url, $icon, $urgent])
            @php $alert = $urgent && $value > 0; @endphp
            <{{ $url ? 'a' : 'div' }} @if ($url) href="{{ $url }}" @endif
                @class([
                    'card flex items-start gap-4 p-4',
                    'border-warning/40 bg-warning-subtle' => $alert,
                    'transition-colors duration-fast ease-out-strong hover:border-primary/40' => $url && ! $alert,
                    'transition-colors duration-fast ease-out-strong hover:border-warning' => $alert,
                ])>
                <x-dynamic-component :component="'phosphor-'.$icon" @class(['h-6 w-6 shrink-0', 'text-warning' => $alert, 'text-muted' => ! $alert]) aria-hidden="true" />
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

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="card" aria-labelledby="apps-title">
            <div class="flex items-center gap-3 border-b border-line px-4 py-3">
                <h2 id="apps-title" class="card-title">Applications awaiting review</h2>
                <a href="{{ route('admin.applications.index') }}" class="ml-auto whitespace-nowrap text-sm font-semibold text-primary hover:underline">View queue</a>
            </div>

            @if ($recentApplications->isEmpty())
                <x-empty-state title="No applications are waiting for review." icon="clipboard-text" />
            @else
                <div class="table-wrap">
                    <table class="w-full">
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
                                    <td class="table-date">{{ $application->submitted_at?->format('d M Y') ?? 'Not filed' }}</td>
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
                <a href="{{ route('admin.activities.index') }}" class="ml-auto whitespace-nowrap text-sm font-semibold text-primary hover:underline">View queue</a>
            </div>

            @if ($recentActivities->isEmpty())
                <x-empty-state title="No activities are waiting for verification." icon="check-square" />
            @else
                <div class="table-wrap">
                    <table class="w-full">
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
                                    <td class="table-date">{{ $activity->activity_date->format('d M Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-layouts.app>
