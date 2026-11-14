<x-layouts.app header="Dashboard" subheader="Everything waiting on the PESO office today." :woven="true">
    {{--
        The counts open to their details (pre-final defense panel): each tile is a button that shows a
        breakdown, the oldest item and the records behind the number in a panel under the row, with a
        link to the full page. One panel at a time; Esc or the tile again closes it. Numbers count up
        on load, and a queue with items waiting gets the warning colour and a gentle pulse.
    --}}
    @php
        $tiles = [
            'review' => ['Applications to review', $counts['awaiting_review'], route('admin.applications.index'), 'clipboard-text', true, 'Open the review queue'],
            'activities' => ['Activities to verify', $counts['pending_activities'], route('admin.activities.index'), 'check-square', true, 'Open Verify activities'],
            'expiring' => ['Expiring within 90 days', $counts['expiring_soon'], route('admin.organizations.index', ['status' => 'expiring']), 'calendar-x', true, 'See all expiring'],
            'inactive' => ['Inactive CSOs', $counts['inactive'], route('admin.organizations.index', ['status' => 'inactive']), 'warning-circle', false, 'See all inactive'],
            'accredited' => ['Active accreditations', $counts['active_accreditations'], route('admin.organizations.index'), 'seal-check', false, 'See the organizations'],
        ];
    @endphp
    <div x-data="{ open: null, toggle(key) { this.open = this.open === key ? null : key } }" @keydown.escape.window="open = null">
        {{-- Two per row on phones (the last spans both). --}}
        <section aria-label="Queue summary" class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-5">
            @foreach ($tiles as $key => [$label, $value, $url, $icon, $urgent])
                @php $alert = $urgent && $value > 0; @endphp
                <button type="button" @click="toggle('{{ $key }}')" :aria-expanded="(open === '{{ $key }}').toString()" aria-controls="tile-{{ $key }}"
                        @class([
                            'card group flex items-start gap-3 p-3 text-left sm:gap-4 sm:p-4',
                            'transition duration-base ease-out-strong hover:-translate-y-0.5 hover:shadow-overlay focus-visible:outline-focus',
                            'col-span-2 sm:col-span-1' => $loop->last,
                            'border-warning/40 bg-warning-subtle hover:border-warning' => $alert,
                            'hover:border-primary/40' => ! $alert,
                        ])
                        :class="open === '{{ $key }}' && '{{ $alert ? 'border-warning ring-2 ring-warning/30' : 'border-primary ring-2 ring-primary/20' }}'">
                    <x-dynamic-component :component="'phosphor-'.$icon" @class([
                        'h-5 w-5 shrink-0 sm:h-6 sm:w-6',
                        'text-warning motion-safe:animate-pulse' => $alert,
                        'text-muted' => ! $alert,
                    ]) aria-hidden="true" />
                    <span class="min-w-0 flex-1">
                        <span x-data="countUp({{ (int) $value }})" x-text="shown" @class(['block text-page-title font-semibold tabular-nums', 'text-warning' => $alert, 'text-ink' => ! $alert])>{{ $value }}</span>
                        <span class="block text-sm text-muted">{{ $label }}</span>
                        <span @class(['mt-1 flex items-center gap-1 text-xs font-semibold', 'text-warning' => $alert, 'text-primary' => ! $alert])>
                            <span x-text="open === '{{ $key }}' ? 'Hide details' : 'Show details'">Show details</span>
                            <x-phosphor-caret-down class="h-3 w-3 transition-transform duration-base" ::class="open === '{{ $key }}' && 'rotate-180'" aria-hidden="true" />
                        </span>
                    </span>
                </button>
            @endforeach
        </section>

        {{-- The details, one panel at a time under the row. --}}
        @foreach ($tiles as $key => [$label, $value, $url, $icon, $urgent, $linkLabel])
            <section id="tile-{{ $key }}" x-show="open === '{{ $key }}'" x-cloak aria-label="{{ $label }}: details"
                     x-transition:enter="transition duration-base ease-out-strong" x-transition:enter-start="-translate-y-2 opacity-0"
                     x-transition:leave="transition duration-fast" x-transition:leave-end="opacity-0"
                     class="card mt-4">
                <div class="flex flex-wrap items-center gap-3 border-b border-line px-4 py-3">
                    <h2 class="card-title">{{ $label }}</h2>
                    <span class="text-sm tabular-nums text-muted">{{ $value }}</span>
                    <a href="{{ $url }}" class="ml-auto inline-flex items-center gap-1 whitespace-nowrap text-sm font-semibold text-primary hover:underline">
                        {{ $linkLabel }} <x-phosphor-arrow-right class="h-4 w-4" aria-hidden="true" />
                    </a>
                    <button type="button" class="icon-btn -mr-2" @click="open = null">
                        <x-phosphor-x class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Close the details</span>
                    </button>
                </div>
                <div class="grid gap-6 p-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
                    @switch($key)
                        @case('review')
                            <div>
                                <p class="mb-2 text-sm font-semibold text-ink">Where they stand at the Sangguniang Bayan</p>
                                <x-bar-chart :data="$details['review']['stages']" label="Applications waiting, by SB stage" />
                            </div>
                            <dl class="grid grid-cols-2 gap-4 self-start text-sm">
                                <x-dashboard-fact label="Waiting longest" :value="$details['review']['oldest'] !== null ? $details['review']['oldest'].' days' : '—'" />
                                <x-dashboard-fact label="Ready to approve" :value="$details['review']['stages']['Endorsed'] ?? 0" :alert="($details['review']['stages']['Endorsed'] ?? 0) > 0" />
                                <x-dashboard-fact label="Documents to check" :value="$details['review']['flagged']" :alert="$details['review']['flagged'] > 0" />
                                <x-dashboard-fact label="Renewals" :value="$details['review']['renewals']" />
                                <x-dashboard-fact label="Filed at the office" :value="$details['review']['assisted']" />
                            </dl>
                            @break

                        @case('activities')
                            <div>
                                <p class="mb-2 text-sm font-semibold text-ink">Waiting, by sector</p>
                                <x-bar-chart :data="$details['activities']['sectors']" label="Activities waiting, by sector" />
                            </div>
                            <dl class="grid grid-cols-2 gap-4 self-start text-sm">
                                <x-dashboard-fact label="Waiting longest" :value="$details['activities']['oldest'] !== null ? $details['activities']['oldest'].' days' : '—'" />
                                <x-dashboard-fact label="With a sign-in sheet" :value="$details['activities']['withSheet']" />
                                <x-dashboard-fact label="Without a sign-in sheet" :value="$details['activities']['withoutSheet']" :alert="$details['activities']['withoutSheet'] > 0" />
                            </dl>
                            @break

                        @case('expiring')
                            <ul class="divide-y divide-line lg:col-span-2">
                                @forelse ($details['expiring'] as $row)
                                    <li class="flex flex-wrap items-center gap-x-4 gap-y-1 py-2 text-sm">
                                        <a href="{{ route('admin.organizations.edit', $row['organization']) }}" class="entity-link">{{ $row['organization']->name }}</a>
                                        <span class="tabular-nums text-muted">Ends {{ $row['expires']->format('d M Y') }}</span>
                                        <span @class(['font-semibold tabular-nums', 'text-warning' => $row['days'] <= 30, 'text-ink' => $row['days'] > 30])>{{ $row['days'] }} days left</span>
                                        <span class="ml-auto text-xs text-muted">{{ $row['reminded'] ? 'Reminded '.$row['reminded']->format('d M') : 'Not reminded yet' }}</span>
                                    </li>
                                @empty
                                    <li class="py-6 text-center text-sm text-muted">No accreditation ends in the next 90 days.</li>
                                @endforelse
                            </ul>
                            @break

                        @case('inactive')
                            <ul class="divide-y divide-line lg:col-span-2">
                                @forelse ($details['inactive'] as $organization)
                                    <li class="flex flex-wrap items-center gap-x-4 gap-y-1 py-2 text-sm">
                                        <a href="{{ route('admin.organizations.edit', $organization) }}" class="entity-link">{{ $organization->name }}</a>
                                        <span class="text-muted">Brgy. {{ $organization->barangay }}</span>
                                        <span class="ml-auto tabular-nums text-muted">
                                            {{ $organization->last_verified ? 'Last verified activity '.\Illuminate\Support\Carbon::parse($organization->last_verified)->format('d M Y') : 'No verified activity yet' }}
                                        </span>
                                    </li>
                                @empty
                                    <li class="py-6 text-center text-sm text-muted">Every accredited organization has recent verified activity.</li>
                                @endforelse
                            </ul>
                            @break

                        @case('accredited')
                            <div>
                                <p class="mb-2 text-sm font-semibold text-ink">Issued per month, last 12 months</p>
                                <x-column-chart :series="$details['accredited']['months']" label="Accreditations issued" />
                            </div>
                            <div class="space-y-4">
                                <dl class="grid grid-cols-2 gap-4 text-sm">
                                    <x-dashboard-fact :label="'New in '.now()->year" :value="$details['accredited']['new']" />
                                    <x-dashboard-fact :label="'Renewed in '.now()->year" :value="$details['accredited']['renewed']" />
                                </dl>
                                <div>
                                    <p class="mb-2 text-sm font-semibold text-ink">By sector</p>
                                    <x-bar-chart :data="$details['accredited']['sectors']" label="Active accreditations, by sector" />
                                </div>
                            </div>
                            @break
                    @endswitch
                </div>
            </section>
        @endforeach
    </div>

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
                    <li class="flex items-center gap-3 border-b border-line py-3 last:border-b-0 xl:[&:nth-child(5)]:border-b-0"
                        title="Activity frequency {{ round($score->activity_frequency * 100) }}% · Community reach {{ round($score->community_reach * 100) }}% · Compliance timeliness {{ round($score->compliance_timeliness * 100) }}% · Document currency {{ round($score->document_currency * 100) }}%">
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
