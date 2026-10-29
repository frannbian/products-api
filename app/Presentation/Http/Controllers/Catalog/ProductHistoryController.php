<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Application\Catalog\UseCases\ListProductHistory;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Pagination\PaginatorFactory;
use App\Presentation\Http\Requests\PaginatedRequest;
use App\Presentation\Http\Resources\AuditEntryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductHistoryController extends Controller
{
    /**
     * List the changes made to a product, oldest first.
     *
     * Each change is recorded asynchronously by a queue consumer, so the
     * latest one may take a moment to appear. Deleted products keep their
     * history.
     */
    public function __invoke(PaginatedRequest $request, int $product, ListProductHistory $listHistory): AnonymousResourceCollection
    {
        $page = $listHistory->execute(new ProductId($product), $request->page(), $request->perPage());

        return AuditEntryResource::collection(PaginatorFactory::fromPage($page, $request));
    }
}
