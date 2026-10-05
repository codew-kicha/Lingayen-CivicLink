@props(['rows', 'label'])

{{--
    Horizontal bars split into self-initiated (dark) and LGU-organized (light).
    $rows: list of ['label' => string, 'independent' => int, 'lgu' => int, 'ratio' => ?float].
    CSS only, like x-bar-chart, so it needs no JavaScript.
--}}
@php
    $rows = collect($rows);
    $max = max($rows->max(fn ($r) => $r['independent'] + $r['lgu']) ?: 0, 1);
@endphp

<table class="w-full">
    <caption class="sr-only">{{ $label }}</caption>
    <thead class="sr-only">
        <tr><th scope="col">Name</th><th scope="col">Self-initiated and LGU-organized</th><th scope="col">Self-initiated share</th></tr>
    </thead>
    <tbody>
        @foreach ($rows as $row)
            <tr>
                <th scope="row" class="w-44 py-1.5 pr-3 text-left text-sm font-normal text-ink">{{ $row['label'] }}</th>
                <td class="py-1.5">
                    <div class="flex h-4 w-full bg-surface" title="{{ $row['independent'] }} self-initiated, {{ $row['lgu'] }} LGU-organized">
                        <div class="h-4 bg-navy-600" style="width: {{ round($row['independent'] / $max * 100, 1) }}%"></div>
                        <div class="h-4 bg-navy-200" style="width: {{ round($row['lgu'] / $max * 100, 1) }}%"></div>
                    </div>
                    <span class="sr-only">{{ $row['independent'] }} self-initiated, {{ $row['lgu'] }} LGU-organized</span>
                </td>
                <td class="w-20 py-1.5 pl-3 text-right font-mono text-sm tabular-nums text-muted">
                    {{ $row['ratio'] === null ? 'n/a' : round($row['ratio'] * 100).'%' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
