<?php

namespace App\Domain\Currency\Exceptions;

use App\Domain\Currency\ValueObjects\CurrencyName;
use DomainException;

final class CurrencyNameAlreadyTaken extends DomainException
{
    public function __construct(CurrencyName $name)
    {
        parent::__construct("The currency name {$name->value()} is already taken.");
    }
}
