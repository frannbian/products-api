<?php

namespace App\Infrastructure\Catalog\Persistence;

use App\Domain\Catalog\Entities\ProductImport;
use App\Domain\Catalog\Enums\ImportStatus;
use App\Domain\Catalog\Repositories\ProductImportRepository;
use App\Domain\Catalog\ValueObjects\ImportId;
use App\Infrastructure\Catalog\Persistence\Eloquent\ProductImportModel;

final class EloquentProductImportRepository implements ProductImportRepository
{
    public function save(ProductImport $import): void
    {
        $model = ProductImportModel::query()->find($import->id()->value()) ?? new ProductImportModel;

        $model->forceFill([
            'id' => $import->id()->value(),
            'owner_id' => $import->ownerId(),
            'status' => $import->status()->value,
            'rows' => $import->rows(),
            'processed' => $import->processed(),
            'succeeded' => $import->succeeded(),
            'errors' => $import->errors(),
            'created_at' => $import->createdAt(),
            'finished_at' => $import->finishedAt(),
        ])->save();
    }

    public function findById(ImportId $id): ?ProductImport
    {
        $model = ProductImportModel::query()->find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    public function findByIdForUpdate(ImportId $id): ?ProductImport
    {
        $model = ProductImportModel::query()->whereKey($id->value())->lockForUpdate()->first();

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(ProductImportModel $model): ProductImport
    {
        return ProductImport::reconstitute(
            new ImportId($model->id),
            $model->owner_id,
            $model->rows,
            ImportStatus::from($model->status),
            $model->processed,
            $model->succeeded,
            $model->errors,
            $model->created_at,
            $model->finished_at,
        );
    }
}
