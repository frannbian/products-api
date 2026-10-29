<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Application\Catalog\UseCases\CreateProduct;
use App\Application\Catalog\UseCases\DeleteProduct;
use App\Application\Catalog\UseCases\GetProduct;
use App\Application\Catalog\UseCases\ListProducts;
use App\Application\Catalog\UseCases\UpdateProduct;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Pagination\PaginatorFactory;
use App\Presentation\Http\Requests\Catalog\ProductRequest;
use App\Presentation\Http\Requests\PaginatedRequest;
use App\Presentation\Http\Resources\Catalog\ProductResource;
use Dedoc\Scramble\Attributes\Header;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Dedoc\Scramble\Attributes\IgnoreResponse;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;

class ProductController extends Controller
{
    /**
     * List products, paginated.
     */
    public function index(PaginatedRequest $request, ListProducts $listProducts): AnonymousResourceCollection
    {
        $page = $listProducts->execute($request->page(), $request->perPage());

        return ProductResource::collection(PaginatorFactory::fromPage($page, $request));
    }

    /**
     * Create a product.
     */
    #[HeaderParameter('Idempotency-Key', 'Unique key (1-255 characters) to retry the request safely: a retry with the same key and body replays the first response instead of repeating it.', required: false, type: 'string')]
    #[Response(201, 'Product created', type: ProductResource::class)]
    #[IgnoreResponse(200)]
    public function store(ProductRequest $request, CreateProduct $createProduct): JsonResponse
    {
        return ProductResource::make($createProduct->execute($request->toData()))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a product.
     *
     * Send the ETag back in If-None-Match to get a 304 when nothing changed.
     */
    #[HeaderParameter('If-None-Match', 'ETag you already have: responds 304 without a body when the product did not change.', required: false, type: 'string')]
    #[Header('ETag', 'Fingerprint of the response, to send back in If-None-Match.', type: 'string')]
    #[Response(304, 'Not modified: the ETag in If-None-Match is still current')]
    public function show(int $product, GetProduct $getProduct): ProductResource
    {
        return ProductResource::make($getProduct->execute(new ProductId($product)));
    }

    /**
     * Replace every field of a product.
     *
     * Concurrent updates of the same product are applied one after the other.
     */
    public function update(ProductRequest $request, int $product, UpdateProduct $updateProduct): ProductResource
    {
        return ProductResource::make($updateProduct->execute(new ProductId($product), $request->toData()));
    }

    /**
     * Delete a product.
     */
    public function destroy(int $product, DeleteProduct $deleteProduct): HttpResponse
    {
        $deleteProduct->execute(new ProductId($product));

        return response()->noContent();
    }
}
