<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Application\Catalog\UseCases\CreateProduct;
use App\Application\Catalog\UseCases\DeleteProduct;
use App\Application\Catalog\UseCases\GetProduct;
use App\Application\Catalog\UseCases\ListProducts;
use App\Application\Catalog\UseCases\UpdateProduct;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Presentation\Http\Concurrency\EntityTag;
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
use Illuminate\Http\Request;
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
     *
     * @status 201
     */
    #[HeaderParameter('Idempotency-Key', 'Unique key (1-255 characters) to retry the request safely: a retry with the same key and body replays the first response instead of repeating it.', required: false, type: 'string')]
    #[Response(201, 'Product created', type: ProductResource::class)]
    #[Header('ETag', 'Version of the product, to use in If-Match and If-None-Match.', type: 'string', status: 201)]
    #[IgnoreResponse(200)]
    public function store(ProductRequest $request, CreateProduct $createProduct): JsonResponse
    {
        $created = $createProduct->execute($request->toData());

        return EntityTag::apply(ProductResource::make($created)->response()->setStatusCode(201), $created->version(), $request);
    }

    /**
     * Get a product.
     *
     * The ETag header holds the product version: send it back in If-None-Match
     * to get a 304 when nothing changed, or in If-Match when updating.
     */
    #[HeaderParameter('If-None-Match', 'ETag you already have: responds 304 without a body when the product did not change.', required: false, type: 'string')]
    #[Header('ETag', 'Version of the product, to use in If-Match and If-None-Match.', type: 'string')]
    #[Response(304, 'Not modified: the version in If-None-Match is the current one')]
    public function show(Request $request, int $product, GetProduct $getProduct): JsonResponse
    {
        $found = $getProduct->execute(new ProductId($product));

        return EntityTag::apply(ProductResource::make($found)->response(), $found->version(), $request);
    }

    /**
     * Replace every field of a product.
     *
     * Send the ETag you read in If-Match to make sure nobody changed the
     * product in between: a different version responds 412.
     */
    #[HeaderParameter('If-Match', 'ETag you read: the update only succeeds if the product still has that version (412 otherwise).', required: false, type: 'string')]
    #[Header('ETag', 'New version of the product.', type: 'string')]
    public function update(ProductRequest $request, int $product, UpdateProduct $updateProduct): JsonResponse
    {
        $updated = $updateProduct->execute(
            new ProductId($product),
            $request->toData(),
            EntityTag::expectedVersion($request),
        );

        return EntityTag::apply(ProductResource::make($updated)->response(), $updated->version(), $request);
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
