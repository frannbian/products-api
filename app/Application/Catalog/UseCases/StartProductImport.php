<?php

namespace App\Application\Catalog\UseCases;

use App\Application\Catalog\Contracts\ProductImportQueue;
use App\Domain\Catalog\Entities\ProductImport;
use App\Domain\Catalog\Repositories\ProductImportRepository;

final class StartProductImport
{
    public function __construct(
        private ProductImportRepository $imports,
        private ProductImportQueue $queue,
    ) {}

    /**
     * Stores the batch and queues it; the products are created later.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public function execute(int $ownerId, array $rows): ProductImport
    {
        $import = ProductImport::start($ownerId, $rows);

        $this->imports->save($import);

        $this->queue->push($import->id());

        return $import;
    }
}
