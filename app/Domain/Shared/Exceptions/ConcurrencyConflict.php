<?php

namespace App\Domain\Shared\Exceptions;

use DomainException;

/**
 * The aggregate was modified by someone else between reading and saving it.
 */
final class ConcurrencyConflict extends DomainException
{
    public function __construct(string $aggregate, int $id)
    {
        parent::__construct("The {$aggregate} {$id} was modified by another request.");
    }
}
