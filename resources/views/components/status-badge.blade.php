@props(['status'])

@php
    // Shared status vocabulary across all three roles (docs/DESIGN_SYSTEM.md §3).
    $map = [
        'draft' => ['Draft', 'badge-neutral'],
        'submitted' => ['Submitted', 'badge-info'],
        'under_review' => ['Under review', 'badge-warning'],
        'pending' => ['Pending verification', 'badge-warning'],
        'approved' => ['Approved', 'badge-success'],
        'verified' => ['Verified', 'badge-success'],
        'active' => ['Active', 'badge-success'],
        'published' => ['Published', 'badge-success'],
        'rejected' => ['Rejected', 'badge-danger'],
        'revoked' => ['Revoked', 'badge-danger'],
        'expired' => ['Expired', 'badge-neutral'],
    ];

    [$label, $class] = $map[$status] ?? [Str::headline($status), 'badge-neutral'];
@endphp

<span class="{{ $class }}">{{ $label }}</span>
