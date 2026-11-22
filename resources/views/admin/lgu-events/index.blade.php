{{--
    LGU events (pre-final defense panel): activities the LGU organizes are recorded here by the office,
    not logged by the CSOs. Create an event (and announce it as a notice), then, once it is held, open
    it to tick the organizations that attended; each is credited automatically.
--}}
<x-layouts.app header="LGU events"
               subheader="Activities the LGU organizes. Record them here and tick the organizations that attended; each is credited without logging anything.">
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1.3fr)]">
        <section class="panel-flat h-fit p-6" aria-labelledby="new-event-title">
            <h2 id="new-event-title" class="font-semibold text-ink">New LGU event</h2>
            <form method="POST" action="{{ route('admin.lgu-events.store') }}" class="mt-6 space-y-6"
                  x-data="{ announce: @js((bool) old('announce', true)) }">
                @csrf
                @include('admin.lgu-events.fields')

                <div class="rounded-md border border-line p-4">
                    <label class="flex items-start gap-3 text-sm text-ink">
                        <input type="hidden" name="announce" value="0">
                        <input type="checkbox" name="announce" value="1" x-model="announce" class="mt-hair h-5 w-5 shrink-0 rounded-sm border-line-strong text-primary">
                        <span>Announce it to organizations
                            <span class="block text-muted">Sent as a notice to their Notifications; it also shows on their Activities page until the day.</span></span>
                    </label>
                    {{-- Only posted when announcing, so an unannounced event needs no audience. --}}
                    <template x-if="announce">
                        <div class="mt-4 space-y-6">
                            @include('admin.notices.audience-fields', ['idPrefix' => 'event', 'choices' => $choices])
                            <label class="flex items-start gap-3 text-sm text-ink">
                                <input type="checkbox" name="email" value="1" @checked(old('email')) class="mt-hair h-5 w-5 shrink-0 rounded-sm border-line-strong text-primary">
                                <span>Also email it <span class="block text-muted">To each representative whose email is confirmed.</span></span>
                            </label>
                        </div>
                    </template>
                </div>

                <button type="submit" class="btn-primary w-full">Save LGU event</button>
            </form>
        </section>

        <div class="space-y-6">
            @foreach (['Upcoming' => $upcoming, 'Held' => $held] as $heading => $events)
                <section class="panel-flat overflow-hidden" aria-labelledby="events-{{ Str::slug($heading) }}">
                    <div class="border-b border-line px-4 py-3">
                        <h2 id="events-{{ Str::slug($heading) }}" class="font-semibold text-ink">{{ $heading }} <span class="font-normal tabular-nums text-muted">{{ $events->count() }}</span></h2>
                    </div>
                    @if ($events->isEmpty())
                        <p class="px-4 py-8 text-center text-sm text-muted">
                            {{ $heading === 'Upcoming' ? 'No LGU event is coming up.' : 'No LGU event has been held yet.' }}
                        </p>
                    @else
                        <ul class="divide-y divide-line">
                            @foreach ($events as $event)
                                <li>
                                    <a href="{{ route('admin.lgu-events.show', $event) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 hover:bg-primary-subtle focus-visible:outline-focus">
                                        <span class="w-24 shrink-0 text-sm font-semibold tabular-nums text-brand-700">{{ $event->held_on->format('d M Y') }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block font-semibold text-ink">{{ $event->title }}</span>
                                            <span class="block text-xs text-muted">
                                                {{ $event->venue ?? 'No venue given' }}
                                                · {{ $event->announcement ? 'Announced to '.$event->announcement->recipients.' '.Str::plural('organization', $event->announcement->recipients) : 'Not announced' }}
                                            </span>
                                        </span>
                                        <span class="text-sm tabular-nums text-muted">
                                            @if ($event->isHeld())
                                                {{ $event->attendance_count }} attended
                                            @else
                                                Attendance on the day
                                            @endif
                                        </span>
                                        <x-phosphor-caret-right class="h-4 w-4 text-muted" aria-hidden="true" />
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</x-layouts.app>
