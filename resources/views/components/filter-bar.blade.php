@props([
    'chips',                 // [value => label]; '' is "All"
    'param' => 'status',     // query key the chips set
    'search' => 'Search',    // accessible label and placeholder for the search box
    'counts' => [],          // optional [value => number] shown after a chip's label
])

{{--
    Filter bar above a list (docs/DESIGN_SYSTEM.md §3): a search box and one-click filter chips.
    Chips are links that keep the search; searching keeps the chosen chip. Either returns to page 1.
--}}
@php
    $current = (string) request($param, '');
    $current = array_key_exists($current, $chips) ? $current : array_key_first($chips);
    $query = request()->string('q')->trim()->value();
@endphp

<div {{ $attributes->merge(['class' => 'mb-4 flex flex-wrap items-center gap-3']) }}>
    <form method="GET" role="search" class="relative w-full sm:w-80">
        <label for="list-search" class="sr-only">{{ $search }}</label>
        <x-phosphor-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-muted" aria-hidden="true" />
        <input type="search" id="list-search" name="q" value="{{ $query }}" placeholder="{{ $search }}" class="search-input">
        @if ($current !== '')
            <input type="hidden" name="{{ $param }}" value="{{ $current }}">
        @endif
    </form>

    <nav aria-label="Filter" class="flex flex-wrap gap-2">
        @foreach ($chips as $value => $label)
            <a href="{{ url()->current().(($link = http_build_query(array_filter([$param => $value, 'q' => $query], 'strlen'))) ? '?'.$link : '') }}"
               @if ((string) $value === $current) aria-current="page" @endif
               class="filter-chip">
                {{ $label }}
                @if (isset($counts[$value]))
                    <span class="tabular-nums text-muted">{{ number_format($counts[$value]) }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 sm:ml-auto">{{ $actions }}</div>
    @endisset
</div>
