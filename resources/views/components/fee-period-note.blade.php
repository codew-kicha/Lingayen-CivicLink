{{--
    Whether filing is free right now (client, Nov 2026): during a free period opened by a DILG
    memorandum there is no fee and accreditation lasts three years; outside it the fee applies and
    accreditation lasts one year. Shown where an organization decides to file or renew. Nothing is
    shown outside a period unless `always` is set.
--}}
@props(['always' => false])
@php
    $open = \App\Models\FeeFreePeriod::open();
    $upcoming = $open ? null : \App\Models\FeeFreePeriod::upcoming();
@endphp

@if ($open)
    <p {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-md border border-success/40 bg-success-subtle px-3 py-2 text-sm text-ink']) }}>
        <x-phosphor-seal-check class="mt-hair h-4 w-4 shrink-0 text-success" aria-hidden="true" />
        <span><span class="font-semibold">Filing is free until {{ $open->ends_on->format('j F Y') }}</span> under {{ $open->memorandum }}.
            Accreditation filed now runs for {{ config('office.validity_years.free') }} years.</span>
    </p>
@elseif ($upcoming)
    <p {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-md border border-line bg-canvas px-3 py-2 text-sm text-ink']) }}>
        <x-phosphor-calendar-check class="mt-hair h-4 w-4 shrink-0 text-brand-600" aria-hidden="true" />
        <span><span class="font-semibold">Free filing opens {{ $upcoming->starts_on->format('j F Y') }}</span> under {{ $upcoming->memorandum }},
            until {{ $upcoming->ends_on->format('j F Y') }}.</span>
    </p>
@elseif ($always)
    <p {{ $attributes->merge(['class' => 'flex items-start gap-2 text-sm text-muted']) }}>
        <x-phosphor-info class="mt-hair h-4 w-4 shrink-0" aria-hidden="true" />
        <span>No free filing period is open. The fee applies, and accreditation runs for {{ config('office.validity_years.paid') }} year.</span>
    </p>
@endif
