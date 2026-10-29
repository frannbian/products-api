<?php

namespace App\Infrastructure\Catalog\Imports;

use App\Application\Catalog\Contracts\ProductImportQueue;
use App\Domain\Catalog\ValueObjects\ImportId;
use App\Infrastructure\Catalog\Jobs\ProcessProductImportJob;
use Illuminate\Contracts\Bus\Dispatcher;

final class QueuedProductImportQueue implements ProductImportQueue
{
    public function __construct(
        private Dispatcher $bus,
    ) {}

    public function push(ImportId $id): void
    {
        $this->bus->dispatch(new ProcessProductImportJob($id->value()));
    }
}
