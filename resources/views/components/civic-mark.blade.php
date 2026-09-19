@props(['class' => 'h-9 w-9'])

{{-- Placeholder badge. The official PESO Lingayen and LGU seals must be supplied by the client;
     Municipal Ordinance No. 79, s-2019 governs use of the municipal seal, so it is not
     approximated here (PRD §11). --}}
<span {{ $attributes->merge(['class' => $class . ' inline-flex shrink-0 items-center justify-center rounded-sm border border-amber-500/40 bg-navy-800 font-semibold tracking-tight text-amber-400']) }}
      aria-hidden="true">
    LC
</span>
