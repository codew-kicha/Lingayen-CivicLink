@props(['label', 'value', 'alert' => false])

{{-- One figure in a dashboard details panel: a label over a large number. --}}
<div class="rounded-md border border-line bg-canvas px-3 py-2">
    <dt class="text-xs text-muted">{{ $label }}</dt>
    <dd @class(['text-card-title font-semibold tabular-nums', 'text-warning' => $alert, 'text-ink' => ! $alert])>{{ $value }}</dd>
</div>
