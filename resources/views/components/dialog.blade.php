@props([
    'name',
    'title' => null,
    'subtitle' => null,
    'width' => 'md',     // sm 560px (short forms), md 640px (review), lg 720px (long text)
    'open' => false,     // true to reopen after a validation error
])

{{--
    The one dialog (docs/DESIGN_SYSTEM.md §3). Centred in the content area to the right of the
    sidebar; the overlay still covers the whole window. Header (title and ×) and footer are pinned
    and only the body scrolls; at most 80% of the window tall, full screen below sm.
    Esc closes it. A click outside closes it only until something is typed. Focus is trapped and
    returns to the trigger on close. Open with $dispatch('open-dialog', '{name}').

    The slot holds the body and footer, usually inside the form:
        <form class="dialog-form"> <div class="dialog-body">…</div> <div class="dialog-footer">…</div> </form>
    A `header` slot replaces the title block (the review dialog adds previous/next there).
--}}
@php
    $widths = ['sm' => 'sm:max-w-[35rem]', 'md' => 'sm:max-w-[40rem]', 'lg' => 'sm:max-w-[45rem]'];
    $titleId = 'dialog-'.$name.'-title';
@endphp

<div x-data="dialog(@js($name), @js((bool) $open))" x-show="open" x-cloak
     x-transition:enter="transition duration-base ease-out-strong" x-transition:enter-start="opacity-0"
     x-transition:leave="transition duration-fast ease-out-strong" x-transition:leave-end="opacity-0"
     @keydown.escape.window="if (open) close()" @click.self="clickOutside()"
     {{ $attributes->merge(['class' => 'dialog-root']) }}>
    {{-- Clicks on the overlay land on .dialog-root (the trap makes the overlay inert). --}}
    <div class="dialog-overlay" aria-hidden="true"></div>

    <div role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
         x-trap.noscroll.inert="open" @input="dirty = true" @change="dirty = true"
         class="dialog-panel {{ $widths[$width] }}">
        <div class="dialog-header">
            @isset($header)
                {{ $header }}
            @else
                <div class="min-w-0 flex-1">
                    <h2 id="{{ $titleId }}" class="card-title">{{ $title }}</h2>
                    @if ($subtitle)
                        <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
                    @endif
                </div>
            @endisset
            <button type="button" class="dialog-close" @click="close()">
                <x-phosphor-x class="h-5 w-5" aria-hidden="true" />
                <span class="sr-only">Close</span>
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
