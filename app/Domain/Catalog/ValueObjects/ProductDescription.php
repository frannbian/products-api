<?php

namespace App\Domain\Catalog\ValueObjects;

use App\Domain\Catalog\Exceptions\InvalidProduct;

final readonly class ProductDescription
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidProduct(
                'The product description cannot be empty.'
            );
        }

        if (mb_strlen($value) > 2000) {
            throw new InvalidProduct(
                'The product description cannot exceed 2000 characters.'
            );
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
