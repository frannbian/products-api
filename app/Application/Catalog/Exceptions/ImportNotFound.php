<?php

namespace App\Application\Catalog\Exceptions;

use App\Domain\Catalog\ValueObjects\ImportId;
use RuntimeException;

/**
 * The import does not exist or belongs to someone else: both look the same
 * from the outside, so imports of other users can not be discovered.
 */
final class ImportNotFound extends RuntimeException
{
    public function __construct(ImportId $id)
    {
        parent::__construct("The import {$id->value()} does not exist.");
    }
}
