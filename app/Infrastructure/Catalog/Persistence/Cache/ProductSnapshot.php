<?php

namespace App\Infrastructure\Catalog\Persistence\Cache;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Entities\ProductPrice;
use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductDescription;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Catalog\ValueObjects\ProductPriceId;
use App\Domain\Currency\ValueObjects\CurrencyId;

/**
 * Plain array form of the Product aggregate, for the cache.
 */
final class ProductSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Product $product): array
    {
        return [
            'id' => $product->id()->value(),
            'name' => $product->name()->value(),
            'description' => $product->description()->value(),
            'price' => $product->price()->value(),
            'currency_id' => $product->currencyId()->value(),
            'tax_cost' => $product->taxCost()->value(),
            'manufacturing_cost' => $product->manufacturingCost()->value(),
            'prices' => array_map(self::priceToArray(...), $product->prices()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): Product
    {
        return Product::reconstitute(
            new ProductId($data['id']),
            new ProductName($data['name']),
            new ProductDescription($data['description']),
            new Amount($data['price']),
            new CurrencyId($data['currency_id']),
            new Amount($data['tax_cost']),
            new Amount($data['manufacturing_cost']),
            array_map(self::priceFromArray(...), $data['prices']),
        );
    }

    /**
     * @return array{id: int, currency_id: int, amount: string}
     */
    public static function priceToArray(ProductPrice $price): array
    {
        return [
            'id' => $price->id()->value(),
            'currency_id' => $price->currencyId()->value(),
            'amount' => $price->amount()->value(),
        ];
    }

    /**
     * @param  array{id: int, currency_id: int, amount: string}  $data
     */
    public static function priceFromArray(array $data): ProductPrice
    {
        return ProductPrice::reconstitute(
            new ProductPriceId($data['id']),
            new CurrencyId($data['currency_id']),
            new Amount($data['amount']),
        );
    }
}
