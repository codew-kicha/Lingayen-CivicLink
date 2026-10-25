@props([
    'id',
    'error' => null,
    'purpose' => 'It confirms it is you before this change.',
    'autofocus' => false,   // the dialog opens on this field
])

{{--
    "Your admin password" re-check inside a dialog (validated with the current_password rule on
    `admin_password`). The hidden username tells password managers whose password this is, so they
    don't fill in a representative's saved one; focusing selects anything already in the field, so
    typing replaces it instead of adding to it.
--}}
<div>
    <input type="text" name="_admin_username" value="{{ auth()->user()->email }}" autocomplete="username" hidden>
    <label for="{{ $id }}" class="field-label field-required">Your admin password</label>
    <input id="{{ $id }}" name="admin_password" type="password" required autocomplete="current-password"
           x-ref="adminPassword" @focus="$el.select()" @if ($autofocus) autofocus @endif
           aria-describedby="{{ $id }}-hint{{ $error ? ' '.$id.'-error' : '' }}"
           @class(['field-input', 'field-input-error' => $error])
           @if ($error) aria-invalid="true" @endif>
    <p id="{{ $id }}-hint" class="field-hint">
        The password you sign in with as <span class="font-semibold text-ink">{{ auth()->user()->email }}</span>. {{ $purpose }}
    </p>
    @if ($error)<p id="{{ $id }}-error" class="field-error">{{ $error }}</p>@endif
</div>
