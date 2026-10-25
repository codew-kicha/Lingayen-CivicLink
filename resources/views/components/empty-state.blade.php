@props(['title' => null, 'icon' => 'tray', 'filtered' => false, 'clearUrl' => null])

{{--
    Shared empty state (no illustrations). With `filtered`, it says the filters matched nothing and
    offers Clear filters; otherwise it says nothing exists yet, using `title` and the slot.
--}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-4 py-12 text-center']) }}>
    <x-dynamic-component :component="'phosphor-'.($filtered ? 'funnel' : $icon)" class="h-8 w-8 text-muted" aria-hidden="true" />
    @if ($filtered)
        <p class="mt-3 font-semibold text-ink">No results for these filters</p>
        <p class="mt-1 max-w-[48ch] text-sm text-muted">Try a broader search, or clear the filters to see everything.</p>
        @if ($clearUrl)
            <a href="{{ $clearUrl }}" class="btn-secondary mt-4">Clear filters</a>
        @endif
    @else
        <p class="mt-3 font-semibold text-ink">{{ $title }}</p>
        @if (trim($slot))
            <div class="mt-1 max-w-[48ch] text-sm text-muted">{{ $slot }}</div>
        @endif
        @isset($action)
            <div class="mt-4">{{ $action }}</div>
        @endisset
    @endif
</div>
