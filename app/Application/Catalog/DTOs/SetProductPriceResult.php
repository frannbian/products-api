<?php

namespace App\Application\Catalog\DTOs;

use App\Domain\Catalog\Entities\ProductPrice;

final readonly class SetProductPriceResult
{
    /**
     * @param  bool  $created  False when an existing price was replaced.
     */
    public function __construct(
        public ProductPrice $price,
        public bool $created,
    ) {}
}
