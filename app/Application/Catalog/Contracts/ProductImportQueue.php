<?php

namespace App\Application\Catalog\Contracts;

use App\Domain\Catalog\ValueObjects\ImportId;

/**
 * Hands an import over to be processed in the background. Only the id
 * travels (claim check): the rows stay in the database.
 */
interface ProductImportQueue
{
    public function push(ImportId $id): void;
}
