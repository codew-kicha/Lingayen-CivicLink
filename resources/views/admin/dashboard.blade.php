<x-layouts.admin header="Dashboard" subheader="Everything waiting on the PESO office today.">
    <section aria-label="Queue summary" class="panel-flat grid grid-cols-2 divide-line md:grid-cols-4 md:divide-x">
        @php
            $tiles = [
                ['Applications to review', $counts['awaiting_review'], 'admin.applications.index'],
                ['Activities to verify', $counts['pending_activities'], 'admin.activities.index'],
                ['Active accreditations', $counts['active_accreditations'], 'admin.organizations.index'],
                ['Expiring within 90 days', $counts['expiring_soon'], 'admin.organizations.index'],
            ];
        @endphp
        @foreach ($tiles as [$label, $value, $route])
            <a href="{{ route($route) }}"
               class="px-5 py-4 transition-colors duration-fast ease-out-strong hover:bg-navy-50">
                <p class="font-mono text-2xl tabular-nums text-navy-800">{{ $value }}</p>
                <p class="mt-1 text-sm text-muted">{{ $label }}</p>
            </a>
        @endforeach
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel-flat">
            <div class="flex items-center gap-3 border-b border-line px-4 py-3">
                <h2 class="font-semibold text-ink">Applications awaiting review</h2>
                <a href="{{ route('admin.applications.index') }}"
                   class="ml-auto text-sm font-semibold text-navy-700 hover:underline">View queue</a>
            </div>

            @if ($recentApplications->isEmpty())
                <p class="px-4 py-10 text-center text-sm text-muted">
                    No applications are waiting for review.
                </p>
            @else
                <table class="w-full">
                    <caption class="sr-only">Applications awaiting PESO review</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head">Type</th>
                            <th scope="col" class="table-head">Status</th>
                            <th scope="col" class="table-head">Filed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentApplications as $application)
                            <tr class="table-row">
                                <td class="table-cell">
                                    <a href="{{ route('admin.applications.show', $application) }}"
                                       class="font-medium text-navy-700 hover:underline">
                                        {{ $application->organization->name }}
                                    </a>
                                </td>
                                <td class="table-cell capitalize">{{ $application->type }}</td>
                                <td class="table-cell"><x-status-badge :status="$application->status" /></td>
                                <td class="table-cell font-mono tabular-nums text-muted">
                                    {{ $application->submitted_at?->format('d M Y') ?? 'Not filed' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="panel-flat">
            <div class="flex items-center gap-3 border-b border-line px-4 py-3">
                <h2 class="font-semibold text-ink">Activities awaiting verification</h2>
                <a href="{{ route('admin.activities.index') }}"
                   class="ml-auto text-sm font-semibold text-navy-700 hover:underline">View queue</a>
            </div>

            @if ($recentActivities->isEmpty())
                <p class="px-4 py-10 text-center text-sm text-muted">
                    No activities are waiting for verification.
                </p>
            @else
                <table class="w-full">
                    <caption class="sr-only">Activities awaiting verification</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head">Activity</th>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentActivities as $activity)
                            <tr class="table-row">
                                <td class="table-cell">
                                    <a href="{{ route('admin.activities.index') }}"
                                       class="font-medium text-navy-700 hover:underline">{{ $activity->title }}</a>
                                </td>
                                <td class="table-cell text-muted">{{ $activity->organization->name }}</td>
                                <td class="table-cell font-mono tabular-nums text-muted">
                                    {{ $activity->activity_date->format('d M Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</x-layouts.admin>
