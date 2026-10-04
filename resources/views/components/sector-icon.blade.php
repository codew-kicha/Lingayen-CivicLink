@props(['sector'])

@php
    // One deliberately chosen Phosphor icon per real sector (DESIGN_SYSTEM.md §0). Phosphor has no
    // tricycle, cane, or woven motif, so TODA, Senior Citizen, and KALIPI use the nearest match.
    $icon = match ($sector) {
        'Health' => 'first-aid',
        'Cooperative' => 'handshake',
        'Farmers and Fisherfolks' => 'fish',
        "KALIPI (Women's)" => 'hand-heart',
        'OFW' => 'airplane-tilt',
        'Pedicab Drivers' => 'person-simple-bike',
        'Rural Improvement Club' => 'plant',
        'Senior Citizen' => 'person-simple-walk',
        'TODA' => 'moped',
        default => 'users-three',
    };
@endphp

<x-dynamic-component :component="'phosphor-'.$icon.'-duotone'" aria-hidden="true" {{ $attributes }} />