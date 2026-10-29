<?php

namespace App\Domain\Catalog\Events;

final class ProductUpdated extends ProductStateChanged
{
    public function name(): string
    {
        return 'product.updated';
    }
}
