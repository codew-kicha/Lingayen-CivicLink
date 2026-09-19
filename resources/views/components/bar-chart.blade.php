@props(['data', 'label' => 'value', 'formatter' => null])

@php
    // Rendered in CSS rather than a JS chart library so the same markup works in the
    // dompdf export, which cannot execute JavaScript.
    $rows = collect($data);
    $max = max($rows->max() ?: 0, 1);
@endphp

@if ($rows->isEmpty())
    <p class="py-10 text-center text-sm text-muted">No data to chart yet.</p>
@else
    <table class="w-full">
        <caption class="sr-only">{{ $label }}</caption>
        <tbody>
            @foreach ($rows as $name => $value)
                <tr>
                    <th scope="row" class="w-40 py-1.5 pr-3 text-left text-sm font-normal text-ink">{{ $name }}</th>
                    <td class="py-1.5">
                        <div class="h-4 w-full bg-surface">
                            <div class="h-4 bg-navy-500" style="width: {{ round(($value / $max) * 100, 1) }}%"></div>
                        </div>
                    </td>
                    <td class="w-16 py-1.5 pl-3 text-right font-mono text-sm tabular-nums text-muted">
                        {{ $formatter ? $formatter($value) : $value }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
