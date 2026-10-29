<?php

namespace App\Presentation\Http\Resources\Catalog;

use App\Domain\Catalog\Entities\ProductImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property ProductImport $resource
 */
class ProductImportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->value(),
            'status' => $this->resource->status()->value,
            'total' => $this->resource->total(),
            'processed' => $this->resource->processed(),
            'succeeded' => $this->resource->succeeded(),
            'failed' => $this->resource->failed(),
            'errors' => $this->resource->errors(),
            'created_at' => Carbon::instance($this->resource->createdAt())->toJSON(),
            'finished_at' => $this->resource->finishedAt() ? Carbon::instance($this->resource->finishedAt())->toJSON() : null,
        ];
    }
}
