<?php

namespace App\Domain\Catalog\ValueObjects;

use App\Domain\Catalog\Exceptions\InvalidProduct;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;

/**
 * A non negative monetary amount with 2 decimals, matching the decimal(12, 2)
 * columns. Kept as an exact decimal to avoid floating point errors.
 */
final readonly class Amount
{
    private const SCALE = 2;

    private const MAX = '9999999999.99';

    private BigDecimal $value;

    public function __construct(string|int|float $value)
    {
        try {
            $amount = BigDecimal::of((string) $value);
        } catch (MathException) {
            throw new InvalidProduct('The amount must be a number.');
        }

        if ($amount->getScale() > self::SCALE) {
            throw new InvalidProduct('The amount cannot have more than 2 decimals.');
        }

        if ($amount->isNegative()) {
            throw new InvalidProduct('The amount cannot be negative.');
        }

        if ($amount->isGreaterThan(self::MAX)) {
            throw new InvalidProduct('The amount cannot exceed '.self::MAX.'.');
        }

        $this->value = $amount->toScale(self::SCALE);
    }

    public function value(): string
    {
        return (string) $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value->isEqualTo($other->value);
    }
}
