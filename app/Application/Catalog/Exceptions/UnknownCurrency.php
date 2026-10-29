<?php

namespace App\Application\Catalog\Exceptions;

use App\Domain\Currency\ValueObjects\CurrencyId;
use RuntimeException;

/**
 * A product or price references a currency that does not exist.
 */
final class UnknownCurrency extends RuntimeException
{
    public function __construct(public readonly CurrencyId $id)
    {
        parent::__construct("The currency {$id->value()} does not exist.");
    }
}
