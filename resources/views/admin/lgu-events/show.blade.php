{{--
    One LGU event: its details (editable), and, once it is held, the attendance: tick the accredited
    organizations that attended and save. Each ticked organization is credited with a verified
    LGU-organized activity (no residents reached of its own); unticking removes the credit.
--}}
<x-layouts.app :header="$event->title"
               subheader="LGU event, {{ $event->held_on->format('l, d F Y') }}{{ $event->venue ? ' · '.$event->venue : '' }}">
    <x-slot:actions>
        <a href="{{ route('admin.lgu-events.index') }}" class="btn-secondary">All LGU events</a>
    </x-slot:actions>

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
        {{-- Attendance --}}
        <section class="card" aria-labelledby="attendance-title">
            <div class="border-b border-line px-6 py-4">
                <h2 id="attendance-title" class="card-title">Attendance</h2>
                <p class="mt-1 text-sm text-muted">
                    Tick the organizations that attended, from the event's own sign-in sheet. Each is credited with this event as an
                    LGU-organized activity and told in its notifications; it adds no residents reached of its own.
                </p>
            </div>

            @if (! $event->isHeld())
                <p class="flex items-start gap-3 px-6 py-8 text-sm text-muted">
                    <x-phosphor-calendar-blank class="mt-hair h-5 w-5 shrink-0" aria-hidden="true" />
                    Attendance opens on the event day, {{ $event->held_on->format('d F Y') }}.
                </p>
            @elseif ($organizations->isEmpty())
                <p class="px-6 py-8 text-sm text-muted">No organization is accredited yet, so no one can be credited.</p>
            @else
                <form method="POST" action="{{ route('admin.lgu-events.attendance', $event) }}"
                      x-data="{ q: '', picked: @js(array_map('intval', old('organizations', $attending))) }">
                    @csrf
                    <div class="flex flex-wrap items-center gap-3 px-6 pt-4">
                        <label for="attendance-search" class="sr-only">Search organizations</label>
                        <input type="search" id="attendance-search" x-model="q" placeholder="Search by name, sector or barangay" class="field-input min-w-0 flex-1 basis-64">
                        <p class="text-sm tabular-nums text-muted" aria-live="polite"><span x-text="picked.length"></span> of {{ $organizations->count() }} ticked</p>
                    </div>
                    <ul class="mt-4 max-h-[28rem] divide-y divide-line overflow-y-auto border-y border-line">
                        @foreach ($organizations as $organization)
                            <li x-show="! q || @js(mb_strtolower($organization->name.' '.$organization->sector.' '.$organization->barangay)).includes(q.toLowerCase())">
                                <label class="flex cursor-pointer items-center gap-3 px-6 py-3 text-sm hover:bg-primary-subtle">
                                    <input type="checkbox" name="organizations[]" value="{{ $organization->id }}" x-model.number="picked"
                                           class="h-5 w-5 shrink-0 rounded-sm border-line-strong text-primary">
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-semibold text-ink">{{ $organization->name }}</span>
                                        <span class="block text-xs text-muted">{{ $organization->sector }} · Brgy. {{ $organization->barangay }}</span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                    @if ($lapsed->isNotEmpty())
                        {{-- Credited while accredited; not on the checklist now, and their credit stays. --}}
                        <p class="flex items-start gap-2 px-6 pt-4 text-sm text-muted">
                            <x-phosphor-info class="mt-hair h-4 w-4 shrink-0" aria-hidden="true" />
                            <span>Also credited, accredited at the time: {{ $lapsed->join(', ', ' and ') }}. Their accreditation has since lapsed, so they
                                are not listed above; saving keeps their credit.</span>
                        </p>
                    @endif
                    <div class="flex flex-wrap items-center gap-3 px-6 py-4">
                        <button type="submit" class="btn-primary">Save attendance</button>
                        @if ($event->attendance_recorded_at)
                            <p class="text-xs text-muted">Last saved {{ $event->attendance_recorded_at->format('d M Y, g:i A') }}</p>
                        @endif
                    </div>
                </form>
            @endif
        </section>

        <div class="space-y-6">
            <section class="card p-6" aria-labelledby="details-title">
                <h2 id="details-title" class="card-title">Details</h2>
                <p class="mt-1 text-sm text-muted">
                    {{ $event->announcement
                        ? 'Announced '.$event->announcement->created_at->format('d M Y').' to '.$event->announcement->audienceLabel().' ('.$event->announcement->recipients.' '.Str::plural('organization', $event->announcement->recipients).').'
                        : 'Not announced.' }}
                    @if ($event->createdBy) Added by {{ $event->createdBy->name }}. @endif
                </p>
                <form method="POST" action="{{ route('admin.lgu-events.update', $event) }}" class="mt-6 space-y-6">
                    @csrf
                    @method('PUT')
                    @include('admin.lgu-events.fields', ['event' => $event])
                    <button type="submit" class="btn-secondary w-full">Save changes</button>
                </form>
            </section>

            <section class="card p-6" aria-labelledby="delete-title">
                <h2 id="delete-title" class="card-title">Delete this event</h2>
                <p class="mt-1 text-sm text-muted">Removes it and every attendance credit it gave; the organizations' scores are recalculated.</p>
                <form method="POST" action="{{ route('admin.lgu-events.destroy', $event) }}" class="mt-4"
                      x-data @submit="if (! confirm('Delete this LGU event and its attendance credits?')) $event.preventDefault()">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Delete LGU event</button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.app>
