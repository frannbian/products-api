<?php

namespace App\Domain\Currency\ValueObjects;

use App\Domain\Currency\Exceptions\InvalidCurrency;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;

/**
 * How many units of this currency equal one unit of the reference currency.
 * Stored with 6 decimals, matching the decimal(18, 6) column.
 */
final readonly class ExchangeRate
{
    private const SCALE = 6;

    private const MAX = '999999999999.999999';

    private BigDecimal $value;

    public function __construct(string|int|float $value)
    {
        try {
            $rate = BigDecimal::of((string) $value);
        } catch (MathException) {
            throw new InvalidCurrency('The exchange rate must be a number.');
        }

        if ($rate->getScale() > self::SCALE) {
            throw new InvalidCurrency('The exchange rate cannot have more than 6 decimals.');
        }

        if ($rate->isLessThanOrEqualTo(0)) {
            throw new InvalidCurrency('The exchange rate must be greater than zero.');
        }

        if ($rate->isGreaterThan(self::MAX)) {
            throw new InvalidCurrency('The exchange rate cannot exceed '.self::MAX.'.');
        }

        $this->value = $rate->toScale(self::SCALE);
    }

    public function value(): string
    {
        return (string) $this->value;
    }
}
