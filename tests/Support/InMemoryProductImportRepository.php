<?php

namespace Tests\Support;

use App\Domain\Catalog\Entities\ProductImport;
use App\Domain\Catalog\Repositories\ProductImportRepository;
use App\Domain\Catalog\ValueObjects\ImportId;

final class InMemoryProductImportRepository implements ProductImportRepository
{
    /** @var array<string, ProductImport> */
    private array $imports = [];

    /** @var list<array{id: string, inTransaction: bool}> */
    public array $locks = [];

    public function __construct(
        private ?FakeTransactionManager $transactions = null,
    ) {}

    public function save(ProductImport $import): void
    {
        $this->imports[$import->id()->value()] = clone $import;
    }

    public function findById(ImportId $id): ?ProductImport
    {
        return isset($this->imports[$id->value()]) ? clone $this->imports[$id->value()] : null;
    }

    public function findByIdForUpdate(ImportId $id): ?ProductImport
    {
        $this->locks[] = ['id' => $id->value(), 'inTransaction' => (bool) $this->transactions?->active()];

        return $this->findById($id);
    }
}
