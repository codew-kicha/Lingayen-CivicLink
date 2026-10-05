@props(['document'])

{{-- Advisory OCR pre-check result for PESO's reviewer. Never a reason on its own to reject. --}}
@php
    $details = $document->ocr_details;
    [$class, $label] = match ($document->ocr_status) {
        'matched' => ['badge-success', 'Looks right'],
        'mismatch' => ['badge-warning', 'Check content'],
        'unreadable' => ['badge-neutral', 'Unreadable scan'],
        default => ['badge-neutral', 'Not checked'],
    };
@endphp

<span class="{{ $class }}">{{ $label }}</span>
@if ($details)
    <p class="mt-1 max-w-[16rem] text-xs text-muted">
        @if ($document->ocr_status === 'unreadable')
            Too little text could be read. It may be handwritten or blurry.
        @elseif ($details['found'])
            Found: {{ implode(', ', $details['found']) }}.
        @else
            None of the expected words were found.
        @endif
        @if ($details['organization_named'])
            Names the organization.
        @endif
    </p>
@endif
