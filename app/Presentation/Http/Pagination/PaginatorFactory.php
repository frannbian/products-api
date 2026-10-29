<?php

namespace App\Presentation\Http\Pagination;

use App\Domain\Shared\Paginated;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class PaginatorFactory
{
    /**
     * Wraps a domain page in Laravel's paginator, so API Resources render the
     * standard "links" and "meta" sections.
     */
    public static function fromPage(Paginated $page, Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $page->items,
            $page->total,
            $page->perPage,
            $page->page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }
}
