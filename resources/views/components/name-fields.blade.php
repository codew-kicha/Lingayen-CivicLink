@props(['person' => null, 'suffix' => true, 'autofocus' => false, 'prefix' => '', 'idPrefix' => null, 'disabled' => null])

{{--
    A person's name in parts (pre-final defense panel): Last name, First name, Middle name (optional)
    and, for accounts, a Suffix. Posts {prefix}last_name, {prefix}first_name, {prefix}middle_name and
    {prefix}suffix; the model composes the display name from them (App\Models\Concerns\HasPersonName).
    $person prefills from a saved record. `prefix` keeps the fields apart from others on the same form
    (the register dialog's rep_); `disabled` is an Alpine expression for fields shown conditionally.
--}}
@php
    $idPrefix ??= $prefix;
    $saved = $person ? $person->nameParts() : [];
    $fields = [
        ['last_name', 'Last name', 'family-name', true],
        ['first_name', 'First name', 'given-name', true],
        ['middle_name', 'Middle name', 'additional-name', false],
    ];
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    @foreach ($fields as [$field, $label, $autocomplete, $required])
        @php $name = $prefix.$field; $id = $idPrefix.$field; @endphp
        <div>
            <label for="{{ $id }}" class="field-label">
                {{ $label }} @unless ($required)<span class="font-normal text-muted">(optional)</span>@endunless
            </label>
            <input id="{{ $id }}" name="{{ $name }}" value="{{ old($name, $saved[$field] ?? null) }}" maxlength="100"
                   pattern="{{ \App\Rules\PersonName::PATTERN }}" data-person-name="{{ \App\Rules\PersonName::MESSAGE }}"
                   autocomplete="{{ $prefix ? 'off' : $autocomplete }}" @required($required) @if ($autofocus && $loop->first) autofocus @endif
                   @if ($disabled) :disabled="{{ $disabled }}" @endif
                   @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                   class="field-input @error($name) field-input-error @enderror">
            @error($name)<p id="{{ $id }}-error" class="field-error">{{ $message }}</p>@enderror
            <p class="field-error" data-person-name-error hidden aria-live="polite"></p>
        </div>
    @endforeach

    @if ($suffix)
        @php $name = $prefix.'suffix'; $id = $idPrefix.'suffix'; @endphp
        <div>
            <label for="{{ $id }}" class="field-label">Suffix <span class="font-normal text-muted">(optional)</span></label>
            <select id="{{ $id }}" name="{{ $name }}" autocomplete="{{ $prefix ? 'off' : 'honorific-suffix' }}"
                    @if ($disabled) :disabled="{{ $disabled }}" @endif
                    @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                    class="field-input @error($name) field-input-error @enderror">
                <option value="">None</option>
                @foreach (\App\Models\User::SUFFIXES as $option)
                    <option @selected(old($name, $saved['suffix'] ?? null) === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @error($name)<p id="{{ $id }}-error" class="field-error">{{ $message }}</p>@enderror
        </div>
    @endif
</div>
