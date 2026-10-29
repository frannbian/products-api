<?php

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Currency\ValueObjects\CurrencyId;
use DomainException;

/**
 * The price in the base currency is the product price itself, so the product
 * can not hold an additional price in that currency.
 */
final class PriceInBaseCurrency extends DomainException
{
    public function __construct(CurrencyId $currencyId)
    {
        parent::__construct("The currency {$currencyId->value()} is the base currency of the product.");
    }
}
