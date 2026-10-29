<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Application\Catalog\UseCases\ListProductPrices;
use App\Application\Catalog\UseCases\SetProductPrice;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Pagination\PaginatorFactory;
use App\Presentation\Http\Requests\Catalog\ProductPriceRequest;
use App\Presentation\Http\Requests\PaginatedRequest;
use App\Presentation\Http\Resources\Catalog\ProductPriceResource;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductPriceController extends Controller
{
    /**
     * List the prices of a product in other currencies, paginated.
     */
    public function index(PaginatedRequest $request, int $product, ListProductPrices $listPrices): AnonymousResourceCollection
    {
        $id = new ProductId($product);
        $page = $listPrices->execute($id, $request->page(), $request->perPage());

        return ProductPriceResource::collectionForProduct($id, PaginatorFactory::fromPage($page, $request));
    }

    /**
     * Register the price of a product in a currency.
     *
     * Responds 201 when the price is new and 200 when it replaces the
     * existing one. Concurrent requests for the same product are applied one
     * after the other.
     *
     * @status 201
     */
    #[HeaderParameter('Idempotency-Key', 'Unique key (1-255 characters) to retry the request safely: a retry with the same key and body replays the first response instead of repeating it.', required: false, type: 'string')]
    #[Response(201, 'Price registered in a currency the product had no price in', type: ProductPriceResource::class)]
    public function store(ProductPriceRequest $request, int $product, SetProductPrice $setPrice): JsonResponse
    {
        $id = new ProductId($product);
        $result = $setPrice->execute($id, $request->toData());

        return ProductPriceResource::forProduct($id, $result->price)
            ->response()
            ->setStatusCode($result->created ? 201 : 200);
    }
}
