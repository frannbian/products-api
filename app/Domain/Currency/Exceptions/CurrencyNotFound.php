<?php

namespace App\Domain\Currency\Exceptions;

use App\Domain\Currency\ValueObjects\CurrencyId;
use DomainException;

final class CurrencyNotFound extends DomainException
{
    public function __construct(public readonly CurrencyId $id)
    {
        parent::__construct("The currency {$id->value()} does not exist.");
    }
}
