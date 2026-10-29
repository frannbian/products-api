<?php

namespace App\Presentation\Http\Resources\Catalog;

use App\Domain\Catalog\Entities\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Amounts are returned as strings with 2 decimals to keep them exact.
 *
 * @property Product $resource
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->value(),
            'name' => $this->resource->name()->value(),
            'description' => $this->resource->description()->value(),
            'price' => $this->resource->price()->value(),
            'currency_id' => $this->resource->currencyId()->value(),
            'tax_cost' => $this->resource->taxCost()->value(),
            'manufacturing_cost' => $this->resource->manufacturingCost()->value(),
        ];
    }
}
