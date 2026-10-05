<x-layouts.cso header="Activities"
               subheader="Log the community work your organization does. PESO verifies each entry before it counts toward your score.">
    @if (! $organization)
        <section class="panel p-8 text-center">
            <p class="font-medium text-ink">Set up your organization profile first.</p>
            <a href="{{ route('cso.profile.edit') }}" class="btn-primary mt-4">Set up profile</a>
        </section>
    @else
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]">
            <section class="panel h-fit p-5">
                <h2 class="font-semibold text-ink">Log an activity</h2>
                <p class="mt-0.5 text-sm text-muted">All fields required unless marked optional.</p>

                <form method="POST" action="{{ route('cso.activities.store') }}" class="mt-5 space-y-5">
                    @csrf

                    @include('activities.fields')

                    <button type="submit" class="btn-primary w-full">Log activity</button>
                </form>
            </section>

            <section>
                <h2 class="font-semibold text-ink">Activity history</h2>

                @if ($activities->isEmpty())
                    <div class="panel mt-4 p-8 text-center">
                        <p class="font-medium text-ink">No activities logged yet.</p>
                        <p class="mt-1 text-sm text-muted">
                            Logged activities appear here, and on your public listing once verified.
                        </p>
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($activities as $activity)
                            <article class="panel p-5">
                                <div class="flex flex-wrap items-start gap-3">
                                    <div>
                                        <h3 class="font-semibold text-ink">{{ $activity->title }}</h3>
                                        <p class="mt-0.5 font-mono text-sm tabular-nums text-muted">
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
                                        <span class="font-mono tabular-nums text-ink">
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
                                    <p class="mt-3 rounded-sm border border-danger-600 bg-danger-100 px-3 py-2 text-sm text-danger-600">
                                        {{ $activity->rejection_reason }}
                                    </p>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-4">{{ $activities->links() }}</div>
                @endif

                @if ($taggedIn->isNotEmpty())
                    <h2 class="mt-8 font-semibold text-ink">Tagged as a partner</h2>
                    <p class="mt-0.5 text-sm text-muted">Logged by other organizations. Verified entries count toward your score too.</p>
                    <ul class="panel mt-4 divide-y divide-line">
                        @foreach ($taggedIn as $activity)
                            <li class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
                                <span class="min-w-0">
                                    <span class="font-medium text-ink">{{ $activity->title }}</span>
                                    <span class="block text-muted">{{ $activity->organization->name }} &middot; <span class="font-mono tabular-nums">{{ $activity->activity_date->format('d M Y') }}</span></span>
                                </span>
                                <span class="ml-auto shrink-0"><x-status-badge :status="$activity->status" /></span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif
</x-layouts.cso>
