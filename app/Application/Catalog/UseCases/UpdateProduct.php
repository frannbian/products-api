<?php

namespace App\Application\Catalog\UseCases;

use App\Application\Catalog\Concerns\EnsuresCurrencyExists;
use App\Application\Catalog\DTOs\ProductData;
use App\Application\Catalog\Exceptions\UnknownCurrency;
use App\Application\Shared\Exceptions\StaleVersion;
use App\Domain\Catalog\Entities\Product;
use App\Domain\Catalog\Exceptions\PriceInBaseCurrency;
use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Currency\Repositories\CurrencyRepository;
use App\Domain\Shared\Exceptions\ConcurrencyConflict;

final class UpdateProduct
{
    use EnsuresCurrencyExists;

    public function __construct(
        private ProductRepository $products,
        private CurrencyRepository $currencies,
    ) {}

    /**
     * @param  int|null  $expectedVersion  Version the client read; null skips the check.
     *
     * @throws ProductNotFound
     * @throws StaleVersion
     * @throws UnknownCurrency
     * @throws PriceInBaseCurrency
     * @throws ConcurrencyConflict
     */
    public function execute(ProductId $id, ProductData $data, ?int $expectedVersion = null): Product
    {
        $product = $this->products->findById($id) ?? throw new ProductNotFound($id);

        StaleVersion::check($expectedVersion, $product->version());

        $this->ensureCurrencyExists($data->currencyId);

        $product->update(
            $data->name,
            $data->description,
            $data->price,
            $data->currencyId,
            $data->taxCost,
            $data->manufacturingCost,
        );

        return $this->products->save($product);
    }
}
