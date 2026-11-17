@props(['independent', 'lgu', 'unknown' => 0, 'label' => 'Activities by organizer'])

{{--
    The self-initiated / LGU-organized split as one 100% bar (pre-final defense panel: see each part,
    not only the overall share). Each colour carries its own percentage; clicking a colour, or its
    legend entry, shows that part on its own ("LGU-organized: 40 of 90 = 44%"). Percentages are of
    the activities whose organizer was recorded (config('metrics.self_share')); the rest are counted
    below the bar. The two percentages always add up to 100.
--}}
@php
    $known = $independent + $lgu;
    $shares = \App\Services\ReportData::shares($independent, $lgu);
    $parts = [
        'independent' => ['Self-initiated', $independent, 'bg-chart-self', 'text-on-primary'],
        'lgu' => ['LGU-organized', $lgu, 'bg-chart-lgu', 'text-ink'],
    ];
@endphp

<div x-data="{ part: null }" {{ $attributes }}>
    @if ($known === 0)
        <p class="text-sm text-muted">No activities with a recorded organizer{{ $unknown ? " ({$unknown} without one)" : '' }}.</p>
    @else
        <div class="flex h-8 w-full gap-hair overflow-hidden rounded-sm bg-canvas" role="group" aria-label="{{ $label }}">
            @foreach ($parts as $key => [$name, $count, $bg, $fg])
                @continue($count === 0)
                <button type="button" @click="part = part === '{{ $key }}' ? null : '{{ $key }}'" :aria-pressed="(part === '{{ $key }}').toString()"
                        aria-label="{{ $name }}: {{ $count }} of {{ $known }} activities, {{ $shares[$key] }}%"
                        class="flex h-full min-w-0 items-center justify-center overflow-hidden text-xs font-semibold tabular-nums transition-opacity duration-fast focus-visible:outline-focus {{ $bg }} {{ $fg }}"
                        :class="part && part !== '{{ $key }}' && 'opacity-40'"
                        style="flex: {{ $count }} 1 0%">
                    @if ($shares[$key] >= 12){{ $shares[$key] }}%@endif
                </button>
            @endforeach
        </div>

        <ul class="mt-2 flex flex-wrap gap-2 text-xs">
            @foreach ($parts as $key => [$name, $count, $bg])
                <li>
                    <button type="button" @click="part = part === '{{ $key }}' ? null : '{{ $key }}'" :aria-pressed="(part === '{{ $key }}').toString()"
                            class="inline-flex items-center gap-tight rounded-sm border px-2 py-1 text-ink transition-colors duration-fast hover:border-primary/40 focus-visible:outline-focus"
                            :class="part === '{{ $key }}' ? 'border-primary bg-primary-subtle' : 'border-line bg-paper'">
                        <span class="h-2.5 w-2.5 {{ $bg }}" aria-hidden="true"></span>
                        {{ $name }} <span class="font-semibold tabular-nums">{{ $shares[$key] }}%</span>
                        <span class="tabular-nums text-muted">({{ $count }})</span>
                    </button>
                </li>
            @endforeach
        </ul>

        @foreach ($parts as $key => [$name, $count])
            <p x-show="part === '{{ $key }}'" x-cloak class="mt-2 text-sm text-ink" aria-live="polite">
                {{ $name }}: {{ $count }} of {{ $known }} {{ Str::plural('activity', $known) }} with a recorded organizer =
                <span class="font-semibold tabular-nums">{{ $shares[$key] }}%</span>
            </p>
        @endforeach

        @if ($unknown)
            <p class="mt-1 text-xs text-muted">{{ $unknown }} more had no organizer recorded and are left out of the percentages.</p>
        @endif
    @endif
</div>
