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
use App\Presentation\Http\Requests\Catalog\ListProductsRequest;
use App\Presentation\Http\Requests\Catalog\ProductRequest;
use App\Presentation\Http\Resources\Catalog\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    /**
     * List products, paginated.
     */
    public function index(ListProductsRequest $request, ListProducts $listProducts): AnonymousResourceCollection
    {
        $page = $listProducts->execute($request->page(), $request->perPage());

        return ProductResource::collection(PaginatorFactory::fromPage($page, $request));
    }

    /**
     * Create a product.
     */
    public function store(ProductRequest $request, CreateProduct $createProduct): JsonResponse
    {
        return ProductResource::make($createProduct->execute($request->toData()))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a product.
     */
    public function show(int $product, GetProduct $getProduct): ProductResource
    {
        return ProductResource::make($getProduct->execute(new ProductId($product)));
    }

    /**
     * Replace every field of a product.
     */
    public function update(ProductRequest $request, int $product, UpdateProduct $updateProduct): ProductResource
    {
        return ProductResource::make($updateProduct->execute(new ProductId($product), $request->toData()));
    }

    /**
     * Delete a product.
     */
    public function destroy(int $product, DeleteProduct $deleteProduct): Response
    {
        $deleteProduct->execute(new ProductId($product));

        return response()->noContent();
    }
}
