<?php

namespace App\Infrastructure\Catalog\Persistence\Eloquent;

use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Entities\ProductPrice;
use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductDescription;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Catalog\ValueObjects\ProductPriceId;
use App\Domain\Currency\ValueObjects\CurrencyId;

final class ProductMapper
{
    public static function toDomain(ProductModel $model): Product
    {
        return Product::reconstitute(
            new ProductId($model->id),
            new ProductName($model->name),
            new ProductDescription($model->description),
            new Amount($model->price),
            new CurrencyId($model->currency_id),
            new Amount($model->tax_cost),
            new Amount($model->manufacturing_cost),
            $model->prices->map(self::priceToDomain(...))->all(),
            $model->version,
        );
    }

    public static function priceToDomain(ProductPriceModel $model): ProductPrice
    {
        return ProductPrice::reconstitute(
            new ProductPriceId($model->id),
            new CurrencyId($model->currency_id),
            new Amount($model->price),
        );
    }

    /**
     * @return array<string, string|int>
     */
    public static function attributes(Product $product): array
    {
        return [
            'name' => $product->name()->value(),
            'description' => $product->description()->value(),
            'price' => $product->price()->value(),
            'currency_id' => $product->currencyId()->value(),
            'tax_cost' => $product->taxCost()->value(),
            'manufacturing_cost' => $product->manufacturingCost()->value(),
        ];
    }
}
