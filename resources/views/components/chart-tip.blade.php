{{--
    The hover and focus read-out for an analytics chart (Alpine `chart`). It sits above the mark that
    was pointed at; values lead, names follow, and a short line in the series colour keys each row.
    Screen readers get the same figures from each mark's aria-label, so this is hidden from them.
--}}
<div x-show="tip" x-cloak aria-hidden="true"
     class="pointer-events-none absolute z-dropdown w-max max-w-56 -translate-x-1/2 -translate-y-full rounded-md border border-line bg-paper px-3 py-2 text-xs shadow-overlay"
     :style="tip && `left: ${tip.x}px; top: ${tip.y - 8}px`">
    <p class="font-semibold text-ink" x-text="tip?.title"></p>
    <template x-for="row in tip?.rows ?? []" :key="row.label">
        <p class="mt-1 flex items-center gap-2">
            <span class="h-0.5 w-3 shrink-0" :class="row.key || 'invisible'"></span>
            <span class="font-semibold tabular-nums text-ink" x-text="row.value"></span>
            <span class="text-muted" x-text="row.label"></span>
        </p>
    </template>
</div>
