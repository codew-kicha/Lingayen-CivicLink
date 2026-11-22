{{-- One step's body for <x-process-explorer>: what happens, the facts that matter, and its markers. --}}
<p @class(['leading-relaxed text-muted', 'text-lg' => $large ?? false])>{{ $step['detail'] }}</p>
@if (! empty($step['who']))
    <p class="mt-4 inline-flex items-center gap-2 rounded-sm bg-canvas px-2 py-1 text-sm text-ink">
        <x-phosphor-user-circle class="h-4 w-4 shrink-0 text-brand-600" aria-hidden="true" />
        <span><span class="text-muted">Who acts:</span> <span class="font-semibold">{{ $step['who'] }}</span></span>
    </p>
@endif
@if (! empty($step['points']))
    <ul class="mt-4 space-y-2">
        @foreach ($step['points'] as $point)
            <li class="flex gap-3 text-ink">
                <x-phosphor-check-circle class="mt-hair h-5 w-5 shrink-0 text-brand-600" aria-hidden="true" />{{ $point }}
            </li>
        @endforeach
    </ul>
@endif
{{-- The outcome chips summarise; skip them where the points above already explain each outcome. --}}
@if (! empty($step['outcomes']) && empty($step['points']))
    <ul class="mt-4 flex flex-wrap items-center gap-2 text-sm font-semibold text-brand-800" aria-label="Possible outcomes">
        @foreach ($step['outcomes'] as $outcome)
            <li class="rounded-sm border border-brand-200 bg-brand-50 px-2 py-1">{{ $outcome }}</li>
        @endforeach
    </ul>
@endif
@if (! empty($step['note']))
    <p class="mt-4 inline-flex items-center gap-2 rounded-sm bg-accent-100 px-2 py-1 text-sm font-semibold text-brand-900">
        <x-phosphor-info class="h-4 w-4 shrink-0" aria-hidden="true" />{{ $step['note'] }}
    </p>
@endif
