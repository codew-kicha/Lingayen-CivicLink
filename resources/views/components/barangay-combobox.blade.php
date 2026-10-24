@props([
    'id' => 'barangay',
    'name' => 'barangay',
    'value' => '',
    'error' => null,      // message to show, or null
    'marked' => true,     // red required asterisk (forms with a "* Required" legend)
])

{{--
    Searchable Barangay field (the `combobox` Alpine component in resources/js/app.js). The visible
    input is the form field itself, so the server still validates the value against config('barangays');
    picking from the list fills in the exact spelling. Used by the admin Register dialog and public sign-up.
--}}
<div x-data="combobox(@js(array_values(config('barangays'))), @js((string) $value))" class="relative" @focusout="settle()">
    <label for="{{ $id }}" @class(['field-label', 'field-required' => $marked])>Barangay</label>
    <input id="{{ $id }}" name="{{ $name }}" x-ref="input" x-model="query" required autocomplete="off"
           role="combobox" aria-autocomplete="list" aria-controls="{{ $id }}-list"
           :aria-expanded="expanded.toString()"
           :aria-activedescendant="expanded && matches[active] ? '{{ $id }}-' + active : null"
           @focus="show()" @click="show()" @input="expanded = true; active = 0"
           @keydown.down.prevent="move(1)" @keydown.up.prevent="move(-1)" @keydown.enter="chooseActive($event)"
           @keydown.escape="if (expanded) { expanded = false; $event.stopPropagation() }"
           placeholder="Type to search"
           @class(['field-input pr-12', 'field-input-error' => $error])
           @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
    <x-phosphor-caret-up-down class="pointer-events-none absolute right-4 top-11 h-5 w-5 text-muted" aria-hidden="true" />
    <ul id="{{ $id }}-list" role="listbox" aria-label="Barangays" x-ref="listbox" x-show="expanded" x-cloak class="listbox">
        <template x-for="(option, i) in matches" :key="option">
            <li role="option" :id="'{{ $id }}-' + i" :aria-selected="(option === query).toString()"
                :data-active="i === active ? '' : null" @mousedown.prevent="choose(option)" @mousemove="active = i"
                class="listbox-option">
                <span x-text="option"></span>
                <x-phosphor-check x-show="option === query" class="ml-auto h-4 w-4 text-primary" aria-hidden="true" />
            </li>
        </template>
        <li x-show="! matches.length" class="px-3 py-2 text-sm text-muted">No barangay matches that.</li>
    </ul>
    @if ($error)<p id="{{ $id }}-error" class="field-error">{{ $error }}</p>@endif
</div>
