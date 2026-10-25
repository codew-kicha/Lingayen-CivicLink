{{--
    Pagination for the public site (Accredited CSOs, Resources news): the brand maroon, matching the
    rest of the public pages. Use with ->links('pagination.public'). Below sm only Previous,
    "Page X of Y" and Next show. The signed-in screens use <x-pagination> instead.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">
            Showing <span class="font-semibold tabular-nums text-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            of <span class="font-semibold tabular-nums text-ink">{{ $paginator->total() }}</span>
        </p>

        @php
            $base = 'inline-flex h-10 min-w-10 items-center justify-center gap-1 rounded-md border px-3 text-sm font-semibold transition-colors duration-fast';
            $link = $base.' border-brand-200 bg-paper text-brand-800 hover:border-brand-700 hover:bg-brand-50 focus-visible:outline-focus';
            $off = $base.' border-line bg-paper text-muted';
        @endphp

        <ul class="flex items-center gap-2">
            <li>
                @if ($paginator->onFirstPage())
                    <span class="{{ $off }}" aria-disabled="true"><x-phosphor-caret-left class="h-4 w-4" aria-hidden="true" />Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $link }}"><x-phosphor-caret-left class="h-4 w-4" aria-hidden="true" />Previous</a>
                @endif
            </li>

            <li class="text-sm text-muted sm:hidden">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="hidden px-1 text-sm text-muted sm:block" aria-hidden="true">{{ $element }}</li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li class="hidden sm:block">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $base }} border-brand-800 bg-brand-800 text-paper">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $link }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $link }}">Next<x-phosphor-caret-right class="h-4 w-4" aria-hidden="true" /></a>
                @else
                    <span class="{{ $off }}" aria-disabled="true">Next<x-phosphor-caret-right class="h-4 w-4" aria-hidden="true" /></span>
                @endif
            </li>
        </ul>
    </nav>
@endif
