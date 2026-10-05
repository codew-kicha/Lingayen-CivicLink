@props(['series', 'label', 'height' => '9rem'])

{{--
    Monthly columns stacked by source: self-initiated (dark), LGU-organized (light), not recorded (grey).
    $series rows: ['label' => 'Mon YYYY', 'independent' => int, 'lgu' => int, 'total' => int].
--}}
@php
    $rows = collect($series)->map(fn ($r) => $r + ['unknown' => max($r['total'] - $r['independent'] - $r['lgu'], 0)]);
    $max = max($rows->max('total') ?: 0, 1);
    $everyOther = $rows->count() > 18;
@endphp

<div>
    <div class="flex items-end gap-1" style="height: {{ $height }};" role="img"
         aria-label="{{ $label }}: {{ $rows->map(fn ($r) => $r['label'].' '.$r['total'])->join(', ') }}">
        @foreach ($rows as $row)
            <div class="flex h-full flex-1 flex-col justify-end" title="{{ $row['label'] }}: {{ $row['independent'] }} self-initiated, {{ $row['lgu'] }} LGU-organized{{ $row['unknown'] ? ', '.$row['unknown'].' not recorded' : '' }}">
                <div class="bg-line-strong" style="height: {{ round($row['unknown'] / $max * 100, 1) }}%"></div>
                <div class="bg-navy-200" style="height: {{ round($row['lgu'] / $max * 100, 1) }}%"></div>
                <div class="bg-navy-600" style="height: {{ round($row['independent'] / $max * 100, 1) }}%"></div>
            </div>
        @endforeach
    </div>

    <div class="mt-2 flex gap-1 border-t border-line pt-2" aria-hidden="true">
        @foreach ($rows as $i => $row)
            <span class="flex-1 text-center font-mono text-[0.6rem] tabular-nums text-muted">
                {{ $everyOther && $i % 2 ? '' : Str::before($row['label'], ' ') }}
            </span>
        @endforeach
    </div>
</div>
