<?php

namespace App\Support;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

/**
 * Server-side pagination for every list (docs/DESIGN_SYSTEM.md §4), rendered by <x-pagination>.
 *
 * - `per_page` is whitelisted; anything else quietly falls back to the list's default.
 * - Links keep every filter and sort parameter, and leave out `per_page` when it is the default.
 * - A page past the end redirects to the last page.
 */
final class ListPagination
{
    public const SIZES = [10, 25, 50];

    public static function perPage(Request $request, int $default): int
    {
        $requested = filter_var($request->query('per_page'), FILTER_VALIDATE_INT);

        return in_array($requested, self::SIZES, true) ? $requested : $default;
    }

    /** @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation  $query */
    public static function paginate($query, Request $request, int $default): LengthAwarePaginator
    {
        $paginator = $query->paginate(self::perPage($request, $default))->appends(self::linkQuery($request, $default));

        if ($paginator->currentPage() > $paginator->lastPage()) {
            throw new HttpResponseException(redirect()->to($paginator->url($paginator->lastPage())));
        }

        return $paginator;
    }

    /** The current query minus `page`, with `per_page` normalised (dropped when it is the default). */
    public static function linkQuery(Request $request, int $default): array
    {
        $query = Arr::except($request->query(), ['page', 'per_page']);
        $perPage = self::perPage($request, $default);

        return $perPage === $default ? $query : $query + ['per_page' => $perPage];
    }
}
