<?php

namespace App\Application\Catalog\UseCases;

use App\Application\Catalog\Concerns\EnsuresCurrencyExists;
use App\Application\Catalog\DTOs\ProductPriceData;
use App\Application\Catalog\DTOs\SetProductPriceResult;
use App\Application\Catalog\Exceptions\UnknownCurrency;
use App\Application\Shared\Contracts\TransactionManager;
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
        private TransactionManager $transactions,
    ) {}

    /**
     * The product is locked while its prices change, so two requests for the
     * same currency are serialized: the second one finds the first price and
     * replaces it instead of inserting a duplicate.
     *
     * @throws ProductNotFound
     * @throws UnknownCurrency
     * @throws PriceInBaseCurrency
     */
    public function execute(ProductId $id, ProductPriceData $data): SetProductPriceResult
    {
        $this->ensureCurrencyExists($data->currencyId);

        return $this->transactions->run(function () use ($id, $data) {
            $product = $this->products->findByIdForUpdate($id) ?? throw new ProductNotFound($id);

            $created = $product->setPriceIn($data->currencyId, $data->price);

            $product = $this->products->save($product);

            return new SetProductPriceResult($product->priceIn($data->currencyId), $created);
        });
    }
}
