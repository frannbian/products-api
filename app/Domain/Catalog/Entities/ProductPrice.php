<?php

namespace App\Domain\Catalog\Entities;

use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductPriceId;
use App\Domain\Currency\ValueObjects\CurrencyId;

/**
 * Price of a product in a currency other than its base one. Part of the
 * Product aggregate: it is only created and changed through Product.
 */
final class ProductPrice
{
    private function __construct(
        private ?ProductPriceId $id,
        private CurrencyId $currencyId,
        private Amount $amount,
    ) {}

    public static function create(CurrencyId $currencyId, Amount $amount): self
    {
        return new self(null, $currencyId, $amount);
    }

    public static function reconstitute(ProductPriceId $id, CurrencyId $currencyId, Amount $amount): self
    {
        return new self($id, $currencyId, $amount);
    }

    public function changeAmount(Amount $amount): void
    {
        $this->amount = $amount;
    }

    public function id(): ?ProductPriceId
    {
        return $this->id;
    }

    public function currencyId(): CurrencyId
    {
        return $this->currencyId;
    }

    public function amount(): Amount
    {
        return $this->amount;
    }
}
