{{-- The memorandum and dates of a free period; old input only refills the form that was sent. --}}
@php
    $mine = $period ? (int) old('period_id') === $period->id : ! old('period_id');
    $value = fn (string $field, $fallback) => $mine ? old($field, $fallback) : $fallback;
    $error = fn (string $field) => $mine ? $errors->first($field) : null;
@endphp

<div>
    <label for="{{ $prefix }}-memorandum" class="field-label">Memorandum</label>
    <input type="text" id="{{ $prefix }}-memorandum" name="memorandum" maxlength="120" required
           value="{{ $value('memorandum', $period?->memorandum) }}" placeholder="e.g. DILG MC No. 2025-060"
           @if ($error('memorandum')) aria-invalid="true" aria-describedby="{{ $prefix }}-memorandum-error" @endif
           @class(['field-input', 'field-input-error' => $error('memorandum')])>
    @if ($error('memorandum'))<p id="{{ $prefix }}-memorandum-error" class="field-error">{{ $error('memorandum') }}</p>@endif
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="{{ $prefix }}-starts_on" class="field-label">Free from</label>
        <input type="date" id="{{ $prefix }}-starts_on" name="starts_on" required
               value="{{ $value('starts_on', $period?->starts_on?->toDateString()) }}"
               @if ($error('starts_on')) aria-invalid="true" aria-describedby="{{ $prefix }}-starts_on-error" @endif
               @class(['field-input', 'field-input-error' => $error('starts_on')])>
        @if ($error('starts_on'))<p id="{{ $prefix }}-starts_on-error" class="field-error">{{ $error('starts_on') }}</p>@endif
    </div>
    <div>
        <label for="{{ $prefix }}-ends_on" class="field-label">Until (last free day)</label>
        <input type="date" id="{{ $prefix }}-ends_on" name="ends_on" required
               value="{{ $value('ends_on', $period?->ends_on?->toDateString()) }}"
               @if ($error('ends_on')) aria-invalid="true" aria-describedby="{{ $prefix }}-ends_on-error" @endif
               @class(['field-input', 'field-input-error' => $error('ends_on')])>
        @if ($error('ends_on'))<p id="{{ $prefix }}-ends_on-error" class="field-error">{{ $error('ends_on') }}</p>@endif
    </div>
</div>
<p class="field-hint">Usually the three months the memorandum gives after the new administration takes office.</p>
