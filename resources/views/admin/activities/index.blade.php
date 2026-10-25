@php
    $pending = $currentStatus === 'pending';
    $filtered = request()->filled('q');

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
        'verifyUrl' => route('admin.activities.verify', $activity),
        'rejectUrl' => route('admin.activities.reject', $activity),
    ]);
@endphp

<x-layouts.app header="Verify activities"
               subheader="Logged activities count toward an organization's score only after verification. The oldest wait first.">
    <x-filter-bar search="Search activity, organization or barangay" :chips="\App\Http\Controllers\Admin\ActivityVerificationController::FILTERS"
                  :counts="['pending' => $pendingCount]" />

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
                                <dt class="text-muted">Logged by</dt>
                                <dd class="text-ink" x-text="current.loggedBy"></dd>
                                <dt class="text-muted" x-text="current.status === 'pending' ? 'Claimed source' : 'Source'"></dt>
                                <dd class="text-ink" x-text="current.source"></dd>
                                <template x-if="current.partners">
                                    <dt class="text-muted">Partners</dt>
                                </template>
                                <template x-if="current.partners">
                                    <dd class="text-ink" x-text="current.partners"></dd>
                                </template>
                            </dl>

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

                            <div x-show="current.status === 'pending'">
                                <label for="review-source" class="field-label field-required">Verified source</label>
                                <select id="review-source" x-model="source" required class="field-input" aria-describedby="review-source-hint">
                                    <option value="" disabled>Choose who organized it</option>
                                    @foreach (\App\Models\Activity::SOURCES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p id="review-source-hint" class="field-hint">
                                    Confirm who organized it; the organization claimed <span class="font-semibold text-ink" x-text="current.source"></span>.
                                    Verify activity unlocks once you choose.
                                </p>
                            </div>

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
                            <button type="button" class="btn-primary" :disabled="! source" @click="verify()">
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
