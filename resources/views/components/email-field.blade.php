@props([
    'name' => 'email',
    'id' => null,
    'label' => 'Email',
    'value' => null,
    'autocomplete' => 'email',
    'hint' => null,
    'autofocus' => false,
])

{{--
    Email input that helps without limiting (pre-final defense panel). While no "@" is typed, a faint
    "@gmail.com" completes the address (Tab or → accepts it). After "@", common domains are offered as
    you type, but any domain is accepted. On leaving the field, a likely typo (gmial.com, yaho.com...)
    gets a one-click "Did you mean ...?". Logic: Alpine `emailAssist` in resources/js/app.js.
--}}
@php
    $id ??= $name;
    $error = $errors->first($name);
    $describedBy = collect([$hint ? "{$id}-hint" : null, "{$id}-suggest", $error ? "{$id}-error" : null])->filter()->join(' ');
@endphp

<div x-data="emailAssist(@js(old($name, $value) ?? ''))" @click.outside="open = false">
    <label for="{{ $id }}" class="field-label">{{ $label }}</label>
    <div class="relative rounded-sm bg-paper">
        {{-- The completion: the typed text (invisible) followed by the suggested rest, under the input. --}}
        <div aria-hidden="true" x-show="ghost" x-cloak
             class="pointer-events-none absolute inset-0 flex items-center overflow-hidden whitespace-pre border border-transparent px-3 text-base">
            <span class="invisible" x-text="value"></span><span class="text-placeholder" x-text="ghost"></span>
        </div>
        <input id="{{ $id }}" name="{{ $name }}" type="email" x-ref="input" x-model="value"
               value="{{ old($name, $value) }}" required autocomplete="{{ $autocomplete }}" maxlength="255"
               autocapitalize="none" spellcheck="false" placeholder="name@gmail.com" @if ($autofocus) autofocus @endif
               role="combobox" aria-autocomplete="list" aria-controls="{{ $id }}-domains" :aria-expanded="open.toString()"
               :aria-activedescendant="open && options.length ? '{{ $id }}-domain-' + active : null"
               @input="update()" @keydown="key($event)" @blur="check()" @focus="update()"
               aria-describedby="{{ $describedBy }}" @if ($error) aria-invalid="true" @endif
               class="field-input relative bg-transparent @if ($error) field-input-error @endif">

        <ul id="{{ $id }}-domains" role="listbox" x-show="open && options.length" x-cloak aria-label="Email providers"
            class="absolute inset-x-0 top-full z-dropdown mt-1 overflow-hidden rounded-md border border-line bg-paper py-1 shadow-overlay">
            <template x-for="(domain, i) in options" :key="domain">
                <li :id="'{{ $id }}-domain-' + i" role="option" :aria-selected="(i === active).toString()"
                    @mousedown.prevent="pick(domain)" @mouseenter="active = i"
                    class="cursor-pointer px-3 py-2 text-sm text-ink" :class="i === active && 'bg-primary-subtle'">
                    <span x-text="local"></span><span class="font-semibold" x-text="'@' + domain"></span>
                </li>
            </template>
        </ul>
    </div>

    @if ($hint)
        <p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>
    @endif
    <p id="{{ $id }}-suggest" aria-live="polite" class="text-sm">
        <template x-if="suggestion">
            <span class="mt-1 flex flex-wrap items-center gap-x-2 text-warning">
                Did you mean <b x-text="suggestion"></b>?
                <button type="button" class="font-semibold text-primary underline underline-offset-2" @click="useSuggestion()">Use it</button>
            </span>
        </template>
    </p>
    @if ($error)
        <p id="{{ $id }}-error" class="field-error">{{ $error }}</p>
    @endif
</div>
