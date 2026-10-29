<?php

use App\Application\Catalog\Contracts\ProductImportQueue;
use App\Application\Catalog\Exceptions\ImportNotFound;
use App\Application\Catalog\UseCases\CreateProduct;
use App\Application\Catalog\UseCases\GetProductImport;
use App\Application\Catalog\UseCases\ProcessProductImport;
use App\Application\Catalog\UseCases\StartProductImport;
use App\Domain\Catalog\Entities\ProductImport;
use App\Domain\Catalog\Enums\ImportStatus;
use App\Domain\Catalog\ValueObjects\ImportId;
use Tests\Support\FakeEventPublisher;
use Tests\Support\FakeTransactionManager;
use Tests\Support\InMemoryCurrencyRepository;
use Tests\Support\InMemoryProductImportRepository;
use Tests\Support\InMemoryProductRepository;

beforeEach(function () {
    $this->tx = new FakeTransactionManager;
    $this->imports = new InMemoryProductImportRepository($this->tx);
    $this->products = new InMemoryProductRepository($this->tx);
    $this->currencies = new InMemoryCurrencyRepository;
    $this->usd = $this->currencies->add('US Dollar');
    $this->queue = new class implements ProductImportQueue
    {
        /** @var list<string> */
        public array $pushed = [];

        public function push(ImportId $id): void
        {
            $this->pushed[] = $id->value();
        }
    };
    $this->process = new ProcessProductImport(
        $this->imports,
        new CreateProduct($this->products, $this->currencies, new FakeEventPublisher),
        $this->tx,
    );
});

function importRow(int $currencyId, string $name = 'Keyboard'): array
{
    return ['name' => $name, 'description' => 'Desc', 'price' => '10', 'currency_id' => $currencyId, 'tax_cost' => '1', 'manufacturing_cost' => '2'];
}

test('starting an import stores it and queues only its id', function () {
    $import = (new StartProductImport($this->imports, $this->queue))->execute(ownerId: 3, rows: [importRow(1), importRow(1)]);

    expect($import->status())->toBe(ImportStatus::Pending)
        ->and($import->total())->toBe(2)
        ->and($this->imports->findById($import->id()))->not->toBeNull()
        ->and($this->queue->pushed)->toBe([$import->id()->value()]);
});

test('processing creates a product per row and completes the import', function () {
    $import = (new StartProductImport($this->imports, $this->queue))->execute(3, [importRow($this->usd->id()->value(), 'A'), importRow($this->usd->id()->value(), 'B')]);

    $this->process->execute($import->id());

    $stored = $this->imports->findById($import->id());
    expect($stored->status())->toBe(ImportStatus::Completed)
        ->and($stored->succeeded())->toBe(2)
        ->and($this->products->paginate(1, 15)->total)->toBe(2);
});

test('rows that break a business rule are recorded without stopping the import', function () {
    $import = (new StartProductImport($this->imports, $this->queue))->execute(3, [importRow(99), importRow($this->usd->id()->value())]);

    $this->process->execute($import->id());

    $stored = $this->imports->findById($import->id());
    expect($stored->status())->toBe(ImportStatus::Completed)
        ->and($stored->succeeded())->toBe(1)
        ->and($stored->failed())->toBe(1)
        ->and($stored->errors())->toBe([['row' => 0, 'message' => 'The currency 99 does not exist.']]);
});

test('each row is processed in its own transaction holding the import lock', function () {
    $import = (new StartProductImport($this->imports, $this->queue))->execute(3, [importRow($this->usd->id()->value()), importRow($this->usd->id()->value())]);

    $this->process->execute($import->id());

    // One per row, plus a last one that finds nothing left under the lock.
    expect($this->tx->transactions)->toBe(3)
        ->and(collect($this->imports->locks)->every(fn (array $lock) => $lock['inTransaction']))->toBeTrue();
});

test('a retried import resumes after the last processed row', function () {
    $import = ProductImport::start(3, [importRow($this->usd->id()->value(), 'A'), importRow($this->usd->id()->value(), 'B')]);
    $import->recordSuccess();
    $this->imports->save($import);

    $this->process->execute($import->id());

    expect($this->products->paginate(1, 15)->total)->toBe(1)
        ->and($this->products->paginate(1, 15)->items[0]->name()->value())->toBe('B');
});

test('processing a finished import again does nothing', function () {
    $import = (new StartProductImport($this->imports, $this->queue))->execute(3, [importRow($this->usd->id()->value())]);
    $this->process->execute($import->id());

    $this->process->execute($import->id());

    expect($this->products->paginate(1, 15)->total)->toBe(1);
});

test('the owner can get the import', function () {
    $import = (new StartProductImport($this->imports, $this->queue))->execute(3, [importRow(1)]);

    expect((new GetProductImport($this->imports))->execute($import->id(), requesterId: 3)->id()->value())->toBe($import->id()->value());
});

test('other users can not see the import', function () {
    $import = (new StartProductImport($this->imports, $this->queue))->execute(3, [importRow(1)]);

    (new GetProductImport($this->imports))->execute($import->id(), requesterId: 4);
})->throws(ImportNotFound::class);
