<?php

use App\Domain\Catalog\Entities\ProductImport;
use App\Domain\Catalog\Enums\ImportStatus;
use App\Domain\Catalog\Repositories\ProductImportRepository;
use App\Infrastructure\Catalog\Jobs\ProcessProductImportJob;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->owner = UserModel::factory()->create();
    $this->repository = app(ProductImportRepository::class);
});

test('an import is stored and loaded back with its progress', function () {
    $import = ProductImport::start($this->owner->id, [['name' => 'A'], ['name' => 'B']]);
    $import->recordFailure('Bad row.');
    $this->repository->save($import);

    $loaded = $this->repository->findById($import->id());

    expect($loaded->ownerId())->toBe($this->owner->id)
        ->and($loaded->rows())->toBe([['name' => 'A'], ['name' => 'B']])
        ->and($loaded->status())->toBe(ImportStatus::Processing)
        ->and($loaded->processed())->toBe(1)
        ->and($loaded->errors())->toBe([['row' => 0, 'message' => 'Bad row.']])
        ->and($loaded->createdAt()->getTimestamp())->toBe($import->createdAt()->getTimestamp());
});

test('loading an import for update locks its row', function () {
    $import = ProductImport::start($this->owner->id, [['name' => 'A']]);
    $this->repository->save($import);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->repository->findByIdForUpdate($import->id());
    DB::disableQueryLog();

    expect(collect(DB::getQueryLog())->pluck('query')->filter(fn ($sql) => str_contains($sql, 'for update')))->toHaveCount(1);
});

test('the job runs on the imports queue with a timeout below the queue visibility timeout', function () {
    $job = new ProcessProductImportJob('0f8fad5b-d9cb-469f-a165-70867728950e');

    expect($job)->toBeInstanceOf(ShouldQueue::class)
        ->and($job->queue)->toBe('imports')
        ->and($job->tries)->toBe(3)
        ->and($job->timeout)->toBeLessThan(360)
        ->and($job->backoff())->toBe([30, 120]);
});

test('a job that ran out of attempts marks the import as failed', function () {
    $import = ProductImport::start($this->owner->id, [['name' => 'A']]);
    $this->repository->save($import);

    (new ProcessProductImportJob($import->id()->value()))->failed(new RuntimeException('Database unreachable'));

    $loaded = $this->repository->findById($import->id());
    expect($loaded->status())->toBe(ImportStatus::Failed)
        ->and($loaded->errors())->toBe([['row' => null, 'message' => __('catalog.import_failed')]]);
});
