@php
    $pending = $currentStatus === 'pending';
    $filtered = request()->filled('q') || $from || $to;

    $window = \App\Http\Controllers\Admin\ActivityVerificationController::LINK_WINDOW_DAYS;

    // Everything the review dialog shows, in table order (data-index points into this list).
    $items = $activities->getCollection()->values()->map(fn ($activity) => [
        'id' => $activity->id,
        'title' => $activity->title,
        'organization' => $activity->organization->name,
        'barangay' => $activity->organization->barangay,
        'date' => $activity->activity_date->format('d M Y'),
        'description' => $activity->description,
        'residents' => $activity->participants_estimate !== null ? number_format($activity->participants_estimate) : 'Not stated',
        'loggedBy' => $activity->loggedBy?->name ?? 'Not recorded',
        'source' => $activity->sourceLabel(),
        'partners' => $activity->partnerOrganizations->pluck('name')->join(', '),
        'evidence' => $activity->evidence_path ? [
            'url' => route('activities.evidence', $activity),
            'image' => $activity->evidenceIsImage(),
            'filename' => $activity->evidence_filename,
        ] : null,
        'status' => $activity->status,
        'decided' => $activity->verified_at?->format('d M Y'),
        'decidedBy' => $activity->verifiedBy?->name,
        'reason' => $activity->rejection_reason,
        'venue' => $activity->venue,
        'findings' => $activity->checkFindings(),
        'confirmations' => $activity->checkConfirmations(),
        'check' => $activity->check_status,
        'checkLabel' => $activity->checkLabel(),
        'score' => $activity->check_score,
        'aiNames' => $activity->ai_names_count,
        'photos' => $activity->photos->values()->map(fn ($photo, $i) => [
            'url' => route('activities.photo', [$activity, $photo]),
            'label' => 'Photo '.($i + 1),
            'summary' => $photo->summary(),
        ])->all(),
        'verifyUrl' => route('admin.activities.verify', $activity),
        'rejectUrl' => route('admin.activities.reject', $activity),
        'linkUrl' => route('admin.activities.link-event', $activity),
        'events' => $nearbyEvents->filter(fn ($event) => abs($event->held_on->diffInDays($activity->activity_date)) <= $window)
            ->map(fn ($event) => ['id' => $event->id, 'label' => $event->title.' · '.$event->held_on->format('d M Y')])->values()->all(),
    ]);
@endphp

<x-layouts.app header="Verify activities"
               subheader="Logged activities count toward an organization's score only after verification. Sort the list or narrow it to the dates the activities were held.">
    <x-filter-bar search="Search activity, organization or barangay" :chips="\App\Http\Controllers\Admin\ActivityVerificationController::FILTERS"
                  :counts="['pending' => $pendingCount]" :keep="['sort', 'from', 'to']" />

    {{-- Sort and "held between" dates; keeps the status chip and the search. --}}
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <input type="hidden" name="status" value="{{ $currentStatus }}">
        @if (request()->filled('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
        <div>
            <label for="sort" class="field-label">Sort by</label>
            <select id="sort" name="sort" class="field-input w-auto" x-data @change="$el.form.requestSubmit()">
                @foreach ($sorts as $value => $label)
                    <option value="{{ $value }}" @selected($currentSort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="from" class="field-label">Held from</label>
            <input type="date" id="from" name="from" value="{{ $from }}" max="{{ now()->toDateString() }}" class="field-input w-auto">
        </div>
        <div>
            <label for="to" class="field-label">to</label>
            <input type="date" id="to" name="to" value="{{ $to }}" max="{{ now()->toDateString() }}" class="field-input w-auto">
        </div>
        <button type="submit" class="btn-secondary">Apply</button>
        @if ($from || $to || request()->filled('sort'))
            <a href="{{ route('admin.activities.index', array_filter(['status' => $currentStatus, 'q' => request('q')])) }}" class="btn-ghost">Clear dates and sort</a>
        @endif
    </form>

    @if ($pending && $pendingCount)
        {{-- The automated result (free checks, and the AI reading when switched on), with the bulk action. --}}
        <section class="card mb-4 flex flex-wrap items-center gap-3 p-4" aria-label="Checks">
            <x-phosphor-sparkle class="h-5 w-5 shrink-0 text-primary" aria-hidden="true" />
            <div class="flex flex-wrap gap-2 text-sm">
                <a href="{{ route('admin.activities.index', array_filter(['q' => request('q'), 'sort' => request('sort'), 'from' => $from, 'to' => $to])) }}"
                   class="filter-chip" @if (! $currentCheck) aria-current="page" @endif>All waiting</a>
                @foreach (\App\Http\Controllers\Admin\ActivityVerificationController::CHECKS as $key => $label)
                    <a href="{{ route('admin.activities.index', array_filter(['check' => $key, 'q' => request('q'), 'sort' => request('sort'), 'from' => $from, 'to' => $to])) }}"
                       class="filter-chip" @if ($currentCheck === $key) aria-current="page" @endif>{{ $label }} <span class="tabular-nums text-muted">{{ $checkCounts[$key] }}</span></a>
                @endforeach
            </div>
            <div class="ml-auto flex flex-wrap items-center gap-2">
                @if ($aiEnabled && $unreadSheets)
                    <form method="POST" action="{{ route('admin.activities.read-sheets') }}">
                        @csrf
                        <button type="submit" class="btn-secondary btn-sm">
                            <x-phosphor-sparkle class="h-4 w-4" aria-hidden="true" />Read {{ min($unreadSheets, \App\Http\Controllers\Admin\ActivityVerificationController::READ_BATCH) }} new {{ Str::plural('sheet', $unreadSheets) }} with AI
                        </button>
                    </form>
                @endif
                @if ($checkCounts['consistent'])
                    <form method="POST" action="{{ route('admin.activities.verify-consistent') }}"
                          x-data @submit="if (! confirm(@js('Verify all '.$checkCounts['consistent'].' activities marked Verified by the checks? Needs review and Flagged ones stay for you to open.'))) $event.preventDefault()">
                        @csrf
                        <button type="submit" class="btn-primary btn-sm">
                            <x-phosphor-checks class="h-4 w-4" aria-hidden="true" />Verify all {{ $checkCounts['consistent'] }} marked Verified
                        </button>
                    </form>
                @endif
            </div>
            <div class="w-full space-y-1 text-xs text-muted">
                <p>
                    <span class="font-semibold text-ink">Decision support, not a final verdict.</span>
                    Each activity's sheet, photos and venue are checked automatically for reused or re-saved pictures, editing apps,
                    and photo dates and places that don't match, giving a confidence score from 0 to 100:
                    Verified ({{ \App\Services\SheetChecks::VERIFIED_FROM }} and up), Needs review, or Flagged (below {{ \App\Services\SheetChecks::FLAGGED_BELOW }}, or a reused file).
                    Open the ones that are not Verified; you decide each one.
                </p>
                <p>{{ $aiEnabled ? 'Sheets and photos are also read by AI (Claude) to count names, read the date and venue, and describe the photos.' : 'AI reading of sheets and photos is off.' }}</p>
            </div>
        </section>
    @endif

    <div x-data="activityReview(@js($items))" @row-open="show($event.detail.index)">
        <section class="card">
            @if ($activities->isEmpty())
                <x-empty-state :filtered="$filtered" :clear-url="route('admin.activities.index', $pending ? [] : ['status' => $currentStatus])"
                               :title="$pending ? 'No activities are waiting for verification.' : 'No '.$currentStatus.' activities yet.'" icon="check-square">
                    @if ($pending)
                        Activities appear here as organizations log them.
                    @endif
                </x-empty-state>
            @else
                {{-- Shown when every row on this page has just been decided. --}}
                <div x-show="! queue.length" x-cloak>
                    <x-empty-state title="All caught up on this page." icon="check-square">
                        <a href="{{ request()->fullUrlWithoutQuery('page') }}" class="entity-link">Refresh the list</a> to see what is still waiting.
                    </x-empty-state>
                </div>

                <div class="table-scroll" x-show="queue.length">
                    <table class="table-stack w-full">
                        <caption class="sr-only">{{ \App\Http\Controllers\Admin\ActivityVerificationController::FILTERS[$currentStatus] }} activities. Use the arrow keys to move between rows and Enter to review one.</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="table-head">Activity</th>
                                <th scope="col" class="table-head w-32">Date</th>
                                <th scope="col" class="table-head w-36 text-right">Residents reached</th>
                                <th scope="col" class="table-head w-56">Automated check</th>
                                <th scope="col" class="table-head w-32">{{ $pending ? 'Waiting' : ($currentStatus === 'verified' ? 'Verified' : 'Rejected') }}</th>
                                <th scope="col" class="table-head w-12"><span class="sr-only">Review</span></th>
                            </tr>
                        </thead>
                        <tbody x-data="tableNav" @click="onClick" @keydown="onKeydown">
                            @foreach ($activities as $activity)
                                @php $waiting = (int) $activity->created_at->startOfDay()->diffInDays(today()); @endphp
                                <tr class="table-row-link" data-row data-index="{{ $loop->index }}" tabindex="{{ $loop->first ? 0 : -1 }}"
                                    x-show="! decided.includes({{ $activity->id }})" aria-haspopup="dialog">
                                    <td class="table-cell min-w-72">
                                        <p class="font-semibold text-ink">{{ $activity->title }}</p>
                                        <p class="text-sm text-muted">{{ $activity->organization->name }} &middot; Brgy. {{ $activity->organization->barangay }}</p>
                                    </td>
                                    <td class="table-date" data-label="Held">{{ $activity->activity_date->format('d M Y') }}</td>
                                    <td class="table-num" data-label="Residents reached">{{ $activity->participants_estimate !== null ? number_format($activity->participants_estimate) : '--' }}</td>
                                    <td class="table-cell text-sm" data-label="Automated check">
                                        @php $findings = $activity->checkFindings(); @endphp
                                        @if ($activity->checkLabel())
                                            <span class="inline-flex flex-wrap items-center gap-2">
                                                <span @class([
                                                    'badge-success' => $activity->check_status === 'consistent',
                                                    'badge-warning' => $activity->check_status === 'review',
                                                    'badge-danger' => $activity->check_status === 'flagged',
                                                ])>{{ $activity->checkLabel() }}</span>
                                                @if ($activity->check_score !== null)
                                                    <span class="tabular-nums text-muted" title="Confidence score">{{ $activity->check_score }}/100</span>
                                                @endif
                                            </span>
                                            @if ($activity->check_status !== 'consistent' && $findings)
                                                <span class="mt-1 block text-xs text-muted">{{ $findings[0] }}@if (count($findings) > 1) (+{{ count($findings) - 1 }} more)@endif</span>
                                            @endif
                                        @else
                                            <span class="badge-neutral">Not checked</span>
                                        @endif
                                        @if ($activity->ai_names_count !== null)
                                            <span class="mt-1 block text-xs text-muted">AI counted {{ $activity->ai_names_count }} {{ Str::plural('name', $activity->ai_names_count) }}</span>
                                        @endif
                                    </td>
                                    @if ($pending)
                                        <td data-label="Waiting" @class(['table-cell whitespace-nowrap tabular-nums', 'font-semibold text-warning' => $waiting > 30, 'text-muted' => $waiting <= 30])>
                                            @if ($waiting > 30)
                                                <x-phosphor-clock-countdown class="-mt-1 mr-1 inline h-4 w-4" aria-hidden="true" />
                                            @endif
                                            {{ $waiting === 0 ? 'Today' : $waiting.' '.Str::plural('day', $waiting) }}
                                            @if ($waiting > 30)<span class="sr-only">, over 30 days</span>@endif
                                        </td>
                                    @else
                                        <td class="table-date" data-label="Decided">{{ $activity->verified_at?->format('d M Y') ?? '--' }}</td>
                                    @endif
                                    <td class="table-cell stack-hide text-right text-muted">
                                        <x-phosphor-caret-right class="ml-auto h-5 w-5" aria-hidden="true" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <x-pagination :paginator="$activities" />
        </section>

        @if ($items->isNotEmpty())
            <x-dialog name="review" width="md">
                <x-slot:header>
                    <div class="min-w-0 flex-1">
                        <h2 id="dialog-review-title" class="card-title" x-text="current?.title"></h2>
                        <p class="mt-1 text-sm text-muted">
                            <span x-text="current?.organization"></span> &middot; Brgy. <span x-text="current?.barangay"></span>
                        </p>
                    </div>
                    <div class="order-last -my-1 flex shrink-0 basis-full items-center sm:order-none sm:basis-auto" x-show="queue.length > 1">
                        <button type="button" class="icon-btn" @click="step(-1)" :disabled="position <= 1">
                            <x-phosphor-caret-left class="h-5 w-5" aria-hidden="true" /><span class="sr-only">Previous activity</span>
                        </button>
                        <span class="min-w-16 text-center text-sm tabular-nums text-muted" aria-live="polite" x-text="position + ' of ' + queue.length"></span>
                        <button type="button" class="icon-btn" @click="step(1)" :disabled="position >= queue.length">
                            <x-phosphor-caret-right class="h-5 w-5" aria-hidden="true" /><span class="sr-only">Next activity</span>
                        </button>
                    </div>
                </x-slot:header>

                <template x-if="current">
                    <div class="flex min-h-0 flex-1 flex-col">
                        <div class="dialog-body space-y-6">
                            <div>
                                <p class="flex items-center gap-2 text-sm tabular-nums text-muted">
                                    <x-phosphor-calendar-blank class="h-4 w-4" aria-hidden="true" />
                                    <span>Held <span x-text="current.date"></span></span>
                                </p>
                                <p class="mt-2 whitespace-pre-line text-sm text-ink" x-text="current.description"></p>
                            </div>

                            <dl class="grid grid-cols-[minmax(0,10rem)_minmax(0,1fr)] gap-x-6 gap-y-2 border-y border-line py-4 text-sm">
                                <dt class="text-muted">Residents reached</dt>
                                <dd class="tabular-nums text-ink" x-text="current.residents"></dd>
                                <dt class="text-muted">Venue</dt>
                                <dd class="text-ink" x-text="current.venue || 'Not given'"></dd>
                                <dt class="text-muted">Logged by</dt>
                                <dd class="text-ink" x-text="current.loggedBy"></dd>
                                <dt class="text-muted">Organized by</dt>
                                <dd class="text-ink" x-text="current.source"></dd>
                                <template x-if="current.partners">
                                    <dt class="text-muted">Partners</dt>
                                </template>
                                <template x-if="current.partners">
                                    <dd class="text-ink" x-text="current.partners"></dd>
                                </template>
                            </dl>

                            {{-- The automated result: decision support, with the reasons both ways. --}}
                            <section aria-label="Automated check" class="rounded-md border px-4 py-3 text-sm"
                                     :class="{
                                         'border-danger/40 bg-danger-subtle': current.check === 'flagged',
                                         'border-warning/40 bg-warning-subtle': current.check === 'review',
                                         'border-success/40 bg-success-subtle': current.check === 'consistent',
                                         'border-line bg-canvas': ! current.check,
                                     }">
                                <p class="flex flex-wrap items-baseline gap-x-2 font-semibold"
                                   :class="{ 'text-danger': current.check === 'flagged', 'text-warning': current.check === 'review', 'text-success': current.check === 'consistent', 'text-muted': ! current.check }">
                                    <span x-text="current.checkLabel ? 'Automated check: ' + current.checkLabel : 'Not checked'"></span>
                                    <span class="tabular-nums" x-show="current.score !== null" x-text="'· confidence ' + current.score + '/100'"></span>
                                </p>
                                <template x-if="current.findings.length">
                                    <div class="mt-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-muted">To look at</p>
                                        <ul class="mt-1 list-disc pl-6 text-ink">
                                            <template x-for="finding in current.findings" :key="finding"><li x-text="finding"></li></template>
                                        </ul>
                                    </div>
                                </template>
                                <template x-if="current.confirmations.length">
                                    <div class="mt-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-muted">What checks out</p>
                                        <ul class="mt-1 list-disc pl-6 text-ink">
                                            <template x-for="item in current.confirmations" :key="item"><li x-text="item"></li></template>
                                        </ul>
                                    </div>
                                </template>
                                <p class="mt-2 text-xs text-muted">Decision support only: the checks can be wrong. You make the decision.</p>
                            </section>

                            <section aria-labelledby="review-evidence">
                                <h3 id="review-evidence" class="text-sm font-semibold text-ink">Evidence: sign-in sheet</h3>
                                <template x-if="current.evidence">
                                    <div class="mt-2 overflow-hidden rounded-md border border-line">
                                        <template x-if="current.evidence.image">
                                            <img :src="current.evidence.url" :alt="'Sign-in sheet for ' + current.title"
                                                 class="h-56 w-full bg-canvas object-contain">
                                        </template>
                                        <template x-if="! current.evidence.image">
                                            <div class="flex h-24 items-center justify-center gap-3 bg-canvas text-sm text-muted">
                                                <x-phosphor-file-pdf class="h-8 w-8" aria-hidden="true" />PDF document
                                            </div>
                                        </template>
                                        <div class="flex items-center gap-3 border-t border-line px-4 py-2 text-sm">
                                            <span class="min-w-0 truncate text-muted" x-text="current.evidence.filename"></span>
                                            <a :href="current.evidence.url" target="_blank" rel="noopener" class="btn-ghost btn-sm ml-auto">
                                                Open file<x-phosphor-arrow-square-out class="h-4 w-4" aria-hidden="true" />
                                                <span class="sr-only">(opens in a new tab)</span>
                                            </a>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="! current.evidence" class="mt-2 rounded-md border border-dashed border-line-strong px-4 py-6 text-center text-sm text-muted">
                                    No sign-in sheet was attached. Ask the organization for one before verifying.
                                </p>
                            </section>

                            <section aria-labelledby="review-photos">
                                <h3 id="review-photos" class="text-sm font-semibold text-ink">
                                    Activity photos <span class="font-normal text-muted" x-text="'(' + current.photos.length + ')'"></span>
                                </h3>
                                <ul class="mt-2 grid gap-3 sm:grid-cols-3" x-show="current.photos.length">
                                    <template x-for="photo in current.photos" :key="photo.url">
                                        <li class="overflow-hidden rounded-md border border-line">
                                            <a :href="photo.url" target="_blank" rel="noopener" class="block bg-canvas">
                                                <img :src="photo.url" :alt="photo.label + ' of ' + current.title" class="h-28 w-full object-cover" loading="lazy">
                                                <span class="sr-only">(opens in a new tab)</span>
                                            </a>
                                            <p class="px-3 py-2 text-xs text-muted">
                                                <span class="font-semibold text-ink" x-text="photo.label"></span>: <span x-text="photo.summary"></span>
                                            </p>
                                        </li>
                                    </template>
                                </ul>
                                <p x-show="! current.photos.length" class="mt-2 text-sm text-muted">No photos were attached.</p>
                            </section>

                            {{-- An LGU event logged as the organization's own: credit it as attending that event instead. --}}
                            <details x-show="current.status === 'pending'" class="rounded-md border border-line px-4 py-3 text-sm">
                                <summary class="cursor-pointer font-semibold text-ink">This was an LGU event</summary>
                                <template x-if="current.events.length">
                                    <div class="mt-3 space-y-3">
                                        <p class="text-muted">Credit the organization as attending the LGU event instead. Its own log is removed.</p>
                                        <label for="review-event" class="field-label">LGU event</label>
                                        <select id="review-event" x-model="eventId" class="field-input">
                                            <option value="">Choose the event</option>
                                            <template x-for="event in current.events" :key="event.id">
                                                <option :value="event.id" x-text="event.label"></option>
                                            </template>
                                        </select>
                                        <button type="button" class="btn-secondary" :disabled="! eventId" @click="link()">
                                            <x-phosphor-flag class="h-4 w-4" aria-hidden="true" />Link to this event
                                        </button>
                                    </div>
                                </template>
                                <p x-show="! current.events.length" class="mt-3 text-muted">
                                    No LGU event is recorded within two weeks of this date.
                                    <a href="{{ route('admin.lgu-events.index') }}" class="entity-link">Add it under LGU events</a>, then link it here.
                                </p>
                            </details>

                            <template x-if="current.status === 'verified'">
                                <p class="rounded-sm border border-success bg-success-subtle px-4 py-3 text-sm text-success">
                                    Verified <span x-text="current.decided"></span><span x-show="current.decidedBy"> by <span x-text="current.decidedBy"></span></span>.
                                </p>
                            </template>
                            <template x-if="current.status === 'rejected'">
                                <div class="rounded-sm border border-danger bg-danger-subtle px-4 py-3 text-sm text-danger">
                                    <p class="font-semibold">Rejected <span x-text="current.decided"></span><span x-show="current.decidedBy"> by <span x-text="current.decidedBy"></span></span></p>
                                    <p class="mt-1" x-text="current.reason"></p>
                                </div>
                            </template>
                        </div>

                        <div class="dialog-footer" x-show="current.status === 'pending' && ! rejecting">
                            <button type="button" class="btn-danger mr-auto"
                                    @click="rejecting = true; $nextTick(() => $refs.reason.focus())">Reject</button>
                            <button type="button" class="btn-primary" @click="verify()">
                                <x-phosphor-check class="h-4 w-4" aria-hidden="true" />Verify activity
                            </button>
                        </div>

                        <div class="dialog-footer" x-show="current.status === 'pending' && rejecting">
                            <div class="w-full">
                                <label for="review-reason" class="field-label field-required">Reason for rejection</label>
                                <textarea id="review-reason" x-ref="reason" x-model="reason" rows="2" maxlength="2000" class="field-input"
                                          aria-describedby="review-reason-count" placeholder="Tell the organization what is missing or needs correcting"></textarea>
                                <p id="review-reason-count" class="field-hint tabular-nums" :class="reasonLength >= 10 && 'text-success'">
                                    <span x-text="reasonLength"></span> characters<span x-show="reasonLength < 10">, at least 10 needed</span>
                                </p>
                                <span class="sr-only" aria-live="polite" x-text="reasonLength >= 10 ? 'The reason is long enough.' : ''"></span>
                            </div>
                            <button type="button" class="btn-secondary" @click="rejecting = false; reason = ''">Cancel</button>
                            <button type="button" class="btn-danger" :disabled="reasonLength < 10" @click="reject()">Confirm rejection</button>
                        </div>

                        <div class="dialog-footer" x-show="current.status !== 'pending'">
                            <button type="button" class="btn-secondary" @click="close()">Close</button>
                        </div>
                    </div>
                </template>
            </x-dialog>
        @endif

        {{-- Toast: the decision, with Undo until it is sent. --}}
        <div x-show="toast" x-cloak role="status" aria-live="polite" class="toast"
             x-transition:enter="transition duration-base ease-out-strong" x-transition:enter-start="translate-y-2 opacity-0"
             x-transition:leave="transition duration-fast ease-out-strong" x-transition:leave-end="opacity-0">
            <span x-text="toast?.message"></span>
            <button type="button" x-show="toast?.undo" class="toast-action" @click="undo()">Undo</button>
        </div>
    </div>
</x-layouts.app>
