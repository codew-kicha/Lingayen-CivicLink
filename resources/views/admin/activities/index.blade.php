<x-layouts.admin header="Activity verification"
                 subheader="Logged activities count toward an organization's score only after verification.">
    <nav aria-label="Filter by status" class="mb-4 flex flex-wrap gap-1">
        @foreach (['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected'] as $status => $label)
            <a href="{{ route('admin.activities.index', ['status' => $status]) }}"
               @if ($currentStatus === $status) aria-current="page" @endif
               class="rounded-sm px-3 py-1.5 text-sm font-medium transition-colors duration-fast ease-out-strong
                      {{ $currentStatus === $status ? 'bg-navy-600 text-paper' : 'text-navy-700 hover:bg-navy-50' }}">
                {{ $label }}
                @if ($status === 'pending' && $pendingCount)
                    <span class="ml-1 font-mono tabular-nums">{{ $pendingCount }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    @if ($activities->isEmpty())
        <div class="panel-flat px-4 py-14 text-center">
            <p class="font-medium text-ink">Nothing here.</p>
            <p class="mt-1 text-sm text-muted">
                No {{ $currentStatus }} activities. Activities appear here as organizations log them.
            </p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($activities as $activity)
                <article class="panel-flat p-5">
                    <div class="flex flex-wrap items-start gap-3">
                        <div class="min-w-0">
                            <h2 class="font-semibold text-ink">{{ $activity->title }}</h2>
                            <p class="mt-0.5 text-sm text-muted">
                                {{ $activity->organization->name }}
                                &middot; Brgy. {{ $activity->organization->barangay }}
                                &middot; <span class="font-mono tabular-nums">{{ $activity->activity_date->format('d M Y') }}</span>
                            </p>
                        </div>
                        <span class="ml-auto shrink-0"><x-status-badge :status="$activity->status" /></span>
                    </div>

                    <p class="mt-3 max-w-[68ch] text-sm text-muted">{{ $activity->description }}</p>

                    <dl class="mt-3 flex flex-wrap gap-x-8 gap-y-1 text-sm">
                        <div class="flex gap-2">
                            <dt class="text-muted">Residents reached</dt>
                            <dd class="font-mono tabular-nums text-ink">
                                {{ $activity->participants_estimate ? number_format($activity->participants_estimate) : 'Not stated' }}
                            </dd>
                        </div>
                        <div class="flex gap-2">
                            <dt class="text-muted">Logged by</dt>
                            <dd class="text-ink">{{ $activity->loggedBy?->name ?? 'Not recorded' }}</dd>
                        </div>
                    </dl>

                    @if ($activity->status === 'rejected' && $activity->rejection_reason)
                        <p class="mt-3 rounded-sm border border-danger-600 bg-danger-100 px-3 py-2 text-sm text-danger-600">
                            {{ $activity->rejection_reason }}
                        </p>
                    @endif

                    @if ($activity->status === 'pending')
                        <div class="mt-4 flex flex-wrap items-start gap-3 border-t border-line pt-4">
                            <form method="POST" action="{{ route('admin.activities.verify', $activity) }}">
                                @csrf
                                <button type="submit" class="btn-primary">Verify activity</button>
                            </form>

                            <form method="POST" action="{{ route('admin.activities.reject', $activity) }}"
                                  class="flex flex-1 flex-wrap items-start gap-2">
                                @csrf
                                <div class="min-w-[16rem] flex-1">
                                    <label for="reason-{{ $activity->id }}" class="sr-only">Reason for rejection</label>
                                    <input type="text" id="reason-{{ $activity->id }}" name="rejection_reason"
                                           placeholder="Reason for rejection"
                                           class="field-input @error('rejection_reason') field-input-error @enderror">
                                </div>
                                <button type="submit" class="btn-danger">Reject</button>
                            </form>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-4">{{ $activities->links() }}</div>
    @endif
</x-layouts.admin>
