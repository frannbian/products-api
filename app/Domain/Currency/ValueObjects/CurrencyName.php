<?php

namespace App\Domain\Currency\ValueObjects;

use App\Domain\Currency\Exceptions\InvalidCurrency;

final readonly class CurrencyName
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidCurrency(
                'The currency name cannot be empty.'
            );
        }

        if (mb_strlen($value) > 100) {
            throw new InvalidCurrency(
                'The currency name cannot exceed 100 characters.'
            );
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
