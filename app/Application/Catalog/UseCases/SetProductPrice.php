<?php

namespace App\Application\Catalog\UseCases;

use App\Application\Catalog\Concerns\EnsuresCurrencyExists;
use App\Application\Catalog\DTOs\ProductPriceData;
use App\Application\Catalog\DTOs\SetProductPriceResult;
use App\Application\Catalog\Exceptions\UnknownCurrency;
use App\Domain\Catalog\Exceptions\PriceInBaseCurrency;
use App\Domain\Catalog\Exceptions\ProductNotFound;
use App\Domain\Catalog\Repositories\ProductRepository;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Currency\Repositories\CurrencyRepository;

/**
 * Registers the price of a product in a currency, replacing the existing
 * price when the product already has one in that currency.
 */
final class SetProductPrice
{
    use EnsuresCurrencyExists;

    public function __construct(
        private ProductRepository $products,
        private CurrencyRepository $currencies,
    ) {}

    /**
     * @throws ProductNotFound
     * @throws UnknownCurrency
     * @throws PriceInBaseCurrency
     */
    public function execute(ProductId $id, ProductPriceData $data): SetProductPriceResult
    {
        $product = $this->products->findById($id) ?? throw new ProductNotFound($id);

        $this->ensureCurrencyExists($data->currencyId);

        $created = $product->setPriceIn($data->currencyId, $data->price);

        $product = $this->products->save($product);

        return new SetProductPriceResult($product->priceIn($data->currencyId), $created);
    }
}
