<?php

namespace App\Domain\Catalog\ValueObjects;

use App\Domain\Catalog\Exceptions\InvalidProduct;

final readonly class ProductName
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidProduct(
                'The product name cannot be empty.'
            );
        }

        if (mb_strlen($value) > 255) {
            throw new InvalidProduct(
                'The product name cannot exceed 255 characters.'
            );
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
