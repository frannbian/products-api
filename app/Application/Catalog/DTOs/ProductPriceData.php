<?php

namespace App\Application\Catalog\DTOs;

use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Currency\ValueObjects\CurrencyId;

final readonly class ProductPriceData
{
    public function __construct(
        public CurrencyId $currencyId,
        public Amount $price,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            currencyId: new CurrencyId((int) $data['currency_id']),
            price: new Amount($data['price']),
        );
    }
}
