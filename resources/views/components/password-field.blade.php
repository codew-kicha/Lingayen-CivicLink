@props([
    'name' => 'password',
    'id' => null,
    'label' => 'Password',
    'autocomplete' => 'current-password',
    'checklist' => false,
    'bag' => 'default',
])

@php
    $id ??= $name;
    $error = $errors->getBag($bag)->first($name);
    $describedBy = trim(($checklist ? "{$id}-rules " : '').($error ? "{$id}-error" : ''));
@endphp

{{-- Show/hide toggle, plus an optional live checklist mirroring Password::defaults() in AppServiceProvider. --}}
<div x-data="{ show: false, value: '' }">
    <label for="{{ $id }}" class="field-label">{{ $label }}</label>
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" type="password" :type="show ? 'text' : 'password'"
               x-model="value" required autocomplete="{{ $autocomplete }}"
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
               @if ($error) aria-invalid="true" @endif
               @class(['field-input pr-20', 'field-input-error' => $error])>
        <button type="button" x-cloak @click="show = ! show" :aria-pressed="show.toString()"
                class="absolute inset-y-0 right-0 flex min-w-16 items-center justify-center rounded-r-sm px-3 text-sm
                       font-semibold text-navy-700 hover:bg-navy-50 focus-visible:outline-navy-600">
            <span x-text="show ? 'Hide' : 'Show'">Show</span>
            <span class="sr-only"> password</span>
        </button>
    </div>

    @if ($checklist)
        <ul id="{{ $id }}-rules" class="mt-2 grid gap-x-4 gap-y-1 text-sm sm:grid-cols-2">
            @foreach ([
                ['At least 12 characters', 'value.length >= 12'],
                ['An uppercase and a lowercase letter', '/[a-z]/.test(value) && /[A-Z]/.test(value)'],
                ['A number', '/[0-9]/.test(value)'],
                ['A symbol, such as - ! or #', '/[^A-Za-z0-9]/.test(value)'],
            ] as [$rule, $test])
                <li class="flex items-center gap-1.5 text-muted" :class="{ '!text-success-600': {{ $test }} }">
                    <x-phosphor-check-circle class="h-4 w-4 shrink-0" x-show="{{ $test }}" aria-hidden="true" />
                    <x-phosphor-circle class="h-4 w-4 shrink-0" x-show="! ({{ $test }})" aria-hidden="true" />
                    <span>{{ $rule }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="field-error">{{ $error }}</p>
    @endif
</div>
