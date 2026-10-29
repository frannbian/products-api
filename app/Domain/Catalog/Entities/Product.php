<?php

namespace App\Domain\Catalog\Entities;

use App\Domain\Catalog\Exceptions\PriceInBaseCurrency;
use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductDescription;
use App\Domain\Catalog\ValueObjects\ProductId;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Currency\ValueObjects\CurrencyId;

/**
 * Aggregate root of the catalog. The price, tax cost and manufacturing cost
 * are expressed in the base currency; prices in other currencies are kept
 * as ProductPrice entities, at most one per currency.
 */
final class Product
{
    /**
     * @param  list<ProductPrice>  $prices
     */
    private function __construct(
        private ?ProductId $id,
        private ProductName $name,
        private ProductDescription $description,
        private Amount $price,
        private CurrencyId $currencyId,
        private Amount $taxCost,
        private Amount $manufacturingCost,
        private array $prices,
        private int $version,
    ) {}

    public static function create(
        ProductName $name,
        ProductDescription $description,
        Amount $price,
        CurrencyId $currencyId,
        Amount $taxCost,
        Amount $manufacturingCost,
    ): self {
        return new self(null, $name, $description, $price, $currencyId, $taxCost, $manufacturingCost, [], 1);
    }

    /**
     * @param  list<ProductPrice>  $prices
     */
    public static function reconstitute(
        ProductId $id,
        ProductName $name,
        ProductDescription $description,
        Amount $price,
        CurrencyId $currencyId,
        Amount $taxCost,
        Amount $manufacturingCost,
        array $prices,
        int $version = 1,
    ): self {
        return new self($id, $name, $description, $price, $currencyId, $taxCost, $manufacturingCost, $prices, $version);
    }

    /**
     * @throws PriceInBaseCurrency when the new base currency already has a price.
     */
    public function update(
        ProductName $name,
        ProductDescription $description,
        Amount $price,
        CurrencyId $currencyId,
        Amount $taxCost,
        Amount $manufacturingCost,
    ): void {
        if ($this->priceIn($currencyId) !== null) {
            throw new PriceInBaseCurrency($currencyId);
        }

        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->currencyId = $currencyId;
        $this->taxCost = $taxCost;
        $this->manufacturingCost = $manufacturingCost;
    }

    /**
     * Registers the price in the given currency, replacing the existing one.
     * Returns true when the currency had no price yet.
     *
     * @throws PriceInBaseCurrency
     */
    public function setPriceIn(CurrencyId $currencyId, Amount $amount): bool
    {
        if ($currencyId->equals($this->currencyId)) {
            throw new PriceInBaseCurrency($currencyId);
        }

        $existing = $this->priceIn($currencyId);

        if ($existing !== null) {
            $existing->changeAmount($amount);

            return false;
        }

        $this->prices[] = ProductPrice::create($currencyId, $amount);

        return true;
    }

    public function priceIn(CurrencyId $currencyId): ?ProductPrice
    {
        foreach ($this->prices as $price) {
            if ($price->currencyId()->equals($currencyId)) {
                return $price;
            }
        }

        return null;
    }

    /**
     * Optimistic locking version: incremented every time the product is saved.
     */
    public function version(): int
    {
        return $this->version;
    }

    public function id(): ?ProductId
    {
        return $this->id;
    }

    public function name(): ProductName
    {
        return $this->name;
    }

    public function description(): ProductDescription
    {
        return $this->description;
    }

    public function price(): Amount
    {
        return $this->price;
    }

    public function currencyId(): CurrencyId
    {
        return $this->currencyId;
    }

    public function taxCost(): Amount
    {
        return $this->taxCost;
    }

    public function manufacturingCost(): Amount
    {
        return $this->manufacturingCost;
    }

    /**
     * @return list<ProductPrice>
     */
    public function prices(): array
    {
        return $this->prices;
    }
}
