<x-layouts.admin header="Analytics & Reports"
                 subheader="Descriptive figures across the accredited sector. Aggregate only, no individual member data.">
    <x-slot:actions>
        <a href="{{ route('admin.analytics.export') }}" class="btn-primary">Export PDF report</a>
    </x-slot:actions>

    <section aria-label="Summary" class="panel-flat grid grid-cols-2 divide-line md:grid-cols-4 md:divide-x">
        @php
            $tiles = [
                ['Registered organizations', number_format($summary['organizations'])],
                ['Currently accredited', number_format($summary['accredited'])],
                ['Verified activities', number_format($summary['verifiedActivities'])],
                ['Residents reached', number_format($summary['residentsReached'])],
            ];
        @endphp
        @foreach ($tiles as [$label, $value])
            <div class="px-5 py-4">
                <p class="font-mono text-2xl tabular-nums text-navy-800">{{ $value }}</p>
                <p class="mt-1 text-sm text-muted">{{ $label }}</p>
            </div>
        @endforeach
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="panel-flat p-5">
            <h2 class="font-semibold text-ink">Sector distribution</h2>
            <p class="mt-0.5 text-sm text-muted">Accredited organizations by advocacy area.</p>
            <div class="mt-4">
                <x-bar-chart :data="$sectorDistribution" label="Accredited organizations by sector" />
            </div>
        </section>

        <section class="panel-flat p-5">
            <h2 class="font-semibold text-ink">Activity volume</h2>
            <p class="mt-0.5 text-sm text-muted">Verified activities per month, last 12 months.</p>
            <div class="mt-4">
                <x-column-chart :series="$activityTrend" label="Verified activity volume" />
            </div>
        </section>

        <section class="panel-flat p-5">
            <h2 class="font-semibold text-ink">Compliance trend</h2>
            <p class="mt-0.5 text-sm text-muted">
                Applications filed (light) against applications approved (dark), per month.
            </p>
            <div class="mt-4">
                <x-column-chart :series="$complianceTrend" value-key="approved" second-key="filed"
                                label="Applications filed and approved" />
            </div>
        </section>

        <section class="panel-flat">
            <div class="border-b border-line p-5 pb-3">
                <h2 class="font-semibold text-ink">Top contributors</h2>
                <p class="mt-0.5 text-sm text-muted">Organizations by verified activity count.</p>
            </div>

            @if ($topContributors->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-muted">
                    No verified activities have been logged yet.
                </p>
            @else
                <table class="w-full">
                    <caption class="sr-only">Organizations ranked by verified activities</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head text-right">Activities</th>
                            <th scope="col" class="table-head text-right">Reach</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topContributors as $organization)
                            <tr class="table-row">
                                <td class="table-cell font-medium">{{ $organization->name }}</td>
                                <td class="table-num">{{ $organization->verified_count }}</td>
                                <td class="table-num">{{ number_format((int) $organization->reach) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</x-layouts.admin>
