<?php

namespace App\Domain\Currency\Exceptions;

use App\Domain\Currency\ValueObjects\CurrencyId;
use DomainException;

final class CurrencyInUse extends DomainException
{
    public function __construct(CurrencyId $id)
    {
        parent::__construct("The currency {$id->value()} is used by products and can not be deleted.");
    }
}
