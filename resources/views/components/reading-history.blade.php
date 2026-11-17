@props(['readings'])

{{--
    What happened at each Sangguniang Bayan reading (pre-final defense panel), oldest first: the
    reading, the session date, the outcome and the Desk Office's remarks. Shown to the CSO on their
    application and to the office on the review page; the same remarks go out in each notification.
--}}
@if ($readings->isNotEmpty())
    <ol {{ $attributes->merge(['class' => 'space-y-3']) }} aria-label="Readings so far">
        @foreach ($readings as $reading)
            @php
                $badge = match ($reading->outcome) {
                    'passed' => 'badge-success',
                    'deferred' => 'badge-warning',
                    default => 'badge-danger',
                };
            @endphp
            <li class="rounded-md border border-line bg-paper px-4 py-3 text-sm">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="font-semibold text-ink">{{ $reading->stageLabel() }}</span>
                    <span class="{{ $badge }}">{{ $reading->outcomeLabel() }}</span>
                    <span class="ml-auto text-xs tabular-nums text-muted">{{ $reading->held_on->format('d M Y') }}</span>
                </div>
                @if ($reading->remarks)
                    <p class="mt-2 whitespace-pre-line text-ink">{{ $reading->remarks }}</p>
                @endif
                <p class="mt-1 text-xs text-muted">{{ $reading->nextStep() }}</p>
            </li>
        @endforeach
    </ol>
@endif
