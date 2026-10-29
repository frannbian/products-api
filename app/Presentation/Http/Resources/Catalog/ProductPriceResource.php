<?php

namespace App\Presentation\Http\Resources\Catalog;

use App\Domain\Catalog\Entities\ProductPrice;
use App\Domain\Catalog\ValueObjects\ProductId;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @property ProductPrice $resource
 */
class ProductPriceResource extends JsonResource
{
    /**
     * The price belongs to the Product aggregate and does not know its
     * product, so the id is provided by whoever renders it.
     */
    private ProductId $productId;

    public static function forProduct(ProductId $productId, ProductPrice $price): self
    {
        return tap(new self($price), fn (self $resource) => $resource->productId = $productId);
    }

    /**
     * @param  LengthAwarePaginator<int, ProductPrice>  $prices
     */
    public static function collectionForProduct(ProductId $productId, LengthAwarePaginator $prices): AnonymousResourceCollection
    {
        return self::collection(
            $prices->through(fn (ProductPrice $price) => self::forProduct($productId, $price))
        );
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->value(),
            'product_id' => $this->productId->value(),
            'currency_id' => $this->resource->currencyId()->value(),
            'price' => $this->resource->amount()->value(),
        ];
    }
}
