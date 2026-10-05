@php
    $query = array_filter(request()->only(['from', 'to']));
    $percent = fn (?float $ratio) => $ratio === null ? 'n/a' : round($ratio * 100).'%';
    $presets = [
        'Last 12 months' => [],
        'This year' => ['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()],
        (string) now()->subYear()->year => ['from' => now()->subYear()->startOfYear()->toDateString(), 'to' => now()->subYear()->endOfYear()->toDateString()],
    ];
@endphp

<x-layouts.admin header="Analytics & Reports"
                 subheader="Verified activity only, organization-level figures. No individual member data.">
    <x-slot:actions>
        <a href="{{ route('admin.analytics.export', $query) }}" class="btn-secondary">PDF report</a>
        <a href="{{ route('admin.analytics.excel', $query) }}" class="btn-primary">Excel workbook</a>
    </x-slot:actions>

    {{-- Reporting period: every section and both exports use it. --}}
    <form method="GET" class="panel-flat mb-6 flex flex-wrap items-end gap-3 p-4">
        <nav aria-label="Reporting period presets" class="flex flex-wrap gap-1">
            @foreach ($presets as $name => $range)
                <a href="{{ route('admin.analytics', $range) }}"
                   @class(['rounded-sm px-3 py-1.5 text-sm font-medium',
                           'bg-navy-600 text-paper' => $query == $range,
                           'text-navy-700 hover:bg-navy-50' => $query != $range])>{{ $name }}</a>
            @endforeach
        </nav>
        <div class="flex w-full flex-wrap items-end gap-2 sm:ml-auto sm:w-auto">
            <div class="min-w-0 flex-1 sm:flex-none">
                <label for="from" class="field-label">From</label>
                <input type="date" id="from" name="from" value="{{ $period->from->toDateString() }}" class="field-input @error('from') field-input-error @enderror">
            </div>
            <div class="min-w-0 flex-1 sm:flex-none">
                <label for="to" class="field-label">To</label>
                <input type="date" id="to" name="to" value="{{ $period->to->toDateString() }}" class="field-input">
            </div>
            <button type="submit" class="btn-ghost w-full sm:w-auto">Apply</button>
        </div>
        @error('from')<p class="field-error w-full">{{ $message }}</p>@enderror
        <p class="w-full text-sm text-muted">Showing {{ $period->periodLabel() }}.</p>
    </form>

    <section aria-label="Summary" class="panel-flat grid grid-cols-2 divide-line md:grid-cols-5 md:divide-x">
        @foreach ([
            ['Registered organizations', number_format($summary['organizations'])],
            ['Currently accredited', number_format($summary['accredited'])],
            ['Verified activities', number_format($summary['verifiedActivities'])],
            ['Residents reached', number_format($summary['residentsReached'])],
            ['Self-initiated', $percent($summary['selfInitiatedRatio'])],
        ] as [$label, $value])
            <div class="px-5 py-4">
                <p class="font-mono text-2xl tabular-nums text-navy-800">{{ $value }}</p>
                <p class="mt-1 text-sm text-muted">{{ $label }}</p>
            </div>
        @endforeach
    </section>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="panel-flat p-5">
            <h2 class="font-semibold text-ink">Self-initiated or LGU-organized, by sector</h2>
            <p class="mt-0.5 text-sm text-muted">
                Does the sector run its own community work, or mostly attend LGU events? The share is shown
                once a sector has {{ \App\Services\ReportData::MIN_FOR_RATIO }} or more activities with a known source.
            </p>
            <x-source-legend class="mt-3" />
            <div class="mt-3">
                <x-source-bars label="Verified activities by sector and source"
                               :rows="$sectorComparison->map(fn ($r) => ['label' => $r['sector']] + $r)" />
            </div>
        </section>

        <section class="panel-flat p-5">
            <h2 class="font-semibold text-ink">Activity volume</h2>
            <p class="mt-0.5 text-sm text-muted">Verified activities per month, by who organized them.</p>
            <x-source-legend class="mt-3" with-unknown />
            <div class="mt-3">
                <x-source-columns :series="$activityTrend" label="Verified activities per month" />
            </div>
        </section>
    </div>

    {{-- The inverse of Top contributors: who PESO should follow up with. --}}
    <section class="panel-flat mt-6">
        <div class="flex flex-wrap items-end gap-3 border-b border-line p-5 pb-3">
            <div>
                <h2 class="font-semibold text-ink">At-risk organizations</h2>
                <p class="mt-0.5 text-sm text-muted">
                    Accredited for at least {{ \App\Models\Organization::INACTIVE_AFTER_MONTHS }} months, with little or no
                    verified activity in this period, or only LGU-organized activity. Least active first.
                </p>
            </div>
            <span class="badge-warning ml-auto">{{ $atRisk->count() }}</span>
        </div>
        @if ($atRisk->isEmpty())
            <p class="px-5 py-10 text-center text-sm text-muted">No accredited organization is at risk in this period.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <caption class="sr-only">At-risk organizations</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head">Why</th>
                            <th scope="col" class="table-head text-right">Verified</th>
                            <th scope="col" class="table-head text-right">Self-initiated</th>
                            <th scope="col" class="table-head">Last verified activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($atRisk as $row)
                            <tr class="table-row">
                                <td class="table-cell">
                                    <a href="{{ route('admin.organizations.edit', $row['id']) }}" class="font-medium text-navy-700 hover:underline">{{ $row['name'] }}</a>
                                    <p class="text-xs text-muted">{{ $row['sector'] }} &middot; Brgy. {{ $row['barangay'] }}</p>
                                </td>
                                <td class="table-cell text-sm">
                                    @foreach ($row['reasons'] as $reason)
                                        <span class="badge-warning mb-1 mr-1">{{ $reason }}</span>
                                    @endforeach
                                </td>
                                <td class="table-num">{{ $row['verified'] }}</td>
                                <td class="table-num">{{ $row['independent'] }}</td>
                                <td class="table-cell font-mono text-sm tabular-nums text-muted">
                                    {{ $row['lastActivity']?->format('d M Y') ?? 'Never' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="panel-flat mt-6">
        <div class="border-b border-line p-5 pb-3">
            <h2 class="font-semibold text-ink">Sector comparison</h2>
            <p class="mt-0.5 text-sm text-muted">Accredited organizations only. Scores are informational and never affect renewal.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <caption class="sr-only">Sector comparison</caption>
                <thead>
                    <tr>
                        <th scope="col" class="table-head">Sector</th>
                        <th scope="col" class="table-head text-right">Accredited</th>
                        <th scope="col" class="table-head text-right">Verified activities</th>
                        <th scope="col" class="table-head text-right">Per organization</th>
                        <th scope="col" class="table-head text-right">Self-initiated share</th>
                        <th scope="col" class="table-head text-right">Average score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sectorComparison as $row)
                        <tr class="table-row">
                            <td class="table-cell font-medium">{{ $row['sector'] }}</td>
                            <td class="table-num">{{ $row['accredited'] }}</td>
                            <td class="table-num">{{ $row['verified'] }}</td>
                            <td class="table-num">{{ $row['perOrganization'] }}</td>
                            <td class="table-num">{{ $percent($row['ratio']) }}</td>
                            <td class="table-num">{{ $row['averageScore'] === null ? 'n/a' : number_format($row['averageScore'] * 100) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <section class="panel-flat">
            <div class="flex flex-wrap items-end gap-3 border-b border-line p-5 pb-3">
                <div>
                    <h2 class="font-semibold text-ink">Top contributors</h2>
                    <p class="mt-0.5 text-sm text-muted">By verified activities, including partner credit.</p>
                </div>
                <a href="{{ route('admin.analytics.awards', $query) }}" class="btn-ghost ml-auto px-3">Awards ranking (Excel)</a>
            </div>
            @if ($topContributors->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-muted">No verified activities in this period.</p>
            @else
                <table class="w-full">
                    <caption class="sr-only">Organizations ranked by verified activities</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="table-head">Organization</th>
                            <th scope="col" class="table-head text-right">Activities</th>
                            <th scope="col" class="table-head text-right">Reach</th>
                            <th scope="col" class="table-head text-right">Self-initiated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topContributors as $row)
                            <tr class="table-row">
                                <td class="table-cell font-medium">{{ $row['name'] }}</td>
                                <td class="table-num">{{ $row['verified'] }}</td>
                                <td class="table-num">{{ number_format($row['reach']) }}</td>
                                <td class="table-num">{{ $percent($row['ratio']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="panel-flat p-5">
            <h2 class="font-semibold text-ink">By barangay</h2>
            <p class="mt-0.5 text-sm text-muted">Verified activities led by organizations based in each barangay. Barangays with none are listed last.</p>
            <div class="mt-4 max-h-[28rem] overflow-y-auto pr-1">
                <x-bar-chart label="Verified activities by barangay"
                             :data="$barangays->pluck('activities', 'barangay')" />
            </div>
        </section>

        <section class="panel-flat p-5">
            <h2 class="font-semibold text-ink">Sector distribution</h2>
            <p class="mt-0.5 text-sm text-muted">Accredited organizations by sector, as of today.</p>
            <div class="mt-4">
                <x-bar-chart :data="$sectorDistribution" label="Accredited organizations by sector" />
            </div>
        </section>

        <section class="panel-flat p-5">
            <h2 class="font-semibold text-ink">Compliance trend</h2>
            <p class="mt-0.5 text-sm text-muted">Applications filed (light) against applications approved (dark), per month.</p>
            <div class="mt-4">
                <x-column-chart :series="$complianceTrend" value-key="approved" second-key="filed"
                                label="Applications filed and approved" />
            </div>
        </section>
    </div>
</x-layouts.admin>
