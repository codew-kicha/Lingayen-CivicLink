<x-layouts.app header="Dashboard" subheader="Everything waiting on the PESO office today.">
    <section aria-label="Queue summary" class="panel-flat grid grid-cols-2 divide-line md:grid-cols-5 md:divide-x">
        @php
            $tiles = [
                ['Applications to review', $counts['awaiting_review'], route('admin.applications.index')],
                ['Activities to verify', $counts['pending_activities'], route('admin.activities.index')],
                ['Active accreditations', $counts['active_accreditations'], route('admin.organizations.index')],
                ['Expiring within 90 days', $counts['expiring_soon'], route('admin.organizations.index')],
                ['Inactive CSOs', $counts['inactive'], route('admin.organizations.index', ['status' => 'inactive'])],
            ];
        @endphp
        @foreach ($tiles as [$label, $value, $url])
            <a href="{{ $url }}"
               class="px-4 py-4 transition-colors duration-fast ease-out-strong hover:bg-primary-subtle">
                <p class="text-2xl tabular-nums text-ink">{{ $value }}</p>
                <p class="mt-1 text-sm text-muted">{{ $label }}</p>
            </a>
        @endforeach
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel-flat">
            <div class="flex items-center gap-3 border-b border-line px-4 py-3">
                <h2 class="font-semibold text-ink">Applications awaiting review</h2>
                <a href="{{ route('admin.applications.index') }}"
                   class="ml-auto text-sm font-semibold text-primary hover:underline">View queue</a>
            </div>

            @if ($recentApplications->isEmpty())
                <p class="px-4 py-12 text-center text-sm text-muted">
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
                                       class="entity-link">
                                        {{ $application->organization->name }}
                                    </a>
                                </td>
                                <td class="table-cell capitalize">{{ $application->type }}</td>
                                <td class="table-cell"><x-status-badge :status="$application->status" /></td>
                                <td class="table-date">
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
                   class="ml-auto text-sm font-semibold text-primary hover:underline">View queue</a>
            </div>

            @if ($recentActivities->isEmpty())
                <p class="px-4 py-12 text-center text-sm text-muted">
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
                                       class="entity-link">{{ $activity->title }}</a>
                                </td>
                                <td class="table-cell text-muted">{{ $activity->organization->name }}</td>
                                <td class="table-date">
                                    {{ $activity->activity_date->format('d M Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</x-layouts.app>
