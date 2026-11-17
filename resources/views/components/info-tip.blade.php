@props(['metric', 'label' => null])

{{--
    An ⓘ beside a figure (pre-final defense panel: every metric explains what it measures and its
    formula). Hover or focus shows it, a click or tap keeps it open, Esc or a click elsewhere closes it.
    The definition comes from config/metrics.php, shared with the PDF and the Excel workbook. It opens
    in the browser's top layer (popover), so table scrolling and card edges never clip it.
--}}
@php
    [$name, $formula, $note] = config("metrics.{$metric}");
    $id = 'tip-'.$metric.'-'.Str::random(5);
@endphp

<span x-data="infoTip" class="relative inline-flex align-middle" @keydown.escape="hide(true)" @click.outside="hide(true)" @scroll.window="hide(true)">
    <button type="button" x-ref="button" @mouseenter="show()" @mouseleave="hide()" @focus="show()" @blur="hide()" @click.stop="pin()"
            :aria-expanded="open.toString()" aria-controls="{{ $id }}"
            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-muted hover:text-primary focus-visible:outline-focus">
        <x-phosphor-info class="h-4 w-4" aria-hidden="true" />
        <span class="sr-only">How "{{ $label ?? $name }}" is counted</span>
    </button>
    <span id="{{ $id }}" x-ref="tip" popover="manual" role="tooltip" x-show="open" x-cloak
          class="fixed z-dropdown m-0 block w-72 rounded-md border border-line bg-paper p-3 text-left whitespace-normal text-xs font-normal normal-case tracking-normal text-ink shadow-overlay">
        <span class="block font-semibold">{{ $name }}</span>
        <span class="mt-1 block rounded-sm bg-canvas px-2 py-1 tabular-nums">{{ $formula }}</span>
        @if ($note)
            <span class="mt-1 block text-muted">{{ $note }}</span>
        @endif
    </span>
</span>
