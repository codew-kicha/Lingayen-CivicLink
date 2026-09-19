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

                    <div>
                        <label for="title" class="field-label">Activity title</label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}"
                               class="field-input @error('title') field-input-error @enderror"
                               @error('title') aria-invalid="true" aria-describedby="title-error" @enderror required>
                        @error('title')<p id="title-error" class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="activity_date" class="field-label">Date held</label>
                        <input type="date" id="activity_date" name="activity_date" value="{{ old('activity_date') }}"
                               max="{{ now()->toDateString() }}"
                               class="field-input @error('activity_date') field-input-error @enderror"
                               @error('activity_date') aria-invalid="true" aria-describedby="activity_date-error" @enderror required>
                        @error('activity_date')<p id="activity_date-error" class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="participants_estimate" class="field-label">
                            Residents reached <span class="font-normal text-muted">(optional)</span>
                        </label>
                        <input type="number" id="participants_estimate" name="participants_estimate"
                               value="{{ old('participants_estimate') }}" min="0"
                               class="field-input @error('participants_estimate') field-input-error @enderror">
                        <p class="field-hint">An estimate is fine.</p>
                        @error('participants_estimate')<p class="field-error">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="description" class="field-label">What took place</label>
                        <textarea id="description" name="description" rows="4"
                                  class="field-input @error('description') field-input-error @enderror"
                                  @error('description') aria-invalid="true" aria-describedby="description-error" @enderror required>{{ old('description') }}</textarea>
                        @error('description')<p id="description-error" class="field-error">{{ $message }}</p>@enderror
                    </div>

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
            </section>
        </div>
    @endif
</x-layouts.cso>
