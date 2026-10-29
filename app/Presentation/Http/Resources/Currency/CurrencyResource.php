<?php

namespace App\Presentation\Http\Resources\Currency;

use App\Domain\Currency\Entities\Currency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Currency $resource
 */
class CurrencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id()->value(),
            'name' => $this->resource->name()->value(),
            'symbol' => $this->resource->symbol()->value(),
            'exchange_rate' => $this->resource->exchangeRate()->value(),
        ];
    }
}
