<?php

namespace App\Presentation\Http\Controllers\Catalog;

use App\Application\Catalog\UseCases\ListProductPrices;
use App\Application\Catalog\UseCases\SetProductPrice;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Presentation\Http\Controllers\Controller;
use App\Presentation\Http\Requests\Catalog\ProductPriceRequest;
use App\Presentation\Http\Resources\Catalog\ProductPriceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductPriceController extends Controller
{
    /**
     * List the prices of a product in other currencies.
     */
    public function index(int $product, ListProductPrices $listPrices): AnonymousResourceCollection
    {
        $id = new ProductId($product);

        return ProductPriceResource::collectionForProduct($id, $listPrices->execute($id));
    }

    /**
     * Register the price of a product in a currency. Responds 201 when the
     * price is new and 200 when it replaces the existing one.
     */
    public function store(ProductPriceRequest $request, int $product, SetProductPrice $setPrice): JsonResponse
    {
        $id = new ProductId($product);
        $result = $setPrice->execute($id, $request->toData());

        return ProductPriceResource::forProduct($id, $result->price)
            ->response()
            ->setStatusCode($result->created ? 201 : 200);
    }
}
