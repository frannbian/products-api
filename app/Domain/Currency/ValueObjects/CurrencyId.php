<?php

namespace App\Domain\Currency\ValueObjects;

use App\Domain\Currency\Exceptions\InvalidCurrency;

final readonly class CurrencyId
{
    public function __construct(
        private int $value
    ) {
        if ($this->value < 1) {
            throw new InvalidCurrency(
                'The currency id must be a positive integer.'
            );
        }
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
