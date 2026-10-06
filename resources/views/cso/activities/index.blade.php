<x-layouts.app header="Activities"
               subheader="Log the community work your organization does. PESO verifies each entry before it counts toward your score.">
    @if (! $organization)
        <section class="panel p-8 text-center">
            <p class="font-semibold text-ink">Set up your organization profile first.</p>
            <a href="{{ route('cso.profile.edit') }}" class="btn-primary mt-4">Set up profile</a>
        </section>
    @else
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]">
            <section class="panel h-fit p-6">
                <h2 class="font-semibold text-ink">Log an activity</h2>
                <p class="mt-1 text-sm text-muted">All fields required unless marked optional.</p>

                <form method="POST" action="{{ route('cso.activities.store') }}" class="mt-6 space-y-6">
                    @csrf

                    @include('activities.fields')

                    <button type="submit" class="btn-primary w-full">Log activity</button>
                </form>
            </section>

            <section>
                <div class="flex flex-wrap items-end gap-3">
                    {{-- Pagination focuses this heading after a page change (#history). --}}
                    <h2 id="history" tabindex="-1" class="card-title mr-auto">Activity history</h2>
                    <form method="GET" class="flex items-end gap-2">
                        <div>
                            <label for="status" class="field-label">Show</label>
                            <select id="status" name="status" class="field-input w-auto py-1 text-sm">
                                <option value="">All activities</option>
                                <option value="pending" @selected(request('status') === 'pending')>Awaiting verification</option>
                                <option value="verified" @selected(request('status') === 'verified')>Verified</option>
                                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                            </select>
                        </div>
                        <x-keep-query />
                        <button type="submit" class="btn-secondary btn-sm">Filter</button>
                    </form>
                </div>

                @if ($activities->isEmpty())
                    <div class="card mt-4">
                        <x-empty-state :filtered="request()->filled('status')" :clear-url="route('cso.activities.index')"
                                       title="No activities logged yet." icon="handshake">
                            Logged activities appear here, and on your public listing once verified.
                        </x-empty-state>
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($activities as $activity)
                            <article class="panel p-6">
                                <div class="flex flex-wrap items-start gap-3">
                                    <div>
                                        <h3 class="font-semibold text-ink">{{ $activity->title }}</h3>
                                        <p class="mt-1 text-sm tabular-nums text-muted">
                                            {{ $activity->activity_date->format('d M Y') }}
                                            <span class="font-sans">&middot; {{ $activity->sourceLabel() }}</span>
                                        </p>
                                    </div>
                                    <span class="ml-auto shrink-0"><x-status-badge :status="$activity->status" /></span>
                                </div>

                                <p class="mt-3 text-sm text-muted">{{ $activity->description }}</p>

                                @if ($activity->participants_estimate)
                                    <p class="mt-2 text-sm text-muted">
                                        Reached about
                                        <span class="tabular-nums text-ink">
                                            {{ number_format($activity->participants_estimate) }}
                                        </span>
                                        residents
                                    </p>
                                @endif

                                @if ($activity->partnerOrganizations->isNotEmpty())
                                    <p class="mt-2 text-sm text-muted">
                                        With {{ $activity->partnerOrganizations->pluck('name')->join(', ', ' and ') }}
                                    </p>
                                @endif

                                @if ($activity->status === 'rejected' && $activity->rejection_reason)
                                    <p class="mt-3 rounded-sm border border-danger bg-danger-subtle px-3 py-2 text-sm text-danger">
                                        {{ $activity->rejection_reason }}
                                    </p>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <x-pagination :paginator="$activities" :default="10" target="history" />
                @endif

                @if ($taggedIn->isNotEmpty())
                    <h2 class="mt-8 font-semibold text-ink">Tagged as a partner</h2>
                    <p class="mt-1 text-sm text-muted">Logged by other organizations. Verified entries count toward your score too.</p>
                    <ul class="panel mt-4 divide-y divide-line">
                        @foreach ($taggedIn as $activity)
                            <li class="flex flex-wrap items-center gap-3 px-4 py-3 text-sm">
                                <span class="min-w-0">
                                    <span class="font-semibold text-ink">{{ $activity->title }}</span>
                                    <span class="block text-muted">{{ $activity->organization->name }} &middot; <span class="tabular-nums">{{ $activity->activity_date->format('d M Y') }}</span></span>
                                </span>
                                <span class="ml-auto shrink-0"><x-status-badge :status="$activity->status" /></span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif
</x-layouts.app>
