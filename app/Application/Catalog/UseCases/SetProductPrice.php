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
use App\Domain\Shared\Exceptions\ConcurrencyConflict;

/**
 * Registers the price of a product in a currency, replacing the existing
 * price when the product already has one in that currency.
 */
final class SetProductPrice
{
    use EnsuresCurrencyExists;

    /**
     * Setting a price is safe to repeat, so a concurrent modification of the
     * product is retried on a freshly loaded copy instead of failing.
     */
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private ProductRepository $products,
        private CurrencyRepository $currencies,
    ) {}

    /**
     * @throws ProductNotFound
     * @throws UnknownCurrency
     * @throws PriceInBaseCurrency
     * @throws ConcurrencyConflict after MAX_ATTEMPTS conflicting attempts
     */
    public function execute(ProductId $id, ProductPriceData $data): SetProductPriceResult
    {
        $this->ensureCurrencyExists($data->currencyId);

        for ($attempt = 1; ; $attempt++) {
            $product = $this->products->findById($id) ?? throw new ProductNotFound($id);

            $created = $product->setPriceIn($data->currencyId, $data->price);

            try {
                $product = $this->products->save($product);
            } catch (ConcurrencyConflict $conflict) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw $conflict;
                }

                continue;
            }

            return new SetProductPriceResult($product->priceIn($data->currencyId), $created);
        }
    }
}
