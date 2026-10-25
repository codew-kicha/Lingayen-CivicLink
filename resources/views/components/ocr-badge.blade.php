@props(['document', 'compact' => false])

{{--
    Advisory OCR pre-check result for PESO's reviewer. Never a reason on its own to reject.
    With `compact`, a match shows only its badge (the words found go in a tooltip); results that need
    a look (Check content, Unreadable scan) always keep their one-line explanation.
--}}
@php
    $details = $document->ocr_details;
    [$class, $label] = match ($document->ocr_status) {
        'matched' => ['badge-success', 'Looks right'],
        'mismatch' => ['badge-warning', 'Check content'],
        'unreadable' => ['badge-neutral', 'Unreadable scan'],
        default => ['badge-neutral', 'Not checked'],
    };

    $explanation = null;
    if ($details) {
        $explanation = match (true) {
            $document->ocr_status === 'unreadable' => 'Too little text could be read. It may be handwritten or blurry.',
            (bool) $details['found'] => 'Found: '.implode(', ', $details['found']).'.',
            default => 'None of the expected words were found.',
        }.($details['organization_named'] ? ' Names the organization.' : '');
    }
    $quiet = $compact && $document->ocr_status === 'matched';
@endphp

<span class="{{ $class }}" @if ($quiet && $explanation) title="{{ $explanation }}" @endif>{{ $label }}</span>
@if ($explanation)
    @if ($quiet)
        <span class="sr-only">{{ $explanation }}</span>
    @else
        <p class="mt-1 max-w-[16rem] text-xs text-muted">{{ $explanation }}</p>
    @endif
@endif
