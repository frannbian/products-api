<?php

namespace App\Domain\Catalog\Repositories;

use App\Domain\Catalog\Entities\ProductImport;
use App\Domain\Catalog\ValueObjects\ImportId;

interface ProductImportRepository
{
    public function save(ProductImport $import): void;

    public function findById(ImportId $id): ?ProductImport;

    /**
     * Loads the import and locks it until the current transaction ends, so
     * two workers that received the same message never process a row twice.
     */
    public function findByIdForUpdate(ImportId $id): ?ProductImport;
}
