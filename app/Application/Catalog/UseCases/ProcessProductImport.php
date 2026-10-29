<?php

namespace App\Application\Catalog\UseCases;

use App\Application\Catalog\DTOs\ProductData;
use App\Application\Catalog\Exceptions\UnknownCurrency;
use App\Application\Shared\Contracts\TransactionManager;
use App\Domain\Catalog\Exceptions\InvalidProduct;
use App\Domain\Catalog\Repositories\ProductImportRepository;
use App\Domain\Catalog\ValueObjects\ImportId;
use App\Domain\Currency\Exceptions\InvalidCurrency;

/**
 * Creates the products of an import, one row at a time.
 *
 * Each row runs in its own transaction that locks the import, creates the
 * product and advances the progress together. A crash loses at most the
 * row in flight, a retry resumes from the stored progress, and a duplicate
 * message waits for the lock and finds the row already done: every row is
 * processed exactly once.
 */
final class ProcessProductImport
{
    public function __construct(
        private ProductImportRepository $imports,
        private CreateProduct $createProduct,
        private TransactionManager $transactions,
    ) {}

    public function execute(ImportId $id): void
    {
        while ($this->processNextRow($id)) {
            // One transaction per row keeps locks short and progress durable.
        }
    }

    /**
     * Returns false when there is nothing left to process.
     */
    private function processNextRow(ImportId $id): bool
    {
        return $this->transactions->run(function () use ($id) {
            $import = $this->imports->findByIdForUpdate($id);
            $row = $import?->nextRow();

            if ($row === null) {
                return false;
            }

            try {
                $this->createProduct->execute(ProductData::fromArray($row['data']));
                $import->recordSuccess();
            } catch (UnknownCurrency|InvalidProduct|InvalidCurrency $e) {
                // A business rule rejected this row: report it and go on.
                $import->recordFailure($e->getMessage());
            }

            $this->imports->save($import);

            return true;
        });
    }
}
