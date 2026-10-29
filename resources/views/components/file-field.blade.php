{{--
    Wraps one file input (the slot) with an × that clears the chosen file, shown only while a file is
    chosen. Clearing fires `change` on the input, so anything listening (the requirement pre-check)
    resets too. Give the input the `field-file` class; its right padding leaves room for the button.
--}}
<div x-data="fileField" @change="has = $event.target.files?.length > 0" class="relative">
    {{ $slot }}
    <button type="button" x-show="has" x-cloak @click="clear()" title="Remove file"
            class="absolute right-2 top-1/2 inline-flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-sm text-muted
                   transition-colors duration-fast ease-out-strong hover:bg-danger-subtle hover:text-danger focus-visible:outline-focus">
        <x-phosphor-x class="h-4 w-4" aria-hidden="true" />
        <span class="sr-only">Remove the chosen file</span>
    </button>
</div>
