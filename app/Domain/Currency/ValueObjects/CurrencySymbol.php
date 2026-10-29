<?php

namespace App\Domain\Currency\ValueObjects;

use App\Domain\Currency\Exceptions\InvalidCurrency;

final readonly class CurrencySymbol
{
    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidCurrency(
                'The currency symbol cannot be empty.'
            );
        }

        if (mb_strlen($value) > 10) {
            throw new InvalidCurrency(
                'The currency symbol cannot exceed 10 characters.'
            );
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
