@props([
    'paginator',
    'target' => 'page-title',               // receives focus after a page change
])

{{--
    Shared pagination (docs/DESIGN_SYSTEM.md §4): the footer bar of a table card. "Showing X-Y of N"
    and Previous / numbered pages / Next, 10 rows a page. It sticks to the bottom of the viewport
    while the table is taller than the window. Below sm only Previous, "Page X of Y" and Next show.
    Disabled ends are aria-disabled text, ellipses are plain text.
--}}
@php
    $window = \Illuminate\Pagination\UrlWindow::make($paginator);
    $elements = array_values(array_filter([
        $window['first'],
        is_array($window['slider']) ? '…' : null,
        $window['slider'],
        is_array($window['last']) ? '…' : null,
        $window['last'],
    ]));
    $href = fn (int $page) => $paginator->url($page).'#'.$target;
@endphp

@if ($paginator->total() > 0)
    <div {{ $attributes->merge(['class' => 'pagination-bar']) }}>
        <p class="tabular-nums text-muted" aria-live="polite">
            Showing {{ number_format($paginator->firstItem()) }}-{{ number_format($paginator->lastItem()) }} of {{ number_format($paginator->total()) }}
        </p>

        @if ($paginator->hasPages())
            <nav aria-label="Pagination" class="ml-auto">
                <ul class="flex items-center gap-1">
                    <li>
                        @if ($paginator->onFirstPage())
                            <span class="page-link page-link-disabled" aria-disabled="true">
                                <x-phosphor-caret-left class="h-4 w-4" aria-hidden="true" />Previous
                            </span>
                        @else
                            <a href="{{ $href($paginator->currentPage() - 1) }}" rel="prev" class="page-link">
                                <x-phosphor-caret-left class="h-4 w-4" aria-hidden="true" />Previous
                            </a>
                        @endif
                    </li>

                    <li class="px-2 tabular-nums text-muted sm:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</li>

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <li class="hidden px-1 text-muted sm:block" aria-hidden="true">{{ $element }}</li>
                        @else
                            @foreach ($element as $page => $url)
                                <li class="hidden sm:block">
                                    @if ($page === $paginator->currentPage())
                                        <span class="page-link page-link-current" aria-current="page">
                                            <span class="sr-only">Page </span>{{ $page }}
                                        </span>
                                    @else
                                        <a href="{{ $href($page) }}" class="page-link"><span class="sr-only">Page </span>{{ $page }}</a>
                                    @endif
                                </li>
                            @endforeach
                        @endif
                    @endforeach

                    <li>
                        @if ($paginator->hasMorePages())
                            <a href="{{ $href($paginator->currentPage() + 1) }}" rel="next" class="page-link">
                                Next<x-phosphor-caret-right class="h-4 w-4" aria-hidden="true" />
                            </a>
                        @else
                            <span class="page-link page-link-disabled" aria-disabled="true">
                                Next<x-phosphor-caret-right class="h-4 w-4" aria-hidden="true" />
                            </span>
                        @endif
                    </li>
                </ul>
            </nav>
        @endif
    </div>
@endif
