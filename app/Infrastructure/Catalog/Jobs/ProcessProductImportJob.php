<?php

namespace App\Infrastructure\Catalog\Jobs;

use App\Application\Catalog\UseCases\ProcessProductImport;
use App\Domain\Catalog\Repositories\ProductImportRepository;
use App\Domain\Catalog\ValueObjects\ImportId;
use App\Infrastructure\Identity\Persistence\Eloquent\UserModel;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Worker side of a product import. The message only holds the import id
 * (claim check): the rows are read from the database.
 */
class ProcessProductImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Below the visibility timeout of the imports queue (360 s): SQS must not
     * hand the message to another worker while this one is still running.
     */
    public int $timeout = 300;

    public function __construct(
        public readonly string $importId,
    ) {
        $this->onQueue('imports');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(ProcessProductImport $process, ProductImportRepository $imports, AuthFactory $auth): void
    {
        $id = new ImportId($this->importId);
        $owner = UserModel::query()->find($imports->findById($id)?->ownerId());

        // Changes made by the import are audited as changes of its owner.
        if ($owner !== null) {
            $auth->guard('api')->setUser($owner);
        }

        $process->execute($id);
    }

    /**
     * Every attempt failed (not a row: the whole job, e.g. the database was
     * down): the import is marked failed so its owner is not left waiting.
     */
    public function failed(?Throwable $exception): void
    {
        $imports = app(ProductImportRepository::class);
        $import = $imports->findById(new ImportId($this->importId));

        if ($import !== null && ! $import->isFinished()) {
            $import->markFailed(__('catalog.import_failed'));
            $imports->save($import);
        }

        report($exception);
    }
}
