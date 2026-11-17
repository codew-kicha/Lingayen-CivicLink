{{--
    What a chart says, in one plain sentence (pre-final defense panel: not just numbers and totals).
    The sentence comes from ReportData::insights(), computed from the same figures the chart draws.
--}}
<p {{ $attributes->merge(['class' => 'mt-3 flex items-start gap-2 rounded-md bg-canvas px-3 py-2 text-sm text-ink']) }}>
    <x-phosphor-lightbulb class="mt-hair h-4 w-4 shrink-0 text-accent-600" aria-hidden="true" />
    <span><span class="sr-only">What this shows: </span>{{ $slot }}</span>
</p>
