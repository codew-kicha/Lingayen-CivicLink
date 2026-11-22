{{--
    Activity fields shared by the CSO log and edit forms and PESO's assisted encoding (always the
    organization's own, self-initiated work).
    Needs $partnerOptions (id => name); $activity (optional) fills in an existing activity.
    The venue and up to three photos feed the automated checks (requirement 6).
--}}
@php $activity ??= null; @endphp
<div>
    <label for="title" class="field-label">Activity title</label>
    <input type="text" id="title" name="title" value="{{ old('title', $activity?->title) }}"
           class="field-input @error('title') field-input-error @enderror"
           @error('title') aria-invalid="true" aria-describedby="title-error" @enderror required>
    @error('title')<p id="title-error" class="field-error">{{ $message }}</p>@enderror
</div>

<div>
    <label for="activity_date" class="field-label">Date held</label>
    <input type="date" id="activity_date" name="activity_date" value="{{ old('activity_date', $activity?->activity_date?->toDateString()) }}"
           max="{{ now()->toDateString() }}"
           class="field-input @error('activity_date') field-input-error @enderror"
           @error('activity_date') aria-invalid="true" aria-describedby="activity_date-error" @enderror required>
    @error('activity_date')<p id="activity_date-error" class="field-error">{{ $message }}</p>@enderror
</div>

<div>
    <label for="venue" class="field-label">Venue <span class="font-normal text-muted">(optional)</span></label>
    <input type="text" id="venue" name="venue" maxlength="150" value="{{ old('venue', $activity?->venue) }}"
           placeholder="e.g. Barangay Hall, Libsong East"
           class="field-input @error('venue') field-input-error @enderror"
           @error('venue') aria-invalid="true" aria-describedby="venue-error" @enderror>
    @error('venue')<p id="venue-error" class="field-error">{{ $message }}</p>@enderror
</div>

<div>
    <label for="participants_estimate" class="field-label">
        Residents reached <span class="font-normal text-muted">(optional)</span>
    </label>
    <input type="number" id="participants_estimate" name="participants_estimate"
           value="{{ old('participants_estimate', $activity?->participants_estimate) }}" min="0"
           class="field-input @error('participants_estimate') field-input-error @enderror">
    <p class="field-hint">An estimate is fine.</p>
    @error('participants_estimate')<p class="field-error">{{ $message }}</p>@enderror
</div>

<div>
    <label for="description" class="field-label">What took place</label>
    <textarea id="description" name="description" rows="4"
              class="field-input @error('description') field-input-error @enderror"
              @error('description') aria-invalid="true" aria-describedby="description-error" @enderror required>{{ old('description', $activity?->description) }}</textarea>
    @error('description')<p id="description-error" class="field-error">{{ $message }}</p>@enderror
</div>

<div>
    <label for="evidence" class="field-label">Sign-in sheet <span class="font-normal text-muted">(optional)</span></label>
    @if ($activity?->evidence_path)
        <div class="mb-2 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-sm border border-line bg-canvas px-3 py-2 text-sm" x-data="{ remove: @js((bool) old('remove_evidence')) }">
            <span class="min-w-0 truncate text-ink" :class="remove && 'line-through text-muted'">
                <x-phosphor-paperclip class="-mt-1 mr-1 inline h-4 w-4 text-muted" aria-hidden="true" />{{ $activity->evidence_filename }}
            </span>
            <a href="{{ route('activities.evidence', $activity) }}" target="_blank" rel="noopener" class="entity-link">Open<span class="sr-only"> the current sign-in sheet (opens in a new tab)</span></a>
            <label class="ml-auto flex min-h-11 items-center gap-2 text-muted">
                <input type="checkbox" name="remove_evidence" value="1" x-model="remove" class="rounded-sm border-line-strong text-primary">
                Remove it
            </label>
        </div>
    @endif
    {{-- The sheet's text is read in the browser (as for requirements) only to check it against the
         activity's date and title; the text itself is never kept. --}}
    <div x-data="documentPrecheck({})">
        <x-file-field>
            <input type="file" id="evidence" name="evidence" accept=".pdf,.jpg,.jpeg,.png" @change="check($event)"
                   aria-describedby="evidence-hint{{ $errors->has('evidence') ? ' evidence-error' : '' }}"
                   class="field-file @error('evidence') field-input-error @enderror">
        </x-file-field>
        <input type="hidden" name="evidence_text" :value="text">
    </div>
    <p id="evidence-hint" class="field-hint">A photo or scan of the attendance sheet (PDF, JPG or PNG, up to 5 MB). PESO checks it before verifying.@if ($activity?->evidence_path) Choosing a file replaces the current one.@endif</p>
    @error('evidence')<p id="evidence-error" class="field-error">{{ $message }}</p>@enderror
</div>

{{-- Up to three photos taken at the activity. Original photos from the phone carry the date and place
     they were taken, which the checks compare with the activity. --}}
@php
    $photos = $activity?->photos ?? collect();
    $removing = array_map('intval', old('remove_photos', []));
@endphp
<div x-data="{ kept: {{ $photos->count() - count(array_intersect($removing, $photos->pluck('id')->all())) }}, chosen: 0 }">
    <label for="photos" class="field-label">Activity photos <span class="font-normal text-muted">(optional, up to {{ \App\Models\ActivityPhoto::MAX }})</span></label>
    @if ($photos->isNotEmpty())
        <ul class="mb-2 grid gap-2 sm:grid-cols-3">
            @foreach ($photos as $photo)
                <li class="overflow-hidden rounded-sm border border-line bg-canvas text-sm" x-data="{ remove: @js(in_array($photo->id, $removing, true)) }">
                    <img src="{{ route('activities.photo', [$activity, $photo]) }}" alt="Photo {{ $loop->iteration }} of this activity"
                         class="h-24 w-full object-cover" :class="remove && 'opacity-40'" loading="lazy">
                    <label class="flex min-h-11 items-center gap-2 px-3 text-muted">
                        <input type="checkbox" name="remove_photos[]" value="{{ $photo->id }}" x-model="remove"
                               @change="kept += remove ? -1 : 1" class="rounded-sm border-line-strong text-primary">
                        Remove
                    </label>
                </li>
            @endforeach
        </ul>
    @endif
    <x-file-field>
        <input type="file" id="photos" name="photos[]" accept=".jpg,.jpeg,.png,.webp" multiple
               @change="chosen = $event.target.files.length"
               aria-describedby="photos-hint{{ $errors->has('photos') || $errors->has('photos.*') ? ' photos-error' : '' }}"
               class="field-file @if ($errors->has('photos') || $errors->has('photos.*')) field-input-error @endif">
    </x-file-field>
    <p id="photos-hint" class="field-hint">
        JPG, PNG or WebP, up to 5 MB each. Upload the original photos from the phone, not ones sent through chat apps,
        so the date and place they were taken can be checked.
    </p>
    <p class="field-error" x-cloak x-show="kept + chosen > {{ \App\Models\ActivityPhoto::MAX }}" aria-live="polite">
        That makes <span x-text="kept + chosen"></span> photos; attach at most {{ \App\Models\ActivityPhoto::MAX }}.
    </p>
    @error('photos')<p id="photos-error" class="field-error">{{ $message }}</p>@enderror
    @error('photos.*')<p class="field-error">{{ $message }}</p>@enderror
</div>

{{-- No "who organized it" question (pre-final defense panel): what the organization logs is its own
     work. LGU events it attends are recorded and credited by the Desk Office (Admin\LguEventController). --}}
<p class="flex items-start gap-2 rounded-sm bg-canvas px-3 py-2 text-sm text-muted">
    <x-phosphor-info class="mt-hair h-4 w-4 shrink-0" aria-hidden="true" />
    <span>Log the activities your organization runs. LGU events you attend are recorded by the Desk Office; you don't need to log them.</span>
</p>

@if ($partnerOptions)
    <fieldset x-data="{ q: '', picked: @js(array_map('intval', old('partners', $activity?->partnerOrganizations->pluck('id')->all() ?? []))) }">
        <legend class="field-label">Partner organizations <span class="font-normal text-muted">(optional)</span></legend>
        <p class="field-hint mt-0">Organizations that ran this with you. Each is credited once the Desk Office verifies the activity.</p>
        <label for="partner-search" class="sr-only">Search organizations</label>
        <input type="search" id="partner-search" x-model="q" placeholder="Search by name" class="field-input mt-2">
        <div class="mt-2 max-h-48 overflow-y-auto rounded-sm border border-line">
            @foreach ($partnerOptions as $id => $name)
                <label class="flex cursor-pointer items-center gap-2 px-3 py-2 text-sm text-ink hover:bg-primary-subtle"
                       x-show="! q || @js(mb_strtolower($name)).includes(q.toLowerCase())">
                    <input type="checkbox" name="partners[]" value="{{ $id }}" x-model.number="picked">
                    {{ $name }}
                </label>
            @endforeach
        </div>
        <p class="field-hint" x-cloak x-show="picked.length" x-text="picked.length + ' selected'"></p>
        @error('partners')<p class="field-error">{{ $message }}</p>@enderror
        @error('partners.*')<p class="field-error">{{ $message }}</p>@enderror
    </fieldset>
@endif
