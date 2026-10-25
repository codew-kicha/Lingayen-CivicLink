<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

/**
 * Server-side pagination for every list (docs/DESIGN_SYSTEM.md §4), rendered by <x-pagination>.
 *
 * - Every list shows 10 rows a page; there is no page-size choice.
 * - Links keep every filter and sort parameter.
 * - A page past the end redirects to the last page.
 */
final class ListPagination
{
    public const PER_PAGE = 10;

    /** @param  Builder|Relation  $query */
    public static function paginate($query, Request $request): LengthAwarePaginator
    {
        $paginator = $query->paginate(self::PER_PAGE)->appends(Arr::except($request->query(), ['page']));

        if ($paginator->currentPage() > $paginator->lastPage()) {
            throw new HttpResponseException(redirect()->to($paginator->url($paginator->lastPage())));
        }

        return $paginator;
    }
}
