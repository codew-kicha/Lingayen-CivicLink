@props(['series', 'valueKey' => 'total', 'secondKey' => null, 'label' => 'trend'])

@php
    $rows = collect($series);
    $max = max(
        $rows->max($valueKey) ?: 0,
        $secondKey ? ($rows->max($secondKey) ?: 0) : 0,
        1,
    );
@endphp

<div>
    <div class="flex items-end gap-1.5" style="height: 9rem;" role="img"
         aria-label="{{ $label }} over the last {{ $rows->count() }} months">
        @foreach ($rows as $row)
            <div class="flex h-full flex-1 items-end justify-center gap-0.5">
                @if ($secondKey)
                    <div class="w-1/2 bg-navy-200" style="height: {{ round(($row[$secondKey] / $max) * 100, 1) }}%"
                         title="{{ $row['label'] }}: {{ $row[$secondKey] }}"></div>
                @endif
                <div class="{{ $secondKey ? 'w-1/2' : 'w-full' }} bg-navy-500"
                     style="height: {{ round(($row[$valueKey] / $max) * 100, 1) }}%"
                     title="{{ $row['label'] }}: {{ $row[$valueKey] }}"></div>
            </div>
        @endforeach
    </div>

    <div class="mt-2 flex gap-1.5 border-t border-line pt-2">
        @foreach ($rows as $row)
            <span class="flex-1 text-center font-mono text-[0.65rem] tabular-nums text-muted">
                {{ Str::before($row['label'], ' ') }}
            </span>
        @endforeach
    </div>
</div>
