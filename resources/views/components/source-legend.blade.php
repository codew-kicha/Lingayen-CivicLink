{{-- Legend for every self-initiated vs. LGU-organized chart. --}}
<p {{ $attributes->merge(['class' => 'flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted']) }}>
    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 bg-chart-self" aria-hidden="true"></span>Self-initiated</span>
    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 bg-chart-lgu" aria-hidden="true"></span>LGU-organized</span>
    @if ($attributes->has('with-unknown'))
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 bg-chart-unknown" aria-hidden="true"></span>Not recorded</span>
    @endif
</p>
