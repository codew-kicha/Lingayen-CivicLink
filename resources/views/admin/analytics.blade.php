@php
    use App\Services\ReportData;
    use Illuminate\Support\Str;

    $query = array_filter(request()->only(['from', 'to']));
    $percent = fn (?float $ratio) => $ratio === null ? 'n/a' : round($ratio * 100).'%';
    $presets = [
        'Last 12 months' => [],
        'This year' => ['from' => now()->startOfYear()->toDateString(), 'to' => now()->toDateString()],
        (string) now()->subYear()->year => ['from' => now()->subYear()->startOfYear()->toDateString(), 'to' => now()->subYear()->endOfYear()->toDateString()],
    ];

    // Change against the period of the same length just before this one. Shares change in points.
    $change = function (int|float|null $now, int|float|null $then, bool $share = false): ?array {
        if ($now === null || $then === null) {
            return null;
        }
        $diff = $share ? (int) round(($now - $then) * 100) : $now - $then;
        $text = match (true) {
            $diff === 0 => 'No change',
            $share => ($diff > 0 ? '+' : '').$diff.' pts',
            // A percentage on a tiny base (+8900% from 1) says nothing; the earlier figure says more.
            default => ($diff > 0 ? '+' : '').number_format($diff).($then >= 10 && abs($diff / $then) < 10 ? ' ('.($diff > 0 ? '+' : '').round($diff / $then * 100).'%)' : ''),
        };

        return ['dir' => $diff <=> 0, 'text' => $text, 'then' => $share ? round($then * 100).'%' : number_format($then)];
    };

    $tiles = [
        'accredited' => ['Accredited now', $summary['accredited'], 'of '.number_format($summary['organizations']).' registered, as of today', null, 'seal-check'],
        'active' => ['Organizations with verified activity', $summary['activeOrganizations'], 'lead or partner, in this period', $change($summary['activeOrganizations'], $before['activeOrganizations']), 'users-three'],
        'activities' => ['Verified activities', $summary['verifiedActivities'], 'checked by the office, in this period', $change($summary['verifiedActivities'], $before['verifiedActivities']), 'check-square'],
        'reach' => ['Residents reached', $summary['residentsReached'], 'total attendance, in this period', $change($summary['residentsReached'], $before['residentsReached']), 'hand-heart'],
        'share' => ['Self-initiated share', null, 'run by the CSO itself, not the LGU', $change($summary['selfInitiatedRatio'], $before['selfInitiatedRatio'], true), 'compass'],
    ];

    $sourceName = ['independent' => 'Self-initiated', 'lgu_organized' => 'LGU-organized'];
    $sourceKey = ['independent' => 'bg-chart-self', 'lgu_organized' => 'bg-chart-lgu'];
    $bySector = $organizations->where('accredited', true)->groupBy('sector');
    $byBarangay = $organizations->groupBy('barangay');
    $peak = $activityTrend->sortByDesc('total')->first();
    $peakReach = $activityTrend->sortByDesc('reach')->first();
    $maxTotal = max($activityTrend->max('total') ?: 0, 1);
    $maxReach = max($activityTrend->max('reach') ?: 0, 1);
    $labelEvery = (int) ceil($activityTrend->count() / 12);
    $earlier = $period->yearsBack() === 1 ? 'a year earlier' : $period->yearsBack().' years earlier';
@endphp

<x-layouts.app header="Analytics & Reports"
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
                   @class(['rounded-sm px-3 py-2 text-sm font-semibold',
                           'bg-primary text-on-primary' => $query == $range,
                           'text-ink hover:bg-primary-subtle' => $query != $range])>{{ $name }}</a>
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
        <p class="w-full text-sm text-muted">
            Showing {{ $period->periodLabel() }}, compared with the same dates {{ $earlier }} ({{ $previous->periodLabel() }}).
        </p>
    </form>

    {{--
        The summary figures open to what is behind them (panel's request after the pre-final defense:
        the statistics were static and vague). Each says what it counts, how it moved against the
        period before, and opens a details panel; one at a time, Esc or the tile again closes it.
    --}}
    <div x-data="{ open: null, toggle(key) { this.open = this.open === key ? null : key } }" @keydown.escape.window="open = null">
        <section aria-label="Summary" class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-5">
            @foreach ($tiles as $key => [$label, $value, $note, $delta, $icon])
                <button type="button" @click="toggle('{{ $key }}')" :aria-expanded="(open === '{{ $key }}').toString()" aria-controls="figure-{{ $key }}"
                        @class(['card group flex items-start gap-3 p-3 text-left sm:gap-4 sm:p-4',
                                'transition duration-base ease-out-strong hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-overlay focus-visible:outline-focus',
                                'col-span-2 sm:col-span-1' => $loop->last])
                        :class="open === '{{ $key }}' && 'border-primary ring-2 ring-primary/20'">
                    <x-dynamic-component :component="'phosphor-'.$icon" class="h-5 w-5 shrink-0 text-muted sm:h-6 sm:w-6" aria-hidden="true" />
                    <span class="min-w-0 flex-1">
                        @if ($value === null)
                            <span class="block text-page-title font-semibold tabular-nums text-ink">{{ $percent($summary['selfInitiatedRatio']) }}</span>
                        @else
                            <span x-data="countUp({{ (int) $value }})" x-text="shown.toLocaleString('en-US')" class="block text-page-title font-semibold tabular-nums text-ink">{{ number_format($value) }}</span>
                        @endif
                        <span class="block text-sm font-semibold text-ink">{{ $label }}</span>
                        <span class="block text-xs text-muted">{{ $note }}</span>
                        @if ($delta)
                            <span class="mt-1 flex items-start gap-1 text-xs text-muted" title="Compared with {{ $previous->periodLabel() }}">
                                @if ($delta['dir'] > 0)
                                    <x-phosphor-trend-up class="h-3.5 w-3.5 shrink-0 text-ink" aria-hidden="true" />
                                @elseif ($delta['dir'] < 0)
                                    <x-phosphor-trend-down class="h-3.5 w-3.5 shrink-0 text-ink" aria-hidden="true" />
                                @else
                                    <x-phosphor-minus class="h-3.5 w-3.5 shrink-0 text-ink" aria-hidden="true" />
                                @endif
                                <span><span class="font-semibold tabular-nums text-ink">{{ $delta['text'] }}</span> vs {{ $delta['then'] }} {{ $earlier }}</span>
                            </span>
                        @elseif ($key !== 'accredited')
                            <span class="mt-1 block text-xs text-muted">Too few activities {{ $earlier }} to compare</span>
                        @endif
                        <span class="mt-1 flex items-center gap-1 text-xs font-semibold text-primary">
                            <span x-text="open === '{{ $key }}' ? 'Hide details' : 'Show details'">Show details</span>
                            <x-phosphor-caret-down class="h-3 w-3 transition-transform duration-base" ::class="open === '{{ $key }}' && 'rotate-180'" aria-hidden="true" />
                        </span>
                    </span>
                </button>
            @endforeach
        </section>

        @foreach ($tiles as $key => [$label])
            <section id="figure-{{ $key }}" x-show="open === '{{ $key }}'" x-cloak aria-label="{{ $label }}: details"
                     x-transition:enter="transition duration-base ease-out-strong" x-transition:enter-start="-translate-y-2 opacity-0"
                     x-transition:leave="transition duration-fast" x-transition:leave-end="opacity-0"
                     class="card mt-4">
                <div class="flex flex-wrap items-center gap-3 border-b border-line px-4 py-3">
                    <h2 class="card-title">{{ $label }}</h2>
                    <button type="button" class="icon-btn -mr-2 ml-auto" @click="open = null">
                        <x-phosphor-x class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Close the details</span>
                    </button>
                </div>
                <div class="grid gap-6 p-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
                    @switch($key)
                        @case('accredited')
                            <div>
                                <p class="mb-2 text-sm font-semibold text-ink">By sector</p>
                                <x-bar-chart :data="$sectorDistribution" label="Accredited organizations by sector" />
                            </div>
                            <div class="space-y-4 self-start">
                                <dl class="grid grid-cols-2 gap-4 text-sm">
                                    <x-dashboard-fact label="Registered in CivicLink" :value="number_format($summary['organizations'])" />
                                    <x-dashboard-fact label="Without an active accreditation" :value="number_format($highlights['notAccredited'])" />
                                    <x-dashboard-fact label="Expiring within 90 days" :value="$highlights['expiring']->count()" :alert="$highlights['expiring']->isNotEmpty()" />
                                </dl>
                                @if ($highlights['expiring']->isNotEmpty())
                                    <ul class="divide-y divide-line rounded-md border border-line text-sm">
                                        @foreach ($highlights['expiring']->take(5) as $row)
                                            <li class="flex items-center gap-3 px-3 py-2">
                                                <a href="{{ route('admin.organizations.edit', $row['id']) }}" class="entity-link min-w-0 flex-1 truncate">{{ $row['name'] }}</a>
                                                <span class="whitespace-nowrap text-xs tabular-nums text-muted">until {{ $row['accreditedUntil']->format('d M Y') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <p class="text-xs text-muted">Counts today's accreditations, whatever period is chosen above.</p>
                                <a href="{{ route('admin.organizations.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">
                                    See the organizations <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                                </a>
                            </div>
                            @break

                        @case('active')
                            <div>
                                <p class="mb-2 text-sm font-semibold text-ink">
                                    Accredited, but nothing verified in this period
                                    <span class="font-normal tabular-nums text-muted">({{ $highlights['quiet']->count() }})</span>
                                </p>
                                @if ($highlights['quiet']->isEmpty())
                                    <p class="text-sm text-muted">Every accredited organization has at least one verified activity in this period.</p>
                                @else
                                    <ul class="divide-y divide-line rounded-md border border-line text-sm">
                                        @foreach ($highlights['quiet']->take(8) as $row)
                                            <li class="flex items-center gap-3 px-3 py-2">
                                                <a href="{{ route('admin.organizations.edit', $row['id']) }}" class="entity-link min-w-0 flex-1 truncate">{{ $row['name'] }}</a>
                                                <span class="whitespace-nowrap text-xs text-muted">last: {{ $row['lastActivity']?->format('M Y') ?? 'never' }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @if ($highlights['quiet']->count() > 8)
                                        <p class="mt-2 text-xs text-muted">and {{ $highlights['quiet']->count() - 8 }} more; see At-risk organizations below.</p>
                                    @endif
                                @endif
                            </div>
                            <div class="space-y-4 self-start">
                                <dl class="grid grid-cols-2 gap-4 text-sm">
                                    <x-dashboard-fact label="This period" :value="$summary['activeOrganizations']" />
                                    <x-dashboard-fact :label="Str::ucfirst($earlier)" :value="$before['activeOrganizations']" />
                                    <x-dashboard-fact label="Share of accredited" :value="$summary['accredited'] ? round(min($summary['activeOrganizations'], $summary['accredited']) / $summary['accredited'] * 100).'%' : 'n/a'" />
                                </dl>
                                <p class="text-xs text-muted">An organization counts once it led, or was tagged as a partner on, at least one verified activity dated inside the period.</p>
                            </div>
                            @break

                        @case('activities')
                            @php $known = max($summary['verifiedActivities'], 1); @endphp
                            <div>
                                <p class="mb-2 text-sm font-semibold text-ink">Who organized them</p>
                                <div class="flex h-4 w-full gap-hair overflow-hidden rounded-sm bg-canvas" role="img"
                                     aria-label="{{ $summary['independent'] }} self-initiated, {{ $summary['lguOrganized'] }} LGU-organized, {{ $summary['unknownSource'] }} not recorded">
                                    <span class="bg-chart-self" style="width: {{ round($summary['independent'] / $known * 100, 1) }}%"></span>
                                    <span class="bg-chart-lgu" style="width: {{ round($summary['lguOrganized'] / $known * 100, 1) }}%"></span>
                                    <span class="bg-chart-unknown" style="width: {{ round($summary['unknownSource'] / $known * 100, 1) }}%"></span>
                                </div>
                                <dl class="mt-3 grid grid-cols-3 gap-4 text-sm">
                                    @foreach ([['Self-initiated', $summary['independent'], 'bg-chart-self'], ['LGU-organized', $summary['lguOrganized'], 'bg-chart-lgu'], ['Not recorded', $summary['unknownSource'], 'bg-chart-unknown']] as [$name, $count, $swatch])
                                        <div>
                                            <dt class="flex items-center gap-tight text-xs text-muted"><span class="h-2.5 w-2.5 {{ $swatch }}" aria-hidden="true"></span>{{ $name }}</dt>
                                            <dd class="font-semibold tabular-nums text-ink">{{ $count }}</dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>
                            <div class="space-y-4 self-start">
                                <dl class="grid grid-cols-2 gap-4 text-sm">
                                    <x-dashboard-fact label="Monthly average" :value="round($summary['verifiedActivities'] / max($activityTrend->count(), 1), 1)" />
                                    <x-dashboard-fact label="Busiest month" :value="$highlights['busiestMonth'] ? $highlights['busiestMonth']['label'].' ('.$highlights['busiestMonth']['total'].')' : '—'" />
                                    <x-dashboard-fact :label="Str::ucfirst($earlier)" :value="number_format($before['verifiedActivities'])" />
                                    <x-dashboard-fact label="Waiting to be verified now" :value="$highlights['pendingActivities']" :alert="$highlights['pendingActivities'] > 0" />
                                </dl>
                                <p class="text-xs text-muted">Only activities the office verified count. Waiting and rejected ones are left out of every figure on this page.</p>
                                <a href="{{ route('admin.activities.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline">
                                    Open Verify activities <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                                </a>
                            </div>
                            @break

                        @case('reach')
                            <div>
                                <p class="mb-2 text-sm font-semibold text-ink">Reached the most residents</p>
                                @if ($highlights['topReach']->isEmpty())
                                    <p class="text-sm text-muted">No verified activities in this period.</p>
                                @else
                                    <ul class="divide-y divide-line rounded-md border border-line text-sm">
                                        @foreach ($highlights['topReach'] as $row)
                                            <li class="flex items-center gap-3 px-3 py-2">
                                                <a href="{{ route('admin.organizations.edit', $row['id']) }}" class="entity-link min-w-0 flex-1 truncate">{{ $row['name'] }}</a>
                                                <span class="whitespace-nowrap tabular-nums text-ink">{{ number_format($row['reach']) }}</span>
                                                <span class="whitespace-nowrap text-xs text-muted">in {{ $row['verified'] }} {{ Str::plural('activity', $row['verified']) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($activity = $highlights['largestActivity'])
                                    <p class="mt-4 text-sm text-ink">
                                        <span class="font-semibold">Largest activity:</span> {{ $activity->title }}
                                        <span class="text-muted">({{ $activity->organization?->name }}, {{ $activity->activity_date->format('d M Y') }})</span>,
                                        <span class="tabular-nums">{{ number_format($activity->participants_estimate) }}</span> residents.
                                    </p>
                                @endif
                            </div>
                            <div class="space-y-4 self-start">
                                <dl class="grid grid-cols-2 gap-4 text-sm">
                                    <x-dashboard-fact label="Average per activity" :value="$summary['verifiedActivities'] ? number_format($summary['residentsReached'] / $summary['verifiedActivities']) : '—'" />
                                    <x-dashboard-fact :label="Str::ucfirst($earlier)" :value="number_format($before['residentsReached'])" />
                                </dl>
                                <p class="text-xs text-muted">
                                    The sum of the participant counts the CSOs reported on verified activities. A resident who joins three
                                    activities is counted three times, so read it as attendance, not as distinct people.
                                </p>
                            </div>
                            @break

                        @case('share')
                            @php $rated = $sectorComparison->whereNotNull('ratio')->sortByDesc('ratio'); @endphp
                            <div>
                                <p class="mb-2 text-sm font-semibold text-ink">Of {{ $summary['independent'] + $summary['lguOrganized'] }} activities with a known organizer</p>
                                <dl class="grid grid-cols-3 gap-4 text-sm">
                                    <x-dashboard-fact label="Self-initiated" :value="$summary['independent']" />
                                    <x-dashboard-fact label="LGU-organized" :value="$summary['lguOrganized']" />
                                    <x-dashboard-fact label="Not recorded" :value="$summary['unknownSource']" />
                                </dl>
                                @if ($rated->count() > 1)
                                    <p class="mt-4 text-sm text-ink">
                                        Highest: <span class="font-semibold">{{ $rated->first()['sector'] }}</span> ({{ $percent($rated->first()['ratio']) }}).
                                        Lowest: <span class="font-semibold">{{ $rated->last()['sector'] }}</span> ({{ $percent($rated->last()['ratio']) }}).
                                    </p>
                                @endif
                            </div>
                            <div class="space-y-4 self-start">
                                <dl class="grid grid-cols-2 gap-4 text-sm">
                                    <x-dashboard-fact label="This period" :value="$percent($summary['selfInitiatedRatio'])" />
                                    <x-dashboard-fact :label="Str::ucfirst($earlier)" :value="$percent($before['selfInitiatedRatio'])" />
                                </dl>
                                <p class="text-xs text-muted">
                                    The share of activities the organization planned and ran itself, out of those whose organizer was
                                    recorded. Shown once there are {{ ReportData::MIN_FOR_RATIO }} or more. A low share means CSOs mostly
                                    attend LGU events.
                                </p>
                            </div>
                            @break
                    @endswitch
                </div>
            </section>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
        {{-- Monthly volume: point at a month for its figures, click it for its activities. --}}
        <section class="panel-flat p-6" x-data="chart({ metric: 'activities' })" @keydown.escape="picked = null">
            <div class="flex flex-wrap items-start gap-3">
                <div class="w-full min-w-0 sm:w-auto sm:flex-1">
                    <h2 class="font-semibold text-ink">Activity volume</h2>
                    <p class="mt-1 text-sm text-muted">Per month. Point at a month for its figures; click it to list its activities.</p>
                </div>
                <div role="group" aria-label="Measure" class="inline-flex rounded-md border border-line p-hair">
                    @foreach (['activities' => 'Activities', 'reach' => 'Residents reached'] as $metric => $name)
                        <button type="button" @click="metric = '{{ $metric }}'" :aria-pressed="(metric === '{{ $metric }}').toString()"
                                class="rounded-sm px-3 py-1 text-xs font-semibold transition duration-fast"
                                :class="metric === '{{ $metric }}' ? 'bg-primary text-on-primary' : 'text-ink hover:bg-primary-subtle'">{{ $name }}</button>
                    @endforeach
                </div>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1">
                <x-source-legend with-unknown x-show="metric === 'activities'" />
                <p class="text-xs text-muted" x-show="metric === 'reach'" x-cloak>Residents reached: participant counts on verified activities.</p>
                <p class="ml-auto text-xs text-muted">
                    Highest:
                    <span x-show="metric === 'activities'" class="tabular-nums">{{ $peak && $peak['total'] ? $peak['label'].', '.$peak['total'] : 'none' }}</span>
                    <span x-show="metric === 'reach'" x-cloak class="tabular-nums">{{ $peakReach && $peakReach['reach'] ? $peakReach['label'].', '.number_format($peakReach['reach']) : 'none' }}</span>
                </p>
            </div>

            <div class="relative mt-3" x-ref="plot">
                <div class="flex h-36 items-end gap-hair">
                    @foreach ($activityTrend as $row)
                        @php
                            $rows = [
                                ['value' => number_format($row['total']), 'label' => Str::plural('verified activity', $row['total']), 'key' => ''],
                                ['value' => $row['independent'], 'label' => 'self-initiated', 'key' => 'bg-chart-self'],
                                ['value' => $row['lgu'], 'label' => 'LGU-organized', 'key' => 'bg-chart-lgu'],
                                ...($row['unknown'] ? [['value' => $row['unknown'], 'label' => 'not recorded', 'key' => 'bg-chart-unknown']] : []),
                                ['value' => number_format($row['reach']), 'label' => 'residents reached', 'key' => ''],
                            ];
                        @endphp
                        <button type="button" data-tip="{{ json_encode(['title' => $row['label'], 'rows' => $rows]) }}"
                                @mouseenter="show($event)" @focus="show($event)" @mouseleave="hide()" @blur="hide()" @click="pick('{{ $row['key'] }}')"
                                :aria-pressed="(picked === '{{ $row['key'] }}').toString()"
                                aria-label="{{ $row['label'] }}: {{ $row['total'] }} verified ({{ $row['independent'] }} self-initiated, {{ $row['lgu'] }} LGU-organized), {{ number_format($row['reach']) }} residents reached. List its activities."
                                class="flex h-full min-w-0 flex-1 flex-col justify-end rounded-sm transition-opacity duration-fast hover:bg-primary-subtle focus-visible:outline-focus"
                                :class="picked && picked !== '{{ $row['key'] }}' && 'opacity-40'">
                            <span data-mark x-show="metric === 'activities'" class="flex w-full flex-col justify-end gap-hair overflow-hidden rounded-t-sm"
                                  style="height: {{ round($row['total'] / $maxTotal * 100, 1) }}%">
                                @if ($row['unknown'])<span class="w-full bg-chart-unknown" style="flex: {{ $row['unknown'] }} 1 0%"></span>@endif
                                @if ($row['lgu'])<span class="w-full bg-chart-lgu" style="flex: {{ $row['lgu'] }} 1 0%"></span>@endif
                                @if ($row['independent'])<span class="w-full bg-chart-self" style="flex: {{ $row['independent'] }} 1 0%"></span>@endif
                            </span>
                            <span data-mark x-show="metric === 'reach'" x-cloak class="w-full rounded-t-sm bg-chart-bar"
                                  style="height: {{ round($row['reach'] / $maxReach * 100, 1) }}%"></span>
                        </button>
                    @endforeach
                </div>
                <x-chart-tip />
            </div>
            <div class="mt-2 flex gap-hair border-t border-line pt-2" aria-hidden="true">
                @foreach ($activityTrend as $i => $row)
                    <span class="min-w-0 flex-1 overflow-hidden text-center text-xs tabular-nums text-muted">
                        {{ $i % $labelEvery ? '' : Str::before($row['label'], ' ') }}
                    </span>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-muted">{{ $activityTrend->first()['label'] ?? '' }} to {{ $activityTrend->last()['label'] ?? '' }}</p>

            @foreach ($activityTrend as $row)
                @php $month = $monthActivities[$row['key']] ?? ['count' => 0, 'items' => collect()]; @endphp
                <div x-show="picked === '{{ $row['key'] }}'" x-cloak x-transition.opacity class="mt-4 rounded-md border border-line">
                    <div class="flex items-start gap-3 border-b border-line px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold text-ink">{{ $row['label'] }}</h3>
                            <span class="block text-sm tabular-nums text-muted">
                            {{ $row['total'] }} verified, {{ number_format($row['reach']) }} residents reached
                        </span>
                        </div>
                        <button type="button" class="icon-btn -mr-2 shrink-0" @click="picked = null">
                            <x-phosphor-x class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Close {{ $row['label'] }}</span>
                        </button>
                    </div>
                    @if ($month['items']->isEmpty())
                        <p class="px-4 py-6 text-center text-sm text-muted">No verified activity this month.</p>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($month['items'] as $activity)
                                <li class="flex items-baseline gap-3 px-4 py-2 text-sm">
                                    <span class="w-12 shrink-0 text-xs tabular-nums text-muted">{{ $activity->activity_date->format('d M') }}</span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-ink">{{ $activity->title }}</p>
                                        @if ($activity->organization)
                                            <a href="{{ route('admin.organizations.edit', $activity->organization_id) }}" class="entity-link text-xs">{{ $activity->organization->name }}</a>
                                        @endif
                                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted">
                                            <span class="inline-flex items-center gap-tight">
                                                <span class="h-2.5 w-2.5 {{ $sourceKey[$activity->activity_source] ?? 'bg-chart-unknown' }}" aria-hidden="true"></span>
                                                {{ $sourceName[$activity->activity_source] ?? 'Not recorded' }}
                                            </span>
                                            <span class="tabular-nums"><span class="font-semibold text-ink">{{ number_format($activity->participants_estimate) }}</span> residents reached</span>
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        @if ($month['count'] > $month['items']->count())
                            <p class="border-t border-line px-4 py-2 text-xs text-muted">The {{ $month['items']->count() }} largest of {{ $month['count'] }}.</p>
                        @endif
                    @endif
                </div>
            @endforeach
        </section>

        {{-- Sectors: point at a bar for its numbers, click it for the organizations behind them. --}}
        <section class="panel-flat p-6" x-data="chart({ shape: 'bar' })" @keydown.escape="picked = null">
            <h2 class="font-semibold text-ink">Self-initiated or LGU-organized, by sector</h2>
            <p class="mt-1 text-sm text-muted">
                Does the sector run its own community work, or mostly attend LGU events? Click a sector for its organizations.
                The share shows once a sector has {{ ReportData::MIN_FOR_RATIO }} or more activities with a known organizer.
            </p>
            <x-source-legend class="mt-3" />
            @php $sectorMax = max($sectorComparison->max(fn ($r) => $r['independent'] + $r['lgu']) ?: 0, 1); @endphp
            <div class="relative mt-3" x-ref="plot">
                <ul>
                    @foreach ($sectorComparison as $row)
                        @php
                            $rows = [
                                ['value' => $row['independent'], 'label' => 'self-initiated', 'key' => 'bg-chart-self'],
                                ['value' => $row['lgu'], 'label' => 'LGU-organized', 'key' => 'bg-chart-lgu'],
                                ['value' => $row['accredited'], 'label' => Str::plural('accredited organization', $row['accredited']), 'key' => ''],
                                ['value' => $percent($row['ratio']), 'label' => 'self-initiated share', 'key' => ''],
                            ];
                            $sum = $row['independent'] + $row['lgu'];
                        @endphp
                        <li>
                            <button type="button" data-tip="{{ json_encode(['title' => $row['sector'], 'rows' => $rows]) }}"
                                    @mouseenter="show($event)" @focus="show($event)" @mouseleave="hide()" @blur="hide()" @click="pick(@js($row['sector']))"
                                    :aria-expanded="(picked === @js($row['sector'])).toString()"
                                    aria-label="{{ $row['sector'] }}: {{ $row['independent'] }} self-initiated, {{ $row['lgu'] }} LGU-organized, {{ $percent($row['ratio']) }} self-initiated. Show its organizations."
                                    class="grid w-full grid-cols-[7rem_minmax(0,1fr)_2.5rem] items-center gap-3 rounded-sm px-2 py-1 text-left transition duration-fast hover:bg-primary-subtle focus-visible:outline-focus sm:grid-cols-[10rem_minmax(0,1fr)_3rem]"
                                    :class="picked === @js($row['sector']) && 'bg-primary-subtle'">
                                <span class="truncate text-sm text-ink">{{ $row['sector'] }}</span>
                                <span class="block h-4 bg-canvas">
                                    <span data-mark class="flex h-4 gap-hair overflow-hidden rounded-r-sm" style="width: {{ round($sum / $sectorMax * 100, 1) }}%">
                                        @if ($row['independent'])<span class="h-4 bg-chart-self" style="flex: {{ $row['independent'] }} 1 0%"></span>@endif
                                        @if ($row['lgu'])<span class="h-4 bg-chart-lgu" style="flex: {{ $row['lgu'] }} 1 0%"></span>@endif
                                    </span>
                                </span>
                                <span class="text-right text-sm tabular-nums text-muted">{{ $percent($row['ratio']) }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <x-chart-tip />
            </div>

            @foreach ($sectorComparison as $row)
                @php $members = ($bySector[$row['sector']] ?? collect())->sortByDesc(fn ($r) => [$r['verified'], $r['reach']]); @endphp
                <div x-show="picked === @js($row['sector'])" x-cloak x-transition.opacity class="mt-4 rounded-md border border-line">
                    <div class="flex items-start gap-3 border-b border-line px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold text-ink">{{ $row['sector'] }}</h3>
                            <span class="block text-sm tabular-nums text-muted">{{ $row['accredited'] }} accredited, {{ $row['verified'] }} verified activities</span>
                        </div>
                        <button type="button" class="icon-btn -mr-2 shrink-0" @click="picked = null">
                            <x-phosphor-x class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Close {{ $row['sector'] }}</span>
                        </button>
                    </div>
                    <x-org-figures :rows="$members" empty="No accredited organization in this sector yet." />
                </div>
            @endforeach
        </section>
    </div>

    <div class="mt-6 grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
        {{-- Barangays: the busiest ten first, the rest on request; click one for its organizations. --}}
        <section class="panel-flat p-6" x-data="chart({ shape: 'bar' })" @keydown.escape="picked = null">
            <h2 class="font-semibold text-ink">By barangay</h2>
            <p class="mt-1 text-sm text-muted">
                Verified activities led by organizations based in each barangay. Click a barangay for its organizations.
                {{ $barangays->where('activities', 0)->count() }} of {{ $barangays->count() }} barangays had none in this period.
            </p>
            @php $barangayMax = max($barangays->max('activities') ?: 0, 1); @endphp
            <div class="relative mt-3" x-ref="plot">
                <ul>
                    @foreach ($barangays as $row)
                        @php
                            $based = ($byBarangay[$row['barangay']] ?? collect())->count();
                            $rows = [
                                ['value' => $row['activities'], 'label' => Str::plural('verified activity', $row['activities']), 'key' => 'bg-chart-bar'],
                                ['value' => $row['accredited'], 'label' => 'accredited '.Str::plural('organization', $row['accredited']), 'key' => ''],
                                ['value' => $based, 'label' => 'registered in CivicLink', 'key' => ''],
                            ];
                        @endphp
                        <li @if ($loop->index >= 10) x-show="more" x-cloak @endif>
                            <button type="button" data-tip="{{ json_encode(['title' => 'Brgy. '.$row['barangay'], 'rows' => $rows]) }}"
                                    @mouseenter="show($event)" @focus="show($event)" @mouseleave="hide()" @blur="hide()" @click="pick(@js($row['barangay']))"
                                    :aria-expanded="(picked === @js($row['barangay'])).toString()"
                                    aria-label="{{ $row['barangay'] }}: {{ $row['activities'] }} verified activities, {{ $row['accredited'] }} accredited organizations. Show its organizations."
                                    class="grid w-full grid-cols-[7rem_minmax(0,1fr)_2.5rem] items-center gap-3 rounded-sm px-2 py-1 text-left transition duration-fast hover:bg-primary-subtle focus-visible:outline-focus sm:grid-cols-[10rem_minmax(0,1fr)_3rem]"
                                    :class="picked === @js($row['barangay']) && 'bg-primary-subtle'">
                                <span class="truncate text-sm text-ink">{{ $row['barangay'] }}</span>
                                <span class="block h-4 bg-canvas">
                                    <span data-mark class="block h-4 rounded-r-sm bg-chart-bar" style="width: {{ round($row['activities'] / $barangayMax * 100, 1) }}%"></span>
                                </span>
                                <span class="text-right text-sm tabular-nums text-muted">{{ $row['activities'] }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <x-chart-tip />
            </div>
            @if ($barangays->count() > 10)
                <button type="button" @click="more = ! more" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline" :aria-expanded="more.toString()">
                    <span x-text="more ? 'Show the top 10 only' : 'Show all {{ $barangays->count() }} barangays'">Show all {{ $barangays->count() }} barangays</span>
                    <x-phosphor-caret-down class="h-3 w-3 transition-transform duration-base" ::class="more && 'rotate-180'" aria-hidden="true" />
                </button>
            @endif

            @foreach ($barangays as $row)
                @php $based = ($byBarangay[$row['barangay']] ?? collect())->sortByDesc(fn ($r) => [$r['verified'], $r['reach']]); @endphp
                <div x-show="picked === @js($row['barangay'])" x-cloak x-transition.opacity class="mt-4 rounded-md border border-line">
                    <div class="flex items-start gap-3 border-b border-line px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold text-ink">Brgy. {{ $row['barangay'] }}</h3>
                            <span class="block text-sm tabular-nums text-muted">{{ $based->count() }} registered, {{ $row['accredited'] }} accredited</span>
                        </div>
                        <button type="button" class="icon-btn -mr-2 shrink-0" @click="picked = null">
                            <x-phosphor-x class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Close {{ $row['barangay'] }}</span>
                        </button>
                    </div>
                    <x-org-figures :rows="$based" empty="No organization based in this barangay is registered yet." status />
                </div>
            @endforeach
        </section>

        {{-- Applications filed against approved, per month. --}}
        <section class="panel-flat p-6" x-data="chart()">
            <h2 class="font-semibold text-ink">Applications filed and approved</h2>
            <p class="mt-1 text-sm text-muted">
                Per month: {{ $complianceTrend->sum('filed') }} filed and {{ $complianceTrend->sum('approved') }} approved in this period.
                An application approved this month may have been filed in an earlier one.
            </p>
            <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted">
                <span class="inline-flex items-center gap-tight"><span class="h-2.5 w-2.5 bg-chart-lgu" aria-hidden="true"></span>Filed</span>
                <span class="inline-flex items-center gap-tight"><span class="h-2.5 w-2.5 bg-chart-bar" aria-hidden="true"></span>Approved</span>
            </p>
            @php $applicationsMax = max($complianceTrend->max('filed') ?: 0, $complianceTrend->max('approved') ?: 0, 1); @endphp
            <div class="relative mt-3" x-ref="plot">
                <div class="flex h-36 items-end gap-hair">
                    @foreach ($complianceTrend as $row)
                        @php
                            $rows = [
                                ['value' => $row['filed'], 'label' => 'filed', 'key' => 'bg-chart-lgu'],
                                ['value' => $row['approved'], 'label' => 'approved', 'key' => 'bg-chart-bar'],
                            ];
                        @endphp
                        <div tabindex="0" role="img" data-tip="{{ json_encode(['title' => $row['label'], 'rows' => $rows]) }}"
                             aria-label="{{ $row['label'] }}: {{ $row['filed'] }} filed, {{ $row['approved'] }} approved"
                             @mouseenter="show($event)" @focus="show($event)" @mouseleave="hide()" @blur="hide()"
                             class="flex h-full min-w-0 flex-1 items-end justify-center gap-hair rounded-sm hover:bg-primary-subtle focus-visible:outline-focus">
                            <span data-mark class="w-1/2 rounded-t-sm bg-chart-lgu" style="height: {{ round($row['filed'] / $applicationsMax * 100, 1) }}%"></span>
                            <span class="w-1/2 rounded-t-sm bg-chart-bar" style="height: {{ round($row['approved'] / $applicationsMax * 100, 1) }}%"></span>
                        </div>
                    @endforeach
                </div>
                <x-chart-tip />
            </div>
            <div class="mt-2 flex gap-hair border-t border-line pt-2" aria-hidden="true">
                @foreach ($complianceTrend as $i => $row)
                    <span class="min-w-0 flex-1 overflow-hidden text-center text-xs tabular-nums text-muted">
                        {{ $i % $labelEvery ? '' : Str::before($row['label'], ' ') }}
                    </span>
                @endforeach
            </div>
        </section>
    </div>

    {{-- The inverse of Top contributors: who PESO should follow up with. --}}
    <section id="at-risk" class="panel-flat mt-6">
        <div class="flex flex-wrap items-end gap-3 border-b border-line p-6 pb-3">
            <div>
                <h2 class="font-semibold text-ink">At-risk organizations</h2>
                <p class="mt-1 text-sm text-muted">
                    Accredited for at least {{ \App\Models\Organization::INACTIVE_AFTER_MONTHS }} months, with little or no
                    verified activity in this period, or only LGU-organized activity. Least active first.
                </p>
            </div>
            <span class="badge-warning ml-auto">{{ $atRiskTotal }}</span>
        </div>
        @if ($atRisk->isEmpty())
            <x-empty-state title="No accredited organization is at risk in this period." icon="seal-check" />
        @else
            <div class="table-wrap">
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
                                    <a href="{{ route('admin.organizations.edit', $row['id']) }}" class="entity-link">{{ $row['name'] }}</a>
                                    <p class="text-xs text-muted">{{ $row['sector'] }} &middot; Brgy. {{ $row['barangay'] }}</p>
                                </td>
                                <td class="table-cell text-sm">
                                    @foreach ($row['reasons'] as $reason)
                                        <span class="badge-warning mb-1 mr-1">{{ $reason }}</span>
                                    @endforeach
                                </td>
                                <td class="table-num">{{ $row['verified'] }}</td>
                                <td class="table-num">{{ $row['independent'] }}</td>
                                <td class="table-date">
                                    {{ $row['lastActivity']?->format('d M Y') ?? 'Never' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if ($atRiskTotal > 0)
            <p class="flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-line px-4 py-3 text-sm text-muted">
                <span class="tabular-nums">Showing {{ $atRisk->count() }} of {{ $atRiskTotal }} at-risk</span>
                @if ($atRiskTotal > $atRisk->count())
                    <a href="{{ route('admin.organizations.index', ['status' => 'at-risk'] + $query) }}" class="font-semibold text-primary hover:underline">See all in Organizations</a>
                @endif
                <a href="{{ route('admin.analytics.excel', $query) }}" class="font-semibold text-primary hover:underline">Full list in the Excel workbook</a>
            </p>
        @endif
    </section>

    <section class="panel-flat mt-6" x-data="sortTable">
        <div class="border-b border-line p-6 pb-3">
            <h2 class="font-semibold text-ink">Sector comparison</h2>
            <p class="mt-1 text-sm text-muted">Accredited organizations only. Click a column to sort. Scores are informational and never affect renewal.</p>
        </div>
        <div class="table-wrap">
            <table class="w-full">
                <caption class="sr-only">Sector comparison</caption>
                <thead>
                    <tr>
                        @foreach (['Sector', 'Accredited', 'Verified activities', 'Per organization', 'Self-initiated share', 'Average score'] as $i => $heading)
                            <th scope="col" @class(['table-head', 'text-right' => $i > 0]) :aria-sort="order({{ $i }})">
                                <button type="button" @click="sort({{ $i }}, {{ $i === 0 ? 'true' : 'false' }})" class="inline-flex items-center gap-1 hover:text-ink">
                                    {{ $heading }} <x-phosphor-caret-up-down class="h-3 w-3" aria-hidden="true" />
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody x-ref="body">
                    @foreach ($sectorComparison as $row)
                        <tr class="table-row">
                            <td class="table-cell font-semibold">{{ $row['sector'] }}</td>
                            <td class="table-num">{{ $row['accredited'] }}</td>
                            <td class="table-num">{{ $row['verified'] }}</td>
                            <td class="table-num">{{ $row['perOrganization'] }}</td>
                            <td class="table-num" data-sort="{{ $row['ratio'] ?? -1 }}">{{ $percent($row['ratio']) }}</td>
                            <td class="table-num" data-sort="{{ $row['averageScore'] ?? -1 }}">{{ $row['averageScore'] === null ? 'n/a' : number_format($row['averageScore'] * 100) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel-flat mt-6" x-data="sortTable">
        <div class="flex flex-wrap items-end gap-3 border-b border-line p-6 pb-3">
            <div>
                <h2 class="font-semibold text-ink">Top contributors</h2>
                <p class="mt-1 text-sm text-muted">The {{ $topContributors->count() }} with the most verified activities, including partner credit. Click a column to sort.</p>
            </div>
            <a href="{{ route('admin.analytics.awards', $query) }}" class="btn-ghost ml-auto px-3">Awards ranking (Excel)</a>
        </div>
        @if ($topContributors->isEmpty())
            <p class="px-4 py-12 text-center text-sm text-muted">No verified activities in this period.</p>
        @else
            <div class="table-wrap">
                <table class="w-full">
                    <caption class="sr-only">Organizations ranked by verified activities</caption>
                    <thead>
                        <tr>
                            @foreach (['Organization', 'Activities', 'Residents reached', 'Self-initiated share', 'Score'] as $i => $heading)
                                <th scope="col" @class(['table-head', 'text-right' => $i > 0]) :aria-sort="order({{ $i }})">
                                    <button type="button" @click="sort({{ $i }}, {{ $i === 0 ? 'true' : 'false' }})" class="inline-flex items-center gap-1 hover:text-ink">
                                        {{ $heading }} <x-phosphor-caret-up-down class="h-3 w-3" aria-hidden="true" />
                                    </button>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody x-ref="body">
                        @foreach ($topContributors as $row)
                            <tr class="table-row">
                                <td class="table-cell" data-sort="{{ $row['name'] }}">
                                    <a href="{{ route('admin.organizations.edit', $row['id']) }}" class="entity-link font-semibold">{{ $row['name'] }}</a>
                                    <p class="text-xs text-muted">{{ $row['sector'] }} &middot; Brgy. {{ $row['barangay'] }}</p>
                                </td>
                                <td class="table-num">{{ $row['verified'] }}</td>
                                <td class="table-num" data-sort="{{ $row['reach'] }}">{{ number_format($row['reach']) }}</td>
                                <td class="table-num" data-sort="{{ $row['ratio'] ?? -1 }}">{{ $percent($row['ratio']) }}</td>
                                <td class="table-num" data-sort="{{ $row['score'] ?? -1 }}">{{ $row['score'] === null ? 'n/a' : number_format($row['score'] * 100) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.app>
