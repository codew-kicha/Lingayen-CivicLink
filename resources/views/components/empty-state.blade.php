@props(['title', 'icon' => 'tray'])

{{-- Shared empty state for tables and lists: icon, what's missing, and optionally what to do next. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col items-center px-4 py-12 text-center']) }}>
    <x-dynamic-component :component="'phosphor-'.$icon" class="h-8 w-8 text-muted" aria-hidden="true" />
    <p class="mt-3 font-semibold text-ink">{{ $title }}</p>
    @if (trim($slot))
        <div class="mt-1 max-w-[48ch] text-sm text-muted">{{ $slot }}</div>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
