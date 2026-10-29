<?php

namespace App\Application\Catalog\DTOs;

use App\Domain\Catalog\ValueObjects\Amount;
use App\Domain\Catalog\ValueObjects\ProductDescription;
use App\Domain\Catalog\ValueObjects\ProductName;
use App\Domain\Currency\ValueObjects\CurrencyId;

final readonly class ProductData
{
    public function __construct(
        public ProductName $name,
        public ProductDescription $description,
        public Amount $price,
        public CurrencyId $currencyId,
        public Amount $taxCost,
        public Amount $manufacturingCost,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: new ProductName($data['name']),
            description: new ProductDescription($data['description']),
            price: new Amount($data['price']),
            currencyId: new CurrencyId((int) $data['currency_id']),
            taxCost: new Amount($data['tax_cost']),
            manufacturingCost: new Amount($data['manufacturing_cost']),
        );
    }
}
