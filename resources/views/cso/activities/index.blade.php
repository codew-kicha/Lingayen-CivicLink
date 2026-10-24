{{--
    The representative's activity record: one line per activity, with its details (description,
    evidence, Edit and Withdraw while pending) opening underneath. Logging happens in a dialog.
--}}
<x-layouts.app header="Activities"
               subheader="Log the community work your organization does. The Desk Office verifies each entry before it counts toward your score.">
    @if (! $organization)
        <section class="card">
            <x-empty-state title="Set up your organization profile first." icon="user-circle">
                <x-slot:action><a href="{{ route('cso.profile.edit') }}" class="btn-primary">Set up profile</a></x-slot:action>
            </x-empty-state>
        </section>
    @else
        <x-slot:actions>
            <button type="button" class="btn-primary" @click="$dispatch('open-dialog', 'log-activity')">
                <x-phosphor-plus class="h-4 w-4" aria-hidden="true" />Log an activity
            </button>
        </x-slot:actions>

        <section class="card" aria-labelledby="history">
            <div class="flex flex-wrap items-center gap-3 border-b border-line px-6 py-4">
                {{-- Pagination focuses this heading after a page change (#history). --}}
                <h2 id="history" tabindex="-1" class="card-title mr-auto">Activity history</h2>
                <form method="GET" class="flex items-center gap-2">
                    <label for="status" class="sr-only">Show</label>
                    <select id="status" name="status" class="field-input w-auto text-sm" x-data @change="$el.form.requestSubmit()">
                        <option value="">All activities</option>
                        <option value="pending" @selected(request('status') === 'pending')>Awaiting verification</option>
                        <option value="verified" @selected(request('status') === 'verified')>Verified</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                    </select>
                    <noscript><button type="submit" class="btn-secondary">Filter</button></noscript>
                </form>
            </div>

            @if ($activities->isEmpty())
                <x-empty-state :filtered="request()->filled('status')" :clear-url="route('cso.activities.index')"
                               title="No activities logged yet." icon="handshake">
                    Logged activities appear here, and on your public listing once verified.
                </x-empty-state>
            @else
                <div class="table-wrap">
                    <table class="table-stack w-full">
                        <caption class="sr-only">Activities your organization logged. Open a row for its details.</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="table-head pl-6">Activity</th>
                                <th scope="col" class="table-head w-32">Held</th>
                                <th scope="col" class="table-head text-right">Reached</th>
                                <th scope="col" class="table-head">Status</th>
                                <th scope="col" class="table-head w-12 pr-6"><span class="sr-only">Details</span></th>
                            </tr>
                        </thead>
                        @foreach ($activities as $activity)
                            {{-- One tbody per activity, so its detail row opens right under it. --}}
                            <tbody x-data="{ open: false }">
                                <tr class="table-row cursor-pointer" @click="if (! $event.target.closest('a, button')) open = ! open">
                                    <td class="table-cell pl-6">
                                        <span class="font-semibold text-ink">{{ $activity->title }}</span>
                                        @if ($activity->partnerOrganizations->isNotEmpty())
                                            <span class="block text-xs text-muted">With {{ $activity->partnerOrganizations->pluck('name')->join(', ', ' and ') }}</span>
                                        @endif
                                    </td>
                                    <td class="table-date" data-label="Held">{{ $activity->activity_date->format('d M Y') }}</td>
                                    <td class="table-cell text-right tabular-nums text-muted sm:text-ink" data-label="Reached">
                                        {{ $activity->participants_estimate ? number_format($activity->participants_estimate) : '—' }}
                                    </td>
                                    <td class="table-cell">
                                        <x-status-badge :status="$activity->status" />
                                        @if ($activity->status === 'pending' && ! $activity->evidence_path)
                                            <x-phosphor-paperclip class="ml-1 inline h-4 w-4 text-warning" aria-label="No sign-in sheet yet" />
                                        @endif
                                    </td>
                                    <td class="table-cell pr-6 text-right">
                                        <button type="button" class="icon-btn" @click="open = ! open" :aria-expanded="open.toString()"
                                                aria-controls="activity-{{ $activity->id }}-details">
                                            <x-phosphor-caret-down class="h-4 w-4 transition-transform duration-fast" ::class="open && 'rotate-180'" aria-hidden="true" />
                                            <span class="sr-only">Details for {{ $activity->title }}</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr id="activity-{{ $activity->id }}-details" x-show="open" x-cloak>
                                    <td colspan="5" class="border-b border-line bg-canvas/60 px-6 py-4 text-sm">
                                        <p class="max-w-[72ch] text-ink">{{ $activity->description }}</p>
                                        <p class="mt-2 text-muted">{{ $activity->sourceLabel() }}</p>

                                        @if ($activity->status === 'rejected' && $activity->rejection_reason)
                                            <p class="mt-3 rounded-sm border border-danger bg-danger-subtle px-3 py-2 text-danger">
                                                {{ $activity->rejection_reason }}
                                            </p>
                                        @endif

                                        @if ($activity->evidence_path)
                                            <p class="mt-2 text-muted">
                                                <x-phosphor-paperclip class="-mt-1 mr-1 inline h-4 w-4" aria-hidden="true" />Sign-in sheet:
                                                <a href="{{ route('activities.evidence', $activity) }}" target="_blank" rel="noopener" class="entity-link">{{ $activity->evidence_filename }}</a>
                                            </p>
                                        @endif

                                        @can('update', $activity)
                                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                                @unless ($activity->evidence_path)
                                                    <p class="text-warning">No sign-in sheet yet. Adding one helps the office verify it.</p>
                                                @endunless
                                                <div class="ml-auto flex gap-2">
                                                    <a href="{{ route('cso.activities.edit', $activity) }}" class="btn-secondary btn-sm">
                                                        <x-phosphor-pencil-simple class="h-4 w-4" aria-hidden="true" />{{ $activity->evidence_path ? 'Edit' : 'Edit or add sign-in sheet' }}
                                                    </a>
                                                    <button type="button" class="btn-danger btn-sm"
                                                            @click="$dispatch('withdraw-activity', @js(['title' => $activity->title, 'url' => route('cso.activities.destroy', $activity)]))">
                                                        Withdraw
                                                    </button>
                                                </div>
                                            </div>
                                        @endcan
                                    </td>
                                </tr>
                            </tbody>
                        @endforeach
                    </table>
                </div>

                <x-pagination :paginator="$activities" target="history" />
            @endif
        </section>

        @if ($taggedIn->isNotEmpty())
            <section class="card mt-6" aria-labelledby="tagged-title">
                <div class="border-b border-line px-6 py-4">
                    <h2 id="tagged-title" class="card-title">Tagged as a partner</h2>
                    <p class="mt-1 text-sm text-muted">Logged by other organizations. Verified entries count toward your score too.@if ($taggedInTotal > $taggedIn->count()) Showing the latest {{ $taggedIn->count() }} of {{ $taggedInTotal }}.@endif</p>
                </div>
                <div class="table-wrap">
                    <table class="table-stack w-full">
                        <caption class="sr-only">Activities that list your organization as a partner</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="table-head pl-6">Activity</th>
                                <th scope="col" class="table-head">Logged by</th>
                                <th scope="col" class="table-head w-32">Held</th>
                                <th scope="col" class="table-head pr-6">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($taggedIn as $activity)
                                <tr class="table-row">
                                    <td class="table-cell pl-6 font-semibold text-ink">{{ $activity->title }}</td>
                                    <td class="table-cell text-muted" data-label="Logged by">{{ $activity->organization->name }}</td>
                                    <td class="table-date" data-label="Held">{{ $activity->activity_date->format('d M Y') }}</td>
                                    <td class="table-cell pr-6"><x-status-badge :status="$activity->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @include('cso.activities.log-dialog')
        @include('cso.activities.withdraw-dialog')
    @endif
</x-layouts.app>
