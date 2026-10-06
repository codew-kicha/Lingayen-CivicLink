@props(['type'])

{{-- Folded into the organization cell instead of a Type column: "New" is the default, so only renewals are tagged. --}}
@if ($type === 'renewal')
    <span class="badge-neutral ml-2 align-middle">Renewal</span>
@endif
