<?php

use App\Domain\Catalog\Entities\ProductImport;
use App\Domain\Catalog\Enums\ImportStatus;
use App\Domain\Catalog\Exceptions\InvalidProduct;
use App\Domain\Catalog\ValueObjects\ImportId;

function rows(int $count): array
{
    return array_fill(0, $count, ['name' => 'Keyboard']);
}

test('an import starts pending with every row left to process', function () {
    $import = ProductImport::start(ownerId: 3, rows: rows(2));

    expect($import->id())->toBeInstanceOf(ImportId::class)
        ->and($import->ownerId())->toBe(3)
        ->and($import->status())->toBe(ImportStatus::Pending)
        ->and($import->total())->toBe(2)
        ->and($import->processed())->toBe(0)
        ->and($import->nextRow())->toBe(['index' => 0, 'data' => ['name' => 'Keyboard']]);
});

test('an import needs at least one row', function () {
    ProductImport::start(ownerId: 3, rows: []);
})->throws(InvalidProduct::class);

test('processing rows advances the progress and records failures', function () {
    $import = ProductImport::start(3, rows(3));

    $import->recordSuccess();
    $import->recordFailure('The currency 9 does not exist.');

    expect($import->status())->toBe(ImportStatus::Processing)
        ->and($import->processed())->toBe(2)
        ->and($import->succeeded())->toBe(1)
        ->and($import->failed())->toBe(1)
        ->and($import->errors())->toBe([['row' => 1, 'message' => 'The currency 9 does not exist.']])
        ->and($import->nextRow()['index'])->toBe(2);
});

test('the import completes after its last row', function () {
    $import = ProductImport::start(3, rows(1));

    $import->recordSuccess();

    expect($import->status())->toBe(ImportStatus::Completed)
        ->and($import->isFinished())->toBeTrue()
        ->and($import->nextRow())->toBeNull()
        ->and($import->finishedAt())->not->toBeNull();
});

test('an import can fail as a whole', function () {
    $import = ProductImport::start(3, rows(2));

    $import->markFailed('Worker crashed repeatedly.');

    expect($import->status())->toBe(ImportStatus::Failed)
        ->and($import->isFinished())->toBeTrue()
        ->and($import->errors())->toBe([['row' => null, 'message' => 'Worker crashed repeatedly.']]);
});

test('import ids are uuids', function () {
    expect(fn () => new ImportId('not-a-uuid'))->toThrow(InvalidProduct::class)
        ->and((new ImportId('0f8fad5b-d9cb-469f-a165-70867728950e'))->value())->toBe('0f8fad5b-d9cb-469f-a165-70867728950e');
});
