<?php

namespace App\Application\Catalog\UseCases;

use App\Application\Catalog\Exceptions\ImportNotFound;
use App\Domain\Catalog\Entities\ProductImport;
use App\Domain\Catalog\Repositories\ProductImportRepository;
use App\Domain\Catalog\ValueObjects\ImportId;

final class GetProductImport
{
    public function __construct(
        private ProductImportRepository $imports,
    ) {}

    /**
     * @throws ImportNotFound when it does not exist or is not the requester's.
     */
    public function execute(ImportId $id, int $requesterId): ProductImport
    {
        $import = $this->imports->findById($id);

        if ($import === null || $import->ownerId() !== $requesterId) {
            throw new ImportNotFound($id);
        }

        return $import;
    }
}
