{{-- An LGU event's details: title, date, venue and what it is. $event (optional) fills them in. --}}
@php $event ??= null; @endphp

<div>
    <label for="event-title" class="field-label">Event</label>
    <input id="event-title" name="title" value="{{ old('title', $event?->title) }}" required maxlength="255"
           placeholder="e.g. Municipal road safety seminar"
           @error('title') aria-invalid="true" aria-describedby="event-title-error" @enderror
           class="field-input @error('title') field-input-error @enderror">
    @error('title')<p id="event-title-error" class="field-error">{{ $message }}</p>@enderror
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="event-held_on" class="field-label">Date</label>
        <input type="date" id="event-held_on" name="held_on" value="{{ old('held_on', $event?->held_on?->toDateString()) }}" required
               @error('held_on') aria-invalid="true" aria-describedby="event-held_on-error" @enderror
               class="field-input @error('held_on') field-input-error @enderror">
        @error('held_on')<p id="event-held_on-error" class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="event-venue" class="field-label">Venue <span class="font-normal text-muted">(optional)</span></label>
        <input id="event-venue" name="venue" value="{{ old('venue', $event?->venue) }}" maxlength="150"
               placeholder="e.g. Municipal Gymnasium"
               class="field-input @error('venue') field-input-error @enderror">
        @error('venue')<p class="field-error">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label for="event-description" class="field-label">What it is</label>
    <textarea id="event-description" name="description" rows="3" required maxlength="2000"
              placeholder="Who runs it, who should come, and what organizations will do"
              @error('description') aria-invalid="true" aria-describedby="event-description-error" @enderror
              class="field-input @error('description') field-input-error @enderror">{{ old('description', $event?->description) }}</textarea>
    @error('description')<p id="event-description-error" class="field-error">{{ $message }}</p>@enderror
</div>
