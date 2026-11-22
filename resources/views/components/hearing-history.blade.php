@props(['hearings'])

{{--
    The Sangguniang Bayan committee hearings on an application, oldest first: when, which committee,
    the outcome, and the Desk Office's remarks (the same remarks each notification carried). Shown to
    the organization on its application and to the office on the review page.
--}}
@if ($hearings->isNotEmpty())
    <ol {{ $attributes->merge(['class' => 'space-y-3']) }} aria-label="Committee hearings">
        @foreach ($hearings as $hearing)
            @php
                $badge = match ($hearing->outcome) {
                    'favorable' => 'badge-success',
                    'deferred' => 'badge-warning',
                    default => 'badge-danger',
                };
            @endphp
            <li class="rounded-md border border-line bg-paper px-4 py-3 text-sm">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <span class="font-semibold text-ink">{{ $hearing->committee ?: 'Committee hearing' }}</span>
                    <span class="{{ $badge }}">{{ $hearing->outcomeLabel() }}</span>
                    <span class="ml-auto text-xs tabular-nums text-muted">{{ $hearing->held_on->format('d M Y') }}</span>
                </div>
                @if ($hearing->remarks)
                    <p class="mt-2 whitespace-pre-line text-ink">{{ $hearing->remarks }}</p>
                @endif
                {{-- What the latest outcome means; the older ones are history. --}}
                @if ($loop->last)
                    <p class="mt-1 text-xs text-muted">{{ $hearing->meaning() }}</p>
                @endif
            </li>
        @endforeach
    </ol>
@endif
